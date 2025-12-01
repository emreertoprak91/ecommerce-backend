<?php

declare(strict_types=1);

namespace App\Domain\Order\Services;

use App\Domain\Order\DTOs\CreateOrderDTO;
use App\Domain\Order\Exceptions\EmptyCartException;
use App\Domain\Order\Exceptions\InsufficientStockException;
use App\Domain\Order\Exceptions\OrderCannotBeCancelledException;
use App\Domain\Order\Exceptions\OrderNotFoundException;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;

final class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly LoggerInterface $logger
    ) {}

    public function createOrder(CreateOrderDTO $dto): Order
    {
        if (empty($dto->items)) {
            throw new EmptyCartException();
        }

        return DB::transaction(function () use ($dto) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($dto->items as $item) {
                $product = $this->productRepository->findById($item['product_id']);

                if (!$product) {
                    continue;
                }

                $quantity = (int) ($item['quantity'] ?? 1);

                // Check stock
                if ($product->quantity < $quantity) {
                    throw new InsufficientStockException($product->id, $product->name, $quantity, $product->quantity);
                }

                $unitPrice = $product->price;
                $totalPrice = $unitPrice * $quantity;
                $subtotal += $totalPrice;

                $itemsData[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'product_description' => $product->short_description,
                    'product_image' => $product->images[0] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                ];

                // Decrease stock
                $product->decrement('quantity', $quantity);
            }

            if (empty($itemsData)) {
                throw new EmptyCartException();
            }

            // Calculate totals using config
            $taxRate = (float) config('shop.tax_rate', 0.20);
            $taxAmount = $subtotal * $taxRate;
            $freeShippingThreshold = (float) config('shop.shipping.free_threshold', 500);
            $defaultShippingCost = (float) config('shop.shipping.default_cost', 29.90);
            $shippingAmount = $subtotal >= $freeShippingThreshold ? 0 : $defaultShippingCost;
            $totalAmount = $subtotal + $taxAmount + $shippingAmount;

            // Generate order number
            $orderPrefix = config('shop.order.prefix', 'ORD');
            $orderNumberLength = (int) config('shop.order.number_length', 8);
            $orderNumber = $orderPrefix . '-' . strtoupper(Str::random($orderNumberLength)) . '-' . time();

            // Create order
            $order = $this->orderRepository->create([
                'user_id' => $dto->userId,
                'order_number' => $orderNumber,
                'status' => Order::STATUS_PENDING,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'shipping_amount' => $shippingAmount,
                'discount_amount' => 0,
                'total_amount' => $totalAmount,
                'currency' => $dto->currency,
                'shipping_name' => $dto->shippingName,
                'shipping_phone' => $dto->shippingPhone,
                'shipping_address' => $dto->shippingAddress,
                'shipping_city' => $dto->shippingCity,
                'shipping_district' => $dto->shippingDistrict,
                'shipping_postal_code' => $dto->shippingPostalCode,
                'shipping_country' => $dto->shippingCountry,
                'billing_name' => $dto->billingName ?? $dto->shippingName,
                'billing_phone' => $dto->billingPhone ?? $dto->shippingPhone,
                'billing_address' => $dto->billingAddress ?? $dto->shippingAddress,
                'billing_city' => $dto->billingCity ?? $dto->shippingCity,
                'billing_district' => $dto->billingDistrict ?? $dto->shippingDistrict,
                'billing_postal_code' => $dto->billingPostalCode ?? $dto->shippingPostalCode,
                'billing_country' => $dto->billingCountry ?? $dto->shippingCountry,
                'notes' => $dto->notes,
                'payment_status' => 'pending',
            ]);

            // Create order items
            foreach ($itemsData as $itemData) {
                $this->orderRepository->createOrderItem(array_merge($itemData, ['order_id' => $order->id]));
            }

            $this->logger->info('[OrderService::createOrder] Order created', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $dto->userId,
                'total_amount' => $totalAmount,
            ]);

            return $order->load('items');
        });
    }

    public function findById(int $id): Order
    {
        $order = $this->orderRepository->findById($id);

        if (!$order) {
            throw new OrderNotFoundException($id);
        }

        return $order;
    }

    public function findByOrderNumber(string $orderNumber): Order
    {
        $order = $this->orderRepository->findByOrderNumber($orderNumber);

        if (!$order) {
            throw new OrderNotFoundException($orderNumber);
        }

        return $order;
    }

    public function getUserOrders(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->orderRepository->findByUserId($userId, $perPage);
    }

    public function getRecentOrders(int $userId, int $limit = 5): Collection
    {
        return $this->orderRepository->getRecentOrders($userId, $limit);
    }

    public function updateOrderStatus(int $orderId, string $status): Order
    {
        $order = $this->findById($orderId);

        return $this->orderRepository->update($order, [
            'status' => $status,
        ]);
    }

    public function markAsPaid(int $orderId): Order
    {
        $order = $this->findById($orderId);

        return $this->orderRepository->update($order, [
            'status' => Order::STATUS_PAID,
            'payment_status' => 'completed',
            'paid_at' => now(),
        ]);
    }

    public function cancelOrder(int $orderId): Order
    {
        $order = $this->findById($orderId);

        if (!$order->canBeCancelled()) {
            throw new OrderCannotBeCancelledException($order->id, $order->order_number, $order->status);
        }

        // Restore stock
        foreach ($order->items as $item) {
            $product = $this->productRepository->findById($item->product_id);
            if ($product) {
                $product->increment('quantity', $item->quantity);
            }
        }

        $this->logger->info('[OrderService::cancelOrder] Order cancelled', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return $this->orderRepository->update($order, [
            'status' => Order::STATUS_CANCELLED,
        ]);
    }
}
