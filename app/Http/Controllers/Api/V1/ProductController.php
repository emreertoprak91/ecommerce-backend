<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Product\DTOs\CreateProductDTO;
use App\Domain\Product\DTOs\ListProductsDTO;
use App\Domain\Product\DTOs\UpdateProductDTO;
use App\Domain\Product\Services\ProductService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\CreateProductRequest;
use App\Http\Requests\Api\V1\Product\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Annotations as OA;

/**
 * Product Controller
 *
 * TRY-CATCH KULLANILMIYOR!
 * Tüm exception'lar Handler.php tarafından yakalanır ve loglanır.
 * - ValidationException → 422 (FormRequest'ten otomatik)
 * - ProductNotFoundException → 404 (DomainException)
 * - DuplicateSkuException → 400 (DomainException)
 * - Diğer tüm hatalar → 500 (Handler loglar)
 */
final class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    /**
     * Display a listing of the products.
     *
     * @OA\Get(
     *     path="/products",
     *     operationId="getProducts",
     *     tags={"Products"},
     *     summary="Get list of products",
     *     description="Returns paginated list of products with filters",
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search term for name, sku, or description",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="is_active",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="min_price",
     *         in="query",
     *         description="Minimum price filter (in cents)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="max_price",
     *         in="query",
     *         description="Maximum price filter (in cents)",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="sort_by",
     *         in="query",
     *         description="Field to sort by",
     *         required=false,
     *         @OA\Schema(type="string", default="created_at", enum={"name", "price", "created_at", "stock_quantity"})
     *     ),
     *     @OA\Parameter(
     *         name="sort_order",
     *         in="query",
     *         description="Sort order",
     *         required=false,
     *         @OA\Schema(type="string", default="desc", enum={"asc", "desc"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Products retrieved successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/ProductResource")
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = new ListProductsDTO(
            perPage: (int) $request->get('per_page', 15),
            search: $request->get('search'),
            isActive: $request->has('is_active') ? (bool) $request->get('is_active') : null,
            minPrice: $request->has('min_price') ? (int) $request->get('min_price') : null,
            maxPrice: $request->has('max_price') ? (int) $request->get('max_price') : null,
            categories: $request->get('categories'),
            sortBy: $request->get('sort_by', 'created_at'),
            sortOrder: $request->get('sort_order', 'desc')
        );

        $products = $this->productService->listProducts($filters);

        return $this->paginatedResponse(
            $products,
            ProductResource::class,
            'Products retrieved successfully'
        );
    }

    /**
     * Store a newly created product.
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - DuplicateSkuException → 400
     *
     * @OA\Post(
     *     path="/products",
     *     operationId="createProduct",
     *     tags={"Products"},
     *     summary="Create a new product",
     *     description="Creates a new product (requires authentication)",
     *     security={{"bearerAuth": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "sku", "price"},
     *             @OA\Property(property="name", type="string", example="iPhone 15 Pro"),
     *             @OA\Property(property="sku", type="string", example="IPHONE-15-PRO-256"),
     *             @OA\Property(property="description", type="string", example="Latest Apple iPhone"),
     *             @OA\Property(property="price", type="integer", example=129900, description="Price in cents"),
     *             @OA\Property(property="stock_quantity", type="integer", example=100),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="categories", type="array", @OA\Items(type="string"), example={"electronics", "smartphones"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Product created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function store(CreateProductRequest $request): JsonResponse
    {
        $dto = CreateProductDTO::fromArray($request->validated());
        $product = $this->productService->createProduct($dto);

        return $this->createdResponse(
            new ProductResource($product),
            'Product created successfully'
        );
    }

    /**
     * Display the specified product.
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - ProductNotFoundException → 404
     *
     * @OA\Get(
     *     path="/products/{id}",
     *     operationId="getProduct",
     *     tags={"Products"},
     *     summary="Get product by ID",
     *     description="Returns a single product",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->getProduct($id);

        return $this->successResponse(
            new ProductResource($product),
            'Product retrieved successfully'
        );
    }

    /**
     * Update the specified product.
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - ProductNotFoundException → 404
     * - DuplicateSkuException → 400
     *
     * @OA\Put(
     *     path="/products/{id}",
     *     operationId="updateProduct",
     *     tags={"Products"},
     *     summary="Update existing product",
     *     description="Updates a product (requires authentication)",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="iPhone 15 Pro Max"),
     *             @OA\Property(property="sku", type="string", example="IPHONE-15-PRO-MAX-512"),
     *             @OA\Property(property="description", type="string", example="Updated description"),
     *             @OA\Property(property="price", type="integer", example=149900),
     *             @OA\Property(property="stock_quantity", type="integer", example=50),
     *             @OA\Property(property="is_active", type="boolean", example=true),
     *             @OA\Property(property="categories", type="array", @OA\Items(type="string"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function update(UpdateProductRequest $request, int $id): JsonResponse
    {
        $dto = UpdateProductDTO::fromArray($request->validated());
        $product = $this->productService->updateProduct($id, $dto);

        return $this->successResponse(
            new ProductResource($product),
            'Product updated successfully'
        );
    }

    /**
     * Remove the specified product.
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - ProductNotFoundException → 404
     *
     * @OA\Delete(
     *     path="/products/{id}",
     *     operationId="deleteProduct",
     *     tags={"Products"},
     *     summary="Delete a product",
     *     description="Deletes a product (requires authentication)",
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Product ID",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Product deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $this->productService->deleteProduct($id);

        return $this->deletedResponse('Product deleted successfully');
    }

    /**
     * Get product by slug.
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - ProductNotFoundException → 404
     *
     * @OA\Get(
     *     path="/products/slug/{slug}",
     *     operationId="getProductBySlug",
     *     tags={"Products"},
     *     summary="Get product by slug",
     *     description="Returns a single product by its slug",
     *     @OA\Parameter(
     *         name="slug",
     *         in="path",
     *         description="Product slug",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Product retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/ProductResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Product not found"
     *     )
     * )
     */
    public function showBySlug(string $slug): JsonResponse
    {
        $product = $this->productService->getProductBySlug($slug);

        return $this->successResponse(
            new ProductResource($product),
            'Product retrieved successfully'
        );
    }
}
