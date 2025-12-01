<?php

declare(strict_types=1);

namespace App\Domain\Payment\DTOs;

final readonly class InitiatePaymentDTO
{
    public function __construct(
        public int $userId,
        public int $orderId,
        public float $amount,
        public string $currency,
        public string $userEmail,
        public string $userName,
        public ?string $userPhone = null,
        public ?string $userAddress = null,
        public string $userIp = '127.0.0.1',
        public bool $termsAccepted = false,
        public ?string $termsAcceptedAt = null,
        public ?string $termsAcceptanceIp = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            orderId: (int) $data['order_id'],
            amount: (float) $data['amount'],
            currency: $data['currency'] ?? 'TRY',
            userEmail: $data['user_email'],
            userName: $data['user_name'],
            userPhone: $data['user_phone'] ?? null,
            userAddress: $data['user_address'] ?? null,
            userIp: $data['user_ip'] ?? '127.0.0.1',
            termsAccepted: (bool) ($data['terms_accepted'] ?? false),
            termsAcceptedAt: $data['terms_accepted_at'] ?? null,
            termsAcceptanceIp: $data['terms_acceptance_ip'] ?? null,
        );
    }
}
