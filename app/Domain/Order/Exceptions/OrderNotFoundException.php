<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class OrderNotFoundException extends DomainException
{
    public function __construct(int|string $identifier)
    {
        $this->context = ['identifier' => $identifier];
        parent::__construct("Order not found: {$identifier}", 404);
    }
}
