<?php

declare(strict_types=1);

namespace App\Domain\Product\Exceptions;

use App\Domain\Shared\Exceptions\DomainException;

final class CategoryNotFoundException extends DomainException
{
    public function __construct(int|string $identifier)
    {
        $this->context = ['identifier' => $identifier];
        parent::__construct("Category not found: {$identifier}", 404);
    }
}
