<?php

declare(strict_types=1);

namespace App\Domain\Order\Enums;

/**
 * Order status values aligned with orders migration.
 */
enum OrderStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case PAID = 'paid';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    public function isFinal(): bool
    {
        return in_array($this, [self::DELIVERED, self::CANCELLED, self::REFUNDED], true);
    }
}
