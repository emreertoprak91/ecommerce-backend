<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Order\DTOs\CreateOrderDTO;
use App\Domain\Order\Services\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Order\CreateOrderRequest;
use App\Http\Resources\Api\V1\OrderResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    /**
     * Get user's orders
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $orders = $this->orderService->getUserOrders($request->user()->id, $perPage);

        return $this->paginatedResponse(
            $orders,
            OrderResource::class,
            'Orders retrieved successfully'
        );
    }

    /**
     * Get single order
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - OrderNotFoundException → 404
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $order = $this->orderService->findById($id);

        // Ensure user owns this order
        if ($order->user_id !== $request->user()->id) {
            return $this->forbiddenResponse('You do not have permission to view this order');
        }

        return $this->successResponse(
            new OrderResource($order),
            'Order retrieved successfully'
        );
    }

    /**
     * Create new order
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - EmptyCartException → 422
     * - InsufficientStockException → 422
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Transform to DTO format
        $shippingAddr = $validated['shipping_address'];
        $billingAddr = $validated['billing_address'];

        $dto = CreateOrderDTO::fromArray([
            'user_id' => $request->user()->id,
            'items' => $validated['items'],
            'shipping_name' => $shippingAddr['firstName'] . ' ' . $shippingAddr['lastName'],
            'shipping_phone' => $shippingAddr['phone'],
            'shipping_address' => $shippingAddr['address'],
            'shipping_city' => $shippingAddr['city'],
            'shipping_district' => $shippingAddr['state'],
            'shipping_postal_code' => $shippingAddr['zipCode'],
            'billing_name' => $billingAddr['firstName'] . ' ' . $billingAddr['lastName'],
            'billing_phone' => $billingAddr['phone'],
            'billing_address' => $billingAddr['address'],
            'billing_city' => $billingAddr['city'],
            'billing_district' => $billingAddr['state'],
            'billing_postal_code' => $billingAddr['zipCode'],
            'notes' => $validated['notes'] ?? null,
        ]);

        $order = $this->orderService->createOrder($dto);

        return $this->createdResponse(
            new OrderResource($order),
            'Order created successfully'
        );
    }

    /**
     * Cancel order
     *
     * Exception'lar Handler.php tarafından handle edilir:
     * - OrderNotFoundException → 404
     * - OrderCannotBeCancelledException → 422
     */
    public function cancel(int $id, Request $request): JsonResponse
    {
        $order = $this->orderService->findById($id);

        // Ensure user owns this order
        if ($order->user_id !== $request->user()->id) {
            return $this->forbiddenResponse('You do not have permission to cancel this order');
        }

        $order = $this->orderService->cancelOrder($id);

        return $this->successResponse(
            new OrderResource($order),
            'Order cancelled successfully'
        );
    }
}
