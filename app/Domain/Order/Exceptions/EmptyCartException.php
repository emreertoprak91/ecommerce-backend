<?php

declare(strict_types=1);

namespace App\Domain\Order\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class EmptyCartException extends DomainException
{
    public function __construct()
    {
        parent::__construct('Cart is empty. Please add items before checkout.', 422);
    }
}
