<?php

declare(strict_types=1);

namespace App\Domain\Product\Repositories;

use App\Domain\Product\DTOs\ListProductsDTO;
use App\Domain\Product\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Eloquent Product Repository
 *
 * Implementation of ProductRepositoryInterface using Eloquent ORM.
 */
final class EloquentProductRepository implements ProductRepositoryInterface
{
    public function __construct(
        private readonly Product $model
    ) {}

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?Product
    {
        return $this->model->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByUuid(string $uuid): ?Product
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findBySlug(string $slug): ?Product
    {
        return $this->model->where('slug', $slug)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function findBySku(string $sku): ?Product
    {
        return $this->model->where('sku', $sku)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(ListProductsDTO $filters): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        // Apply search filter
        if ($filters->search) {
            $query->search($filters->search);
        }

        // Apply active filter
        if ($filters->isActive !== null) {
            $query->where('is_active', $filters->isActive);
        }

        // Apply price range filter
        if ($filters->minPrice !== null || $filters->maxPrice !== null) {
            $query->priceRange($filters->minPrice, $filters->maxPrice);
        }

        // Apply category filter
        if ($filters->categories) {
            $query->whereHas('categories', function ($q) use ($filters) {
                $q->whereIn('categories.id', $filters->categories);
            });
        }

        // Apply sorting
        $sortBy = in_array($filters->sortBy, ['name', 'price', 'created_at', 'quantity'])
            ? $filters->sortBy
            : 'created_at';
        $sortOrder = in_array(strtolower($filters->sortOrder), ['asc', 'desc'])
            ? $filters->sortOrder
            : 'desc';

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($filters->perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function save(Product $product): Product
    {
        $product->save();
        return $product->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function skuExists(string $sku, ?int $excludeId = null): bool
    {
        $query = $this->model->where('sku', $sku);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function syncCategories(Product $product, array $categoryIds): void
    {
        $product->categories()->sync($categoryIds);
    }
}
