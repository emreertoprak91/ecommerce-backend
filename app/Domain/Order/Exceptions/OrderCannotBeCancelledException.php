<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * Order Cannot Be Cancelled Exception
 *
 * Thrown when attempting to cancel an order that cannot be cancelled.
 */
final class OrderCannotBeCancelledException extends DomainException
{
    public function __construct(int $orderId, string $orderNumber, string $currentStatus)
    {
        parent::__construct(
            message: "Order '{$orderNumber}' cannot be cancelled. Current status: {$currentStatus}",
            code: 422,
            context: [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'current_status' => $currentStatus,
            ]
        );
    }
}
