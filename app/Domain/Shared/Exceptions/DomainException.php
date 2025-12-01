<?php

declare(strict_types=1);

namespace App\Domain\Shared\Exceptions;

use Exception;

/**
 * Base Domain Exception
 *
 * Tum domain-specific exceptionlar bu siniftan turetilir.
 */
class DomainException extends Exception
{
    protected array $context = [];

    public function __construct(
        string $message = '',
        int $code = 400,
        ?Exception $previous = null,
        array $context = []
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
