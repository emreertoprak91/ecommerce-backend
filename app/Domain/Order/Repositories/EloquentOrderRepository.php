<?php

declare(strict_types=1);

namespace App\Domain\Order\Repositories;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

final class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function findById(int $id): ?Order
    {
        return Order::with(['items.product', 'user', 'payment'])->find($id);
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return Order::with(['items.product', 'user', 'payment'])
            ->where('order_number', $orderNumber)
            ->first();
    }

    public function findByUserId(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Order::with(['items.product'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function createOrderItem(array $data): OrderItem
    {
        return OrderItem::create($data);
    }

    public function update(Order $order, array $data): Order
    {
        $order->update($data);
        return $order->fresh();
    }

    public function delete(Order $order): bool
    {
        return $order->delete();
    }

    public function getUserOrders(int $userId): Collection
    {
        return Order::with(['items.product'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getRecentOrders(int $userId, int $limit = 5): Collection
    {
        return Order::with(['items.product'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
