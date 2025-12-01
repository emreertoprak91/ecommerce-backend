<?php

declare(strict_types=1);

namespace App\Domain\Product\Services;

use App\Domain\Product\Exceptions\CategoryNotFoundException;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Repositories\CategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {}

    /**
     * Get all active categories.
     */
    public function getAllActive(?int $parentId = null, bool $withChildren = false): Collection
    {
        return $this->categoryRepository->getAllActive($parentId, $withChildren);
    }

    /**
     * Get root categories.
     */
    public function getRootCategories(bool $withChildren = false): Collection
    {
        return $this->categoryRepository->getRootCategories($withChildren);
    }

    /**
     * Find category by ID.
     *
     * @throws CategoryNotFoundException
     */
    public function findById(int $id, bool $withChildren = true): Category
    {
        $category = $this->categoryRepository->findById($id, $withChildren);

        if (!$category) {
            throw new CategoryNotFoundException($id);
        }

        return $category;
    }

    /**
     * Find category by slug.
     *
     * @throws CategoryNotFoundException
     */
    public function findBySlug(string $slug, bool $withChildren = true): Category
    {
        $category = $this->categoryRepository->findBySlug($slug, $withChildren);

        if (!$category) {
            throw new CategoryNotFoundException($slug);
        }

        return $category;
    }

    /**
     * Get children of a category.
     */
    public function getChildren(int $parentId): Collection
    {
        return $this->categoryRepository->getChildren($parentId);
    }
}
