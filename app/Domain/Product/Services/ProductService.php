<?php

declare(strict_types=1);

namespace App\Domain\Product\Services;

use App\Domain\Product\DTOs\CreateProductDTO;
use App\Domain\Product\DTOs\ListProductsDTO;
use App\Domain\Product\DTOs\UpdateProductDTO;
use App\Domain\Product\Events\ProductCreatedEvent;
use App\Domain\Product\Events\ProductDeletedEvent;
use App\Domain\Product\Events\ProductUpdatedEvent;
use App\Domain\Product\Exceptions\DuplicateSkuException;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Psr\Log\LoggerInterface;

/**
 * Product Service
 *
 * Handles all product-related business logic operations.
 */
final class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Get paginated list of products.
     */
    public function listProducts(ListProductsDTO $filters): LengthAwarePaginator
    {
        $this->logger->info('Listing products', [
            'filters' => [
                'per_page' => $filters->perPage,
                'search' => $filters->search,
                'is_active' => $filters->isActive,
            ],
        ]);

        return $this->productRepository->paginate($filters);
    }

    /**
     * Get a single product by ID.
     *
     * @throws ProductNotFoundException
     */
    public function getProduct(int $id): Product
    {
        $product = $this->productRepository->findById($id);

        if (!$product) {
            throw new ProductNotFoundException($id);
        }

        return $product;
    }

    /**
     * Get a single product by slug.
     *
     * @throws ProductNotFoundException
     */
    public function getProductBySlug(string $slug): Product
    {
        $product = $this->productRepository->findBySlug($slug);

        if (!$product) {
            throw new ProductNotFoundException($slug);
        }

        return $product;
    }

    /**
     * Create a new product.
     *
     * @throws DuplicateSkuException
     */
    public function createProduct(CreateProductDTO $dto): Product
    {
        $this->logger->info('Creating new product', ['sku' => $dto->sku]);

        // Check for duplicate SKU
        if ($this->productRepository->skuExists($dto->sku)) {
            throw new DuplicateSkuException($dto->sku);
        }

        return DB::transaction(function () use ($dto) {
            // Create product
            $product = new Product($dto->toArray());
            $product = $this->productRepository->save($product);

            // Sync categories if provided
            if (!empty($dto->categories)) {
                $this->productRepository->syncCategories($product, $dto->categories);
            }

            // Dispatch event
            Event::dispatch(new ProductCreatedEvent($product));

            $this->logger->info('Product created successfully', [
                'product_id' => $product->id,
                'sku' => $product->sku,
            ]);

            return $product->load('categories');
        });
    }

    /**
     * Update an existing product.
     *
     * @throws ProductNotFoundException
     * @throws DuplicateSkuException
     */
    public function updateProduct(int $id, UpdateProductDTO $dto): Product
    {
        $product = $this->getProduct($id);

        $this->logger->info('Updating product', [
            'product_id' => $id,
            'changes' => array_keys($dto->toArray()),
        ]);

        // Check for duplicate SKU if SKU is being changed
        if ($dto->sku && $dto->sku !== $product->sku) {
            if ($this->productRepository->skuExists($dto->sku, $id)) {
                throw new DuplicateSkuException($dto->sku);
            }
        }

        return DB::transaction(function () use ($product, $dto) {
            $originalAttributes = $product->getAttributes();

            // Update product
            $product->fill($dto->toArray());
            $changedAttributes = $product->getDirty();
            $product = $this->productRepository->save($product);

            // Sync categories if provided
            if ($dto->categories !== null) {
                $this->productRepository->syncCategories($product, $dto->categories);
            }

            // Dispatch event
            Event::dispatch(new ProductUpdatedEvent($product, $changedAttributes));

            $this->logger->info('Product updated successfully', [
                'product_id' => $product->id,
                'changed_attributes' => array_keys($changedAttributes),
            ]);

            return $product->load('categories');
        });
    }

    /**
     * Delete a product.
     *
     * @throws ProductNotFoundException
     */
    public function deleteProduct(int $id): bool
    {
        $product = $this->getProduct($id);

        $this->logger->info('Deleting product', [
            'product_id' => $id,
            'sku' => $product->sku,
        ]);

        $productId = $product->id;
        $productUuid = $product->uuid;
        $productSku = $product->sku;

        $deleted = $this->productRepository->delete($product);

        if ($deleted) {
            // Dispatch event
            Event::dispatch(new ProductDeletedEvent($productId, $productUuid, $productSku));

            $this->logger->info('Product deleted successfully', [
                'product_id' => $productId,
                'sku' => $productSku,
            ]);
        }

        return $deleted;
    }
}
