<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * Product Not Found Exception
 */
class ProductNotFoundException extends DomainException
{
    public function __construct(int|string $identifier)
    {
        parent::__construct(
            message: "Product with identifier '{$identifier}' not found.",
            code: 404,
            context: ['identifier' => $identifier]
        );
    }
}
