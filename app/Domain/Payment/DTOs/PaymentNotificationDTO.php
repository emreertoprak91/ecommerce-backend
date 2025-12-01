<?php

declare(strict_types=1);

namespace App\Domain\Payment\DTOs;

final readonly class PaymentNotificationDTO
{
    public function __construct(
        public string $merchantOid,
        public string $status,
        public float $totalAmount,
        public string $hash,
        public ?string $failedReasonCode = null,
        public ?string $failedReasonMsg = null,
        public ?string $testMode = null,
        public ?string $paymentType = null,
        public ?string $currency = null,
        public ?int $paymentAmount = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            merchantOid: $data['merchant_oid'] ?? '',
            status: $data['status'] ?? '',
            totalAmount: (float) ($data['total_amount'] ?? 0),
            hash: $data['hash'] ?? '',
            failedReasonCode: $data['failed_reason_code'] ?? null,
            failedReasonMsg: $data['failed_reason_msg'] ?? null,
            testMode: $data['test_mode'] ?? null,
            paymentType: $data['payment_type'] ?? null,
            currency: $data['currency'] ?? null,
            paymentAmount: isset($data['payment_amount']) ? (int) $data['payment_amount'] : null,
        );
    }
}
