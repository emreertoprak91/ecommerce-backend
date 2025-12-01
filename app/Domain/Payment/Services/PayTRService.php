<?php

declare(strict_types=1);

namespace App\Domain\Payment\Services;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Services\OrderService;
use App\Domain\Payment\DTOs\InitiatePaymentDTO;
use App\Domain\Payment\DTOs\PaymentNotificationDTO;
use App\Domain\Payment\Exceptions\PaymentFailedException;
use App\Domain\Payment\Exceptions\PaymentNotFoundException;
use App\Domain\Payment\Models\Payment;
use App\Domain\Payment\Repositories\PaymentRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class PayTRService
{
    public const RESULT_SUCCESS = 'success';
    public const RESULT_FAILED = 'failed';
    public const RESULT_HASH_MISMATCH = 'hash_mismatch';
    public const RESULT_NOT_FOUND = 'not_found';
    public const RESULT_ALREADY_PROCESSED = 'already_processed';

    private string $merchantId;
    private string $merchantKey;
    private string $merchantSalt;
    private bool $testMode;

    public function __construct(
        private readonly PaymentRepositoryInterface $paymentRepository,
        private readonly OrderService $orderService,
        private readonly LoggerInterface $logger
    ) {
        $this->merchantId = config('services.paytr.merchant_id', '');
        $this->merchantKey = config('services.paytr.merchant_key', '');
        $this->merchantSalt = config('services.paytr.merchant_salt', '');
        $this->testMode = (bool) config('services.paytr.test_mode', true);
    }

    public function initiatePayment(InitiatePaymentDTO $dto): array
    {
        // Generate merchant order ID
        $merchantOid = 'ORD' . Str::upper(Str::random(8)) . $dto->userId . time();

        // Convert amount to kuruş (cents)
        $paymentAmount = (int) round($dto->amount * 100);

        // Create payment record
        $payment = $this->paymentRepository->create([
            'user_id' => $dto->userId,
            'order_id' => $dto->orderId,
            'merchant_oid' => $merchantOid,
            'payment_provider' => 'paytr',
            'amount' => $dto->amount,
            'payment_amount' => $paymentAmount,
            'currency' => $dto->currency,
            'status' => Payment::STATUS_PENDING,
            'terms_accepted' => $dto->termsAccepted,
            'terms_accepted_at' => $dto->termsAcceptedAt,
            'terms_acceptance_ip' => $dto->termsAcceptanceIp,
        ]);

        // Prepare basket
        $order = $this->orderService->findById($dto->orderId);
        $basket = $this->prepareBasket($order);
        $userBasket = base64_encode(json_encode($basket));

        // PayTR parameters
        $noInstallment = 0;
        $maxInstallment = 0;
        $timeoutLimit = 30;
        $debugOn = 1;
        $testMode = $this->testMode ? 1 : 0;

        // Generate hash
        $hashStr = $this->merchantId . $dto->userIp . $merchantOid . $dto->userEmail
            . $paymentAmount . $userBasket . $noInstallment . $maxInstallment
            . $dto->currency . $testMode;
        $paytrToken = base64_encode(hash_hmac('sha256', $hashStr . $this->merchantSalt, $this->merchantKey, true));

        // Prepare POST data
        $postData = [
            'merchant_id' => $this->merchantId,
            'user_ip' => $dto->userIp,
            'merchant_oid' => $merchantOid,
            'email' => $dto->userEmail,
            'payment_amount' => $paymentAmount,
            'paytr_token' => $paytrToken,
            'user_basket' => $userBasket,
            'debug_on' => $debugOn,
            'no_installment' => $noInstallment,
            'max_installment' => $maxInstallment,
            'user_name' => $dto->userName,
            'user_address' => $dto->userAddress ?? 'Adres belirtilmedi',
            'user_phone' => $dto->userPhone ?? '0000000000',
            'merchant_ok_url' => config('app.frontend_url') . '/payment/success',
            'merchant_fail_url' => config('app.frontend_url') . '/payment/fail',
            'timeout_limit' => $timeoutLimit,
            'currency' => $dto->currency,
            'test_mode' => $testMode,
        ];

        try {
            $response = Http::asForm()
                ->timeout(30)
                ->post('https://www.paytr.com/odeme/api/get-token', $postData);

            $result = $response->json();

            if (!$result || ($result['status'] ?? '') !== 'success') {
                $errorMessage = $result['reason'] ?? 'PayTR token alınamadı';

                $this->paymentRepository->update($payment, [
                    'status' => Payment::STATUS_FAILED,
                    'error_message' => $errorMessage,
                    'provider_response' => $result,
                ]);

                $this->logger->error('[PayTRService::initiatePayment] PayTR token failed', [
                    'merchant_oid' => $merchantOid,
                    'error' => $errorMessage,
                    'response' => $result,
                ]);

                throw new PaymentFailedException($errorMessage);
            }

            // Update payment with token
            $this->paymentRepository->update($payment, [
                'paytr_token' => $result['token'],
                'status' => Payment::STATUS_PROCESSING,
            ]);

            $this->logger->info('[PayTRService::initiatePayment] Payment initiated', [
                'payment_id' => $payment->id,
                'merchant_oid' => $merchantOid,
                'amount' => $dto->amount,
            ]);

            return [
                'success' => true,
                'token' => $result['token'],
                'merchant_oid' => $merchantOid,
                'payment_id' => $payment->id,
            ];

        } catch (\Exception $e) {
            $this->logger->error('[PayTRService::initiatePayment] Exception', [
                'merchant_oid' => $merchantOid,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            throw new PaymentFailedException($e->getMessage());
        }
    }

    public function handleNotification(array $data): string
    {
        $dto = PaymentNotificationDTO::fromArray($data);

        // Verify hash - PayTR sends total_amount in kuruş (cents), no conversion needed
        $hashStr = $dto->merchantOid . $this->merchantSalt . $dto->status . (int) $dto->totalAmount;
        $expectedHash = base64_encode(hash_hmac('sha256', $hashStr, $this->merchantKey, true));

        if ($expectedHash !== $dto->hash) {
            $this->logger->warning('[PayTRService::handleNotification] Hash mismatch', [
                'merchant_oid' => $dto->merchantOid,
                'expected_hash' => $expectedHash,
                'received_hash' => $dto->hash,
            ]);
            return self::RESULT_HASH_MISMATCH;
        }

        // Find payment
        $payment = $this->paymentRepository->findByMerchantOid($dto->merchantOid);
        if (!$payment) {
            $this->logger->warning('[PayTRService::handleNotification] Payment not found', [
                'merchant_oid' => $dto->merchantOid,
            ]);
            return self::RESULT_NOT_FOUND;
        }

        // Check if already processed
        if ($payment->isCompleted() || $payment->isFailed()) {
            $this->logger->info('[PayTRService::handleNotification] Already processed', [
                'payment_id' => $payment->id,
                'merchant_oid' => $dto->merchantOid,
                'status' => $payment->status,
            ]);
            return self::RESULT_ALREADY_PROCESSED;
        }

        // Process payment
        if ($dto->status === 'success') {
            $payment->markAsCompleted($dto->merchantOid, $data);

            // Update order status
            if ($payment->order_id) {
                $this->orderService->markAsPaid($payment->order_id);
            }

            $this->logger->info('[PayTRService::handleNotification] Payment completed', [
                'payment_id' => $payment->id,
                'merchant_oid' => $dto->merchantOid,
                'amount' => $payment->amount,
            ]);

            return self::RESULT_SUCCESS;
        }

        // Payment failed
        $errorMessage = $dto->failedReasonMsg ?? 'Ödeme başarısız';
        $payment->markAsFailed($errorMessage, $data);

        $this->logger->warning('[PayTRService::handleNotification] Payment failed', [
            'payment_id' => $payment->id,
            'merchant_oid' => $dto->merchantOid,
            'reason_code' => $dto->failedReasonCode,
            'reason_msg' => $errorMessage,
        ]);

        return self::RESULT_FAILED;
    }

    public function getPaymentByMerchantOid(string $merchantOid): Payment
    {
        $payment = $this->paymentRepository->findByMerchantOid($merchantOid);

        if (!$payment) {
            throw new PaymentNotFoundException($merchantOid);
        }

        return $payment;
    }

    private function prepareBasket(Order $order): array
    {
        $basket = [];

        foreach ($order->items as $item) {
            $basket[] = [
                $item->product_name,
                number_format((float) $item->unit_price, 2, '.', ''),
                $item->quantity,
            ];
        }

        // Add shipping if applicable
        if ($order->shipping_amount > 0) {
            $basket[] = [
                'Kargo Ücreti',
                number_format((float) $order->shipping_amount, 2, '.', ''),
                1,
            ];
        }

        return $basket;
    }
}
