<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Services;

use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Domain\Wishlist\Models\Wishlist;
use App\Domain\Wishlist\Repositories\WishlistRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;

final class WishlistService
{
    public function __construct(
        private readonly WishlistRepositoryInterface $wishlistRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger
    ) {}

    public function addToWishlist(int $userId, int $productId): Wishlist
    {
        // Check if product exists
        $product = $this->productRepository->findById($productId);
        if (!$product) {
            throw new ProductNotFoundException($productId);
        }

        $wishlist = $this->wishlistRepository->add($userId, $productId);

        $this->logger->info('[WishlistService::addToWishlist] Product added to wishlist', [
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        return $wishlist->load('product');
    }

    public function removeFromWishlist(int $userId, int $productId): bool
    {
        $removed = $this->wishlistRepository->remove($userId, $productId);

        if ($removed) {
            $this->logger->info('[WishlistService::removeFromWishlist] Product removed from wishlist', [
                'user_id' => $userId,
                'product_id' => $productId,
            ]);
        }

        return $removed;
    }

    public function getUserWishlist(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->wishlistRepository->getUserWishlist($userId, $perPage);
    }

    public function getAllUserWishlist(int $userId): Collection
    {
        return $this->wishlistRepository->getAllUserWishlist($userId);
    }

    public function isInWishlist(int $userId, int $productId): bool
    {
        return $this->wishlistRepository->isInWishlist($userId, $productId);
    }

    public function getWishlistCount(int $userId): int
    {
        return $this->wishlistRepository->getCount($userId);
    }

    public function clearWishlist(int $userId): int
    {
        $count = $this->wishlistRepository->clear($userId);

        $this->logger->info('[WishlistService::clearWishlist] Wishlist cleared', [
            'user_id' => $userId,
            'items_removed' => $count,
        ]);

        return $count;
    }

    public function toggleWishlist(int $userId, int $productId): array
    {
        if ($this->isInWishlist($userId, $productId)) {
            $this->removeFromWishlist($userId, $productId);
            return ['added' => false, 'message' => 'Product removed from wishlist'];
        }

        $this->addToWishlist($userId, $productId);
        return ['added' => true, 'message' => 'Product added to wishlist'];
    }
}
