<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InsufficientStockException extends DomainException
{
    public function __construct(int $productId, string $productName, int $requested, int $available)
    {
        $this->context = [
            'product_id' => $productId,
            'product_name' => $productName,
            'requested' => $requested,
            'available' => $available,
        ];
        parent::__construct(
            "Insufficient stock for '{$productName}'. Requested: {$requested}, Available: {$available}",
            422
        );
    }
}
