<?php

declare(strict_types=1);

namespace App\Domain\Order\Repositories;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;

    public function findByOrderNumber(string $orderNumber): ?Order;

    public function findByUserId(int $userId, int $perPage = 15): LengthAwarePaginator;

    public function create(array $data): Order;

    public function createOrderItem(array $data): OrderItem;

    public function update(Order $order, array $data): Order;

    public function delete(Order $order): bool;

    public function getUserOrders(int $userId): Collection;

    public function getRecentOrders(int $userId, int $limit = 5): Collection;
}
