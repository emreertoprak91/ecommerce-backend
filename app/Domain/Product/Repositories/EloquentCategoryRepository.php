<?php

declare(strict_types=1);

namespace App\Domain\Product\Repositories;

use App\Domain\Product\Models\Category;
use Illuminate\Database\Eloquent\Collection;

final class EloquentCategoryRepository implements CategoryRepositoryInterface
{
    /**
     * Get all active categories.
     */
    public function getAllActive(?int $parentId = null, bool $withChildren = false): Collection
    {
        $query = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }

        if ($withChildren) {
            $query->with(['children' => fn($q) => $q
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
            ]);
        }

        return $query->get();
    }

    /**
     * Find category by ID.
     */
    public function findById(int $id, bool $withChildren = false): ?Category
    {
        $query = Category::query();

        if ($withChildren) {
            $query->with(['children' => fn($q) => $q
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
            ]);
        }

        return $query->find($id);
    }

    /**
     * Find category by slug.
     */
    public function findBySlug(string $slug, bool $withChildren = false): ?Category
    {
        $query = Category::query()
            ->where('slug', $slug)
            ->where('is_active', true);

        if ($withChildren) {
            $query->with(['children' => fn($q) => $q
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
            ]);
        }

        return $query->first();
    }

    /**
     * Get root categories (parent_id = null).
     */
    public function getRootCategories(bool $withChildren = false): Collection
    {
        $query = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        if ($withChildren) {
            $query->with(['children' => fn($q) => $q
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
            ]);
        }

        return $query->get();
    }

    /**
     * Get children of a category.
     */
    public function getChildren(int $parentId): Collection
    {
        return Category::query()
            ->where('parent_id', $parentId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }
}
