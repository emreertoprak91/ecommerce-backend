<?php

declare(strict_types=1);

namespace App\Domain\Wishlist\Repositories;

use App\Domain\Wishlist\Models\Wishlist;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface WishlistRepositoryInterface
{
    public function findByUserAndProduct(int $userId, int $productId): ?Wishlist;

    public function getUserWishlist(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function getAllUserWishlist(int $userId): Collection;

    public function add(int $userId, int $productId): Wishlist;

    public function remove(int $userId, int $productId): bool;

    public function isInWishlist(int $userId, int $productId): bool;

    public function getCount(int $userId): int;

    public function clear(int $userId): int;
}
