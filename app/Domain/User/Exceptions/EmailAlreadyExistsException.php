<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class EmailAlreadyExistsException extends DomainException
{
    public function __construct(string $email)
    {
        $this->context = ['email' => $email];
        parent::__construct("Email '{$email}' is already registered.", 422);
    }
}
