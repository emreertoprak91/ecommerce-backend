<?php

declare(strict_types=1);

namespace App\Domain\Product\Actions;

use App\Domain\Product\DTOs\CreateProductDTO;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Services\ProductService;

/**
 * Create Product Action
 *
 * Single-purpose action class for creating a product.
 * This provides a cleaner interface when you need to create products
 * from different contexts (HTTP, CLI, Queue jobs).
 */
final class CreateProductAction
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Execute the action.
     */
    public function execute(CreateProductDTO $dto): Product
    {
        return $this->productService->createProduct($dto);
    }
}
