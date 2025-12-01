<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class EmailNotVerifiedException extends DomainException
{
    public function __construct(
        public readonly string $email
    ) {
        $this->context = ['email' => $email];
        parent::__construct('Please verify your email address before logging in.', 422);
    }
}
