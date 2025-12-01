<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class PaymentFailedException extends DomainException
{
    public function __construct(string $message = 'Payment failed')
    {
        parent::__construct($message, 400);
    }
}
