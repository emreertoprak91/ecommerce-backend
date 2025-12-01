<?php

declare(strict_types=1);

namespace App\Domain\Order\DTOs;

final readonly class OrderItemDTO
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            productId: (int) $data['product_id'],
            quantity: (int) ($data['quantity'] ?? 1),
        );
    }
}
