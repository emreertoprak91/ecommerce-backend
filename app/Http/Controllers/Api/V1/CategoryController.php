<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Product\Services\CategoryService;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Category Controller
 *
 * Category operasyonlarını yöneten API controller.
 * Business logic CategoryService'de, DB operasyonları Repository'de.
 */
final class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    /**
     * Display a listing of the categories.
     */
    public function index(Request $request): JsonResponse
    {
        $parentId = $request->has('parent_id')
            ? ($request->get('parent_id') === 'null' ? null : (int) $request->get('parent_id'))
            : null;

        $withChildren = $request->boolean('with_children');

        $categories = $request->has('parent_id')
            ? $this->categoryService->getAllActive($parentId, $withChildren)
            : $this->categoryService->getAllActive(null, $withChildren);

        return $this->successResponse(
            CategoryResource::collection($categories),
            'Categories retrieved successfully'
        );
    }

    /**
     * Display the specified category.
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - CategoryNotFoundException → 404
     */
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        return $this->successResponse(
            new CategoryResource($category),
            'Category retrieved successfully'
        );
    }

    /**
     * Display the specified category by slug.
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - CategoryNotFoundException → 404
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $category = $this->categoryService->findBySlug($slug);

        return $this->successResponse(
            new CategoryResource($category),
            'Category retrieved successfully'
        );
    }
}
