<?php

declare(strict_types=1);

namespace App\Domain\Order\DTOs;

final readonly class CreateOrderDTO
{
    public function __construct(
        public int $userId,
        public array $items,
        public ?string $shippingName = null,
        public ?string $shippingPhone = null,
        public ?string $shippingAddress = null,
        public ?string $shippingCity = null,
        public ?string $shippingDistrict = null,
        public ?string $shippingPostalCode = null,
        public string $shippingCountry = 'TR',
        public ?string $billingName = null,
        public ?string $billingPhone = null,
        public ?string $billingAddress = null,
        public ?string $billingCity = null,
        public ?string $billingDistrict = null,
        public ?string $billingPostalCode = null,
        public string $billingCountry = 'TR',
        public ?string $notes = null,
        public string $currency = 'TRY',
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: (int) $data['user_id'],
            items: $data['items'] ?? [],
            shippingName: $data['shipping_name'] ?? null,
            shippingPhone: $data['shipping_phone'] ?? null,
            shippingAddress: $data['shipping_address'] ?? null,
            shippingCity: $data['shipping_city'] ?? null,
            shippingDistrict: $data['shipping_district'] ?? null,
            shippingPostalCode: $data['shipping_postal_code'] ?? null,
            shippingCountry: $data['shipping_country'] ?? 'TR',
            billingName: $data['billing_name'] ?? null,
            billingPhone: $data['billing_phone'] ?? null,
            billingAddress: $data['billing_address'] ?? null,
            billingCity: $data['billing_city'] ?? null,
            billingDistrict: $data['billing_district'] ?? null,
            billingPostalCode: $data['billing_postal_code'] ?? null,
            billingCountry: $data['billing_country'] ?? 'TR',
            notes: $data['notes'] ?? null,
            currency: $data['currency'] ?? 'TRY',
        );
    }
}
