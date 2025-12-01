<?php

declare(strict_types=1);

namespace Tests\Integration\Domain\Order\Repositories;

use App\Domain\Order\Enums\OrderStatus;
use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use App\Domain\Order\Repositories\EloquentOrderRepository;
use App\Domain\Product\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * EloquentOrderRepository Integration Tests
 *
 * Gerçek veritabanı işlemlerini test eder. Order repository metodlarının
 * doğru şekilde çalıştığını doğrular.
 */
final class OrderRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentOrderRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentOrderRepository();
    }

    // ==================== FIND BY USER ID ====================

    #[Test]
    public function it_can_get_user_orders_with_pagination(): void
    {
        // Arrange
        $user = User::factory()->create();

        Order::factory()->count(5)->create([
            'user_id' => $user->id,
        ]);

        // Act
        $result = $this->repository->findByUserId($user->id, 3);

        // Assert
        $this->assertEquals(5, $result->total());
        $this->assertEquals(3, $result->perPage());
        $this->assertCount(3, $result->items());
    }

    #[Test]
    public function it_orders_results_by_created_at_descending(): void
    {
        // Arrange
        $user = User::factory()->create();

        $order1 = Order::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(2),
        ]);

        $order2 = Order::factory()->create([
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $order3 = Order::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDay(),
        ]);

        // Act
        $result = $this->repository->findByUserId($user->id, 15);

        // Assert
        $orders = $result->items();
        $this->assertEquals($order2->id, $orders[0]->id);
        $this->assertEquals($order3->id, $orders[1]->id);
        $this->assertEquals($order1->id, $orders[2]->id);
    }

    #[Test]
    public function it_only_returns_current_user_orders(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Order::factory()->count(3)->create(['user_id' => $user1->id]);
        Order::factory()->count(2)->create(['user_id' => $user2->id]);

        // Act
        $result = $this->repository->findByUserId($user1->id, 15);

        // Assert
        $this->assertEquals(3, $result->total());
    }

    // ==================== FIND BY ID ====================

    #[Test]
    public function it_can_find_order_by_id(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Act
        $result = $this->repository->findById($order->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($order->id, $result->id);
    }

    #[Test]
    public function it_returns_null_when_order_not_found(): void
    {
        // Act
        $result = $this->repository->findById(99999);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function it_loads_items_relation_with_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        // Act
        $result = $this->repository->findById($order->id);

        // Assert
        $this->assertNotNull($result);
        $this->assertTrue($result->relationLoaded('items'));
        $this->assertCount(1, $result->items);
    }

    // ==================== FIND BY ORDER NUMBER ====================

    #[Test]
    public function it_can_find_order_by_order_number(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-12345',
        ]);

        // Act
        $result = $this->repository->findByOrderNumber('ORD-TEST-12345');

        // Assert
        $this->assertNotNull($result);
        $this->assertEquals($order->id, $result->id);
    }

    #[Test]
    public function it_returns_null_when_order_number_not_found(): void
    {
        // Act
        $result = $this->repository->findByOrderNumber('NON-EXISTENT');

        // Assert
        $this->assertNull($result);
    }

    // ==================== CREATE ====================

    #[Test]
    public function it_can_create_order(): void
    {
        // Arrange
        $user = User::factory()->create();

        // Act
        $result = $this->repository->create([
            'user_id' => $user->id,
            'order_number' => 'ORD-TEST-123',
            'status' => OrderStatus::PENDING->value,
            'subtotal' => 10000,
            'tax_amount' => 1800,
            'shipping_amount' => 500,
            'discount_amount' => 0,
            'total_amount' => 12300,
            'shipping_name' => 'Test User',
            'shipping_address' => 'Test Address',
            'shipping_city' => 'Istanbul',
        ]);

        // Assert
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals('ORD-TEST-123', $result->order_number);
        $this->assertEquals(12300, $result->total_amount);

        $this->assertDatabaseHas('orders', [
            'order_number' => 'ORD-TEST-123',
            'user_id' => $user->id,
        ]);
    }

    // ==================== UPDATE ====================

    #[Test]
    public function it_can_update_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => OrderStatus::PENDING,
        ]);

        // Act
        $result = $this->repository->update($order, [
            'status' => OrderStatus::PROCESSING->value,
        ]);

        // Assert
        $this->assertInstanceOf(Order::class, $result);
        $this->assertEquals(OrderStatus::PROCESSING->value, $result->status);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PROCESSING->value,
        ]);
    }

    // ==================== DELETE ====================

    #[Test]
    public function it_can_delete_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        // Act
        $result = $this->repository->delete($order);

        // Assert
        $this->assertTrue($result);

        // Soft delete check
        $this->assertSoftDeleted('orders', ['id' => $order->id]);
    }

    // ==================== GET USER ORDERS ====================

    #[Test]
    public function it_can_get_all_user_orders(): void
    {
        // Arrange
        $user = User::factory()->create();
        Order::factory()->count(5)->create(['user_id' => $user->id]);

        // Act
        $result = $this->repository->getUserOrders($user->id);

        // Assert
        $this->assertCount(5, $result);
    }

    // ==================== GET RECENT ORDERS ====================

    #[Test]
    public function it_can_get_recent_orders_with_limit(): void
    {
        // Arrange
        $user = User::factory()->create();
        Order::factory()->count(10)->create(['user_id' => $user->id]);

        // Act
        $result = $this->repository->getRecentOrders($user->id, 3);

        // Assert
        $this->assertCount(3, $result);
    }

    // ==================== ORDER ITEMS ====================

    #[Test]
    public function it_can_calculate_order_total_from_items(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'subtotal' => 0,
            'total_amount' => 0,
        ]);

        $product1 = Product::factory()->create(['price' => 1000]);
        $product2 = Product::factory()->create(['price' => 2500]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 2,
            'unit_price' => 1000,
            'total_price' => 2000,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'unit_price' => 2500,
            'total_price' => 2500,
        ]);

        // Act
        $result = $this->repository->findById($order->id);
        $calculatedTotal = $result->items->sum('total_price');

        // Assert
        $this->assertEquals(4500, $calculatedTotal);
    }

    #[Test]
    public function it_loads_items_with_product_relation(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['name' => 'Test Product']);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        // Act
        $result = $this->repository->findById($order->id);

        // Assert
        $this->assertTrue($result->items->first()->relationLoaded('product'));
        $this->assertEquals('Test Product', $result->items->first()->product->name);
    }
}
