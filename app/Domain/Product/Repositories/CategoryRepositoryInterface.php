<?php

declare(strict_types=1);

namespace App\Domain\Product\Repositories;

use App\Domain\Product\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    /**
     * Get all active categories.
     */
    public function getAllActive(?int $parentId = null, bool $withChildren = false): Collection;

    /**
     * Find category by ID.
     */
    public function findById(int $id, bool $withChildren = false): ?Category;

    /**
     * Find category by slug.
     */
    public function findBySlug(string $slug, bool $withChildren = false): ?Category;

    /**
     * Get root categories (parent_id = null).
     */
    public function getRootCategories(bool $withChildren = false): Collection;

    /**
     * Get children of a category.
     */
    public function getChildren(int $parentId): Collection;
}
