<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

/**
 * Duplicate SKU Exception
 */
class DuplicateSkuException extends DomainException
{
    public function __construct(string $sku)
    {
        parent::__construct(
            message: "Product with SKU '{$sku}' already exists.",
            code: 409,
            context: ['sku' => $sku]
        );
    }
}
