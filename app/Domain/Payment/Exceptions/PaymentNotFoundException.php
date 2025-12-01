<?php

declare(strict_types=1);

namespace App\Domain\Payment\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class PaymentNotFoundException extends DomainException
{
    public function __construct(int|string $identifier)
    {
        $this->context = ['identifier' => $identifier];
        parent::__construct("Payment not found: {$identifier}", 404);
    }
}
