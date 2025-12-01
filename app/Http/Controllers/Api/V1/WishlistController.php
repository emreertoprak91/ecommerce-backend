<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Wishlist\Services\WishlistService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Wishlist\AddToWishlistRequest;
use App\Http\Requests\Api\V1\Wishlist\ToggleWishlistRequest;
use App\Http\Resources\Api\V1\WishlistResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Wishlist Controller
 *
 * TRY-CATCH KULLANILMIYOR!
 * Tüm exception'lar Handler.php tarafından yakalanır ve loglanır.
 * - ValidationException → 422 (FormRequest'ten otomatik)
 * - ProductNotFoundException → 404 (DomainException, Handler yakalar)
 * - Diğer tüm hatalar → 500 (Handler yakalar ve loglar)
 */
final class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistService $wishlistService
    ) {}

    /**
     * Get user's wishlist
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $wishlist = $this->wishlistService->getUserWishlist($request->user()->id, $perPage);

        return $this->paginatedResponse(
            $wishlist,
            WishlistResource::class,
            'Wishlist retrieved successfully'
        );
    }

    /**
     * Add product to wishlist
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - ProductNotFoundException → 404
     */
    public function store(AddToWishlistRequest $request): JsonResponse
    {
        $wishlist = $this->wishlistService->addToWishlist(
            $request->user()->id,
            (int) $request->validated('product_id')
        );

        return $this->createdResponse(
            new WishlistResource($wishlist),
            'Product added to wishlist'
        );
    }

    /**
     * Remove product from wishlist
     */
    public function destroy(int $productId, Request $request): JsonResponse
    {
        $removed = $this->wishlistService->removeFromWishlist(
            $request->user()->id,
            $productId
        );

        if (!$removed) {
            return $this->notFoundResponse('Product not found in wishlist');
        }

        return $this->deletedResponse('Product removed from wishlist');
    }

    /**
     * Toggle product in wishlist
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - ProductNotFoundException → 404
     */
    public function toggle(ToggleWishlistRequest $request): JsonResponse
    {
        $result = $this->wishlistService->toggleWishlist(
            $request->user()->id,
            (int) $request->validated('product_id')
        );

        return $this->successResponse([
            'added' => $result['added'],
            'in_wishlist' => $result['added'],
        ], $result['message']);
    }

    /**
     * Check if product is in wishlist
     */
    public function check(int $productId, Request $request): JsonResponse
    {
        $inWishlist = $this->wishlistService->isInWishlist(
            $request->user()->id,
            $productId
        );

        return $this->successResponse([
            'in_wishlist' => $inWishlist,
            'product_id' => $productId,
        ], 'Wishlist status retrieved');
    }

    /**
     * Clear all items from wishlist
     */
    public function clear(Request $request): JsonResponse
    {
        $count = $this->wishlistService->clearWishlist($request->user()->id);

        return $this->successResponse([
            'items_removed' => $count,
        ], 'Wishlist cleared successfully');
    }

    /**
     * Get wishlist count
     */
    public function count(Request $request): JsonResponse
    {
        $count = $this->wishlistService->getWishlistCount($request->user()->id);

        return $this->successResponse([
            'count' => $count,
        ], 'Wishlist count retrieved');
    }
}
