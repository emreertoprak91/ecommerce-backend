<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Order\Services\OrderService;
use App\Domain\Payment\DTOs\InitiatePaymentDTO;
use App\Domain\Payment\Services\PayTRService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Payment\InitiatePaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

final class PaymentController extends Controller
{
    public function __construct(
        private readonly PayTRService $payTRService,
        private readonly OrderService $orderService
    ) {}

    /**
     * Initiate payment for an order
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - OrderNotFoundException → 404
     * - PaymentFailedException → 400
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $user = $request->user();
        $order = $this->orderService->findById((int) $validated['order_id']);

        // Ensure user owns this order
        if ($order->user_id !== $user->id) {
            return $this->forbiddenResponse('You do not have permission to pay for this order');
        }

        // Check if order is already paid
        if ($order->isPaid()) {
            return $this->errorResponse('This order has already been paid', 422);
        }

        $dto = InitiatePaymentDTO::fromArray([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'amount' => (float) $order->total_amount,
            'currency' => $order->currency,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'user_phone' => $order->shipping_phone,
            'user_address' => $order->shipping_address,
            'user_ip' => $request->ip(),
            'terms_accepted' => $validated['terms_accepted'] ?? true,
            'terms_accepted_at' => now()->toISOString(),
            'terms_acceptance_ip' => $request->ip(),
        ]);

        $result = $this->payTRService->initiatePayment($dto);

        return $this->successResponse([
            'success' => true,
            'token' => $result['token'],
            'merchant_oid' => $result['merchant_oid'],
            'payment_id' => $result['payment_id'],
            'iframe_url' => 'https://www.paytr.com/odeme/guvenli/' . $result['token'],
        ], 'Payment initiated successfully');
    }

    /**
     * Handle PayTR notification (webhook)
     *
     * Not: Webhook'lar için loglama kritik önemde, bu yüzden burada tutuldu.
     */
    public function notify(Request $request): Response
    {
        $data = $request->all();

        Log::channel('syslog')->info('[PaymentController::notify] PayTR notification received', [
            'merchant_oid' => $data['merchant_oid'] ?? 'unknown',
            'status' => $data['status'] ?? 'unknown',
        ]);

        try {
            $result = $this->payTRService->handleNotification($data);

            if ($result === PayTRService::RESULT_HASH_MISMATCH) {
                return response('Bad hash', 400)->header('Content-Type', 'text/plain');
            }

            if ($result === PayTRService::RESULT_NOT_FOUND) {
                return response('Order not found', 404)->header('Content-Type', 'text/plain');
            }

            return response('OK', 200)->header('Content-Type', 'text/plain');

        } catch (\Throwable $e) {
            Log::channel('syslog')->error('[PaymentController::notify] Webhook error', [
                'data' => $data,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            // Return OK to prevent PayTR from retrying
            return response('OK', 200)->header('Content-Type', 'text/plain');
        }
    }

    /**
     * Get payment status
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - PaymentNotFoundException → 404
     */
    public function status(string $merchantOid, Request $request): JsonResponse
    {
        $payment = $this->payTRService->getPaymentByMerchantOid($merchantOid);

        // Ensure user owns this payment
        if ($payment->user_id !== $request->user()->id) {
            return $this->forbiddenResponse('You do not have permission to view this payment');
        }

        return $this->successResponse([
            'merchant_oid' => $payment->merchant_oid,
            'status' => $payment->status,
            'status_label' => $payment->status_label,
            'amount' => $payment->amount,
            'formatted_amount' => $payment->formatted_amount,
            'currency' => $payment->currency,
            'completed_at' => $payment->completed_at?->toISOString(),
            'order_id' => $payment->order_id,
        ], 'Payment status retrieved');
    }
}
