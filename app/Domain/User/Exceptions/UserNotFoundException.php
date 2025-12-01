<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class UserNotFoundException extends DomainException
{
    public function __construct(string $email)
    {
        $this->context = ['email' => $email];
        parent::__construct('User not found', 404);
    }
}
