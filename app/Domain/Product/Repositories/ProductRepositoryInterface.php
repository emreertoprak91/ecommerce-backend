<?php

declare(strict_types=1);

namespace App\Domain\Product\Repositories;

use App\Domain\Product\DTOs\ListProductsDTO;
use App\Domain\Product\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Product Repository Interface
 *
 * Defines the contract for product data access operations.
 */
interface ProductRepositoryInterface
{
    /**
     * Find a product by ID.
     */
    public function findById(int $id): ?Product;

    /**
     * Find a product by UUID.
     */
    public function findByUuid(string $uuid): ?Product;

    /**
     * Find a product by slug.
     */
    public function findBySlug(string $slug): ?Product;

    /**
     * Find a product by SKU.
     */
    public function findBySku(string $sku): ?Product;

    /**
     * Get paginated list of products with filters.
     */
    public function paginate(ListProductsDTO $filters): LengthAwarePaginator;

    /**
     * Save a product (create or update).
     */
    public function save(Product $product): Product;

    /**
     * Delete a product.
     */
    public function delete(Product $product): bool;

    /**
     * Check if SKU exists.
     */
    public function skuExists(string $sku, ?int $excludeId = null): bool;

    /**
     * Sync product categories.
     */
    public function syncCategories(Product $product, array $categoryIds): void;
}
