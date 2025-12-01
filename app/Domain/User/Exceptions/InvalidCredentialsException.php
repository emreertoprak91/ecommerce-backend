<?php

declare(strict_types=1);

namespace App\Domain\User\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class InvalidCredentialsException extends DomainException
{
    public function __construct()
    {
        parent::__construct('The provided credentials are incorrect.', 401);
    }
}
