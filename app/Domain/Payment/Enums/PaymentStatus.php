<?php

declare(strict_types=1);

namespace App\Domain\Payment\Enums;

/**
 * Payment status values aligned with payments migration.
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';

    public function isFinal(): bool
    {
        return in_array($this, [self::COMPLETED, self::FAILED, self::REFUNDED, self::CANCELLED], true);
    }
}
