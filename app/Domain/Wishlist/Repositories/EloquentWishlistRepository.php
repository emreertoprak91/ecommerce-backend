<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Repositories;

use App\Domain\Wishlist\Models\Wishlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class EloquentWishlistRepository implements WishlistRepositoryInterface
{
    public function findByUserAndProduct(int $userId, int $productId): ?Wishlist
    {
        return Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
    }

    public function getUserWishlist(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Wishlist::with(['product', 'product.categories'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getAllUserWishlist(int $userId): Collection
    {
        return Wishlist::with(['product', 'product.categories'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function add(int $userId, int $productId): Wishlist
    {
        return Wishlist::firstOrCreate([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);
    }

    public function remove(int $userId, int $productId): bool
    {
        return Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->delete() > 0;
    }

    public function isInWishlist(int $userId, int $productId): bool
    {
        return Wishlist::where('user_id', $userId)
            ->where('product_id', $productId)
            ->exists();
    }

    public function getCount(int $userId): int
    {
        return Wishlist::where('user_id', $userId)->count();
    }

    public function clear(int $userId): int
    {
        return Wishlist::where('user_id', $userId)->delete();
    }
}
