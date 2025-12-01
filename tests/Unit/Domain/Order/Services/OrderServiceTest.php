<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Order\Services;

use App\Domain\Order\DTOs\CreateOrderDTO;
use App\Domain\Order\Exceptions\EmptyCartException;
use App\Domain\Order\Exceptions\OrderNotFoundException;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Repositories\OrderRepositoryInterface;
use App\Domain\Order\Services\OrderService;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Tests\TestCase;

/**
 * OrderService Unit Tests
 *
 * İzole birim testleri - Repository mocklanır, sadece Service'in iş mantığı test edilir.
 */
final class OrderServiceTest extends TestCase
{
    private OrderService $service;
    private MockInterface&OrderRepositoryInterface $orderRepository;
    private MockInterface&ProductRepositoryInterface $productRepository;
    private MockInterface&LoggerInterface $logger;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var MockInterface&OrderRepositoryInterface $orderRepository */
        $orderRepository = Mockery::mock(OrderRepositoryInterface::class);
        $this->orderRepository = $orderRepository;

        /** @var MockInterface&ProductRepositoryInterface $productRepository */
        $productRepository = Mockery::mock(ProductRepositoryInterface::class);
        $this->productRepository = $productRepository;

        /** @var MockInterface&LoggerInterface $logger */
        $logger = Mockery::mock(LoggerInterface::class);
        $logger->shouldReceive('info')->andReturnNull();
        $logger->shouldReceive('warning')->andReturnNull();
        $logger->shouldReceive('error')->andReturnNull();
        $this->logger = $logger;

        $this->service = new OrderService(
            $this->orderRepository,
            $this->productRepository,
            $this->logger
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // ==================== GET USER ORDERS ====================

    #[Test]
    public function it_can_get_user_orders(): void
    {
        // Arrange
        $userId = 1;
        $perPage = 15;
        $paginator = new LengthAwarePaginator([], 0, $perPage);

        $this->orderRepository
            ->shouldReceive('findByUserId')
            ->once()
            ->with($userId, $perPage)
            ->andReturn($paginator);

        // Act
        $result = $this->service->getUserOrders($userId, $perPage);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    // ==================== FIND BY ID ====================

    #[Test]
    public function it_can_find_order_by_id(): void
    {
        // Arrange
        $order = new Order(['id' => 1, 'order_number' => 'ORD-001']);
        $order->id = 1;

        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($order);

        // Act
        $result = $this->service->findById(1);

        // Assert
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals('ORD-001', $result->order_number);
    }

    #[Test]
    public function it_throws_exception_when_order_not_found(): void
    {
        // Arrange
        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        // Assert
        $this->expectException(OrderNotFoundException::class);

        // Act
        $this->service->findById(999);
    }

    // ==================== FIND BY ORDER NUMBER ====================

    #[Test]
    public function it_can_find_order_by_order_number(): void
    {
        // Arrange
        $order = new Order(['id' => 1, 'order_number' => 'ORD-ABC123']);
        $order->id = 1;

        $this->orderRepository
            ->shouldReceive('findByOrderNumber')
            ->once()
            ->with('ORD-ABC123')
            ->andReturn($order);

        // Act
        $result = $this->service->findByOrderNumber('ORD-ABC123');

        // Assert
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals('ORD-ABC123', $result->order_number);
    }

    #[Test]
    public function it_throws_exception_when_order_not_found_by_order_number(): void
    {
        // Arrange
        $this->orderRepository
            ->shouldReceive('findByOrderNumber')
            ->once()
            ->with('INVALID')
            ->andReturn(null);

        // Assert
        $this->expectException(OrderNotFoundException::class);

        // Act
        $this->service->findByOrderNumber('INVALID');
    }

    // ==================== CREATE ORDER ====================

    #[Test]
    public function it_throws_exception_when_creating_order_with_empty_items(): void
    {
        // Arrange
        $dto = new CreateOrderDTO(
            userId: 1,
            items: [],
            shippingName: 'John Doe',
            shippingPhone: '5551234567',
            shippingAddress: '123 Main St',
            shippingCity: 'Istanbul',
        );

        // Assert
        $this->expectException(EmptyCartException::class);

        // Act
        $this->service->createOrder($dto);
    }

    // ==================== UPDATE ORDER STATUS ====================

    #[Test]
    public function it_can_update_order_status(): void
    {
        // Arrange
        $order = new Order(['id' => 1, 'status' => 'pending']);
        $order->id = 1;

        $updatedOrder = new Order(['id' => 1, 'status' => 'processing']);
        $updatedOrder->id = 1;

        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($order);

        $this->orderRepository
            ->shouldReceive('update')
            ->once()
            ->with($order, ['status' => 'processing'])
            ->andReturn($updatedOrder);

        // Act
        $result = $this->service->updateOrderStatus(1, 'processing');

        // Assert
        $this->assertEquals('processing', $result->status);
    }

    // ==================== MARK AS PAID ====================

    #[Test]
    public function it_can_mark_order_as_paid(): void
    {
        // Arrange
        $order = new Order(['id' => 1, 'status' => 'pending']);
        $order->id = 1;

        $paidOrder = new Order(['id' => 1, 'status' => 'paid', 'payment_status' => 'completed']);
        $paidOrder->id = 1;

        $this->orderRepository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($order);

        $this->orderRepository
            ->shouldReceive('update')
            ->once()
            ->andReturn($paidOrder);

        // Act
        $result = $this->service->markAsPaid(1);

        // Assert
        $this->assertEquals('paid', $result->status);
    }

    // ==================== GET RECENT ORDERS ====================

    #[Test]
    public function it_can_get_recent_orders(): void
    {
        // Arrange
        $userId = 1;
        $limit = 5;
        $orders = new \Illuminate\Database\Eloquent\Collection([
            new Order(['id' => 1]),
            new Order(['id' => 2]),
        ]);

        $this->orderRepository
            ->shouldReceive('getRecentOrders')
            ->once()
            ->with($userId, $limit)
            ->andReturn($orders);

        // Act
        $result = $this->service->getRecentOrders($userId, $limit);

        // Assert
        $this->assertCount(2, $result);
    }
}
