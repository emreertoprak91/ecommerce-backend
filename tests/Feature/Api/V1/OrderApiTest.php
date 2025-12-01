<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Order\Models\Order;
use App\Domain\Order\Models\OrderItem;
use App\Domain\Product\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Order API Feature Tests
 *
 * End-to-end tests for the Order API endpoints.
 */
final class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    // ==================== LIST ORDERS ====================

    #[Test]
    public function it_requires_authentication_to_list_orders(): void
    {
        // Act
        $response = $this->getJson('/api/v1/orders');

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_can_list_user_orders(): void
    {
        // Arrange
        Order::factory()
            ->for($this->user)
            ->count(3)
            ->create();

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/orders');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => [
                        'id',
                        'order_number',
                        'status',
                        'total_amount',
                        'formatted_total',
                        'items_count',
                        'created_at',
                    ],
                ],
            ])
            ->assertJsonCount(3, 'data')
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function it_returns_empty_array_when_no_orders_exist(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/orders');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function it_only_returns_current_user_orders(): void
    {
        // Arrange
        $otherUser = User::factory()->create();

        Order::factory()
            ->for($this->user)
            ->create(['order_number' => 'ORD-USER-001']);

        Order::factory()
            ->for($otherUser)
            ->create(['order_number' => 'ORD-OTHER-001']);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/orders');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.order_number', 'ORD-USER-001');
    }

    #[Test]
    public function it_can_paginate_orders(): void
    {
        // Arrange
        Order::factory()
            ->for($this->user)
            ->count(25)
            ->create();

        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/orders?per_page=10');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('meta.pagination.per_page', 10)
            ->assertJsonPath('meta.pagination.total', 25);
    }

    // ==================== SHOW ORDER ====================

    #[Test]
    public function it_can_show_single_order(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 10000]);
        $order = Order::factory()
            ->for($this->user)
            ->create();

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 10000,
            'total_price' => 20000,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/orders/{$order->id}");

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'status_label',
                    'subtotal',
                    'tax_amount',
                    'shipping_amount',
                    'total_amount',
                    'formatted_total',
                    'shipping',
                    'billing',
                    'items' => [
                        '*' => [
                            'id',
                            'product_name',
                            'quantity',
                            'unit_price',
                            'total_price',
                        ],
                    ],
                    'created_at',
                ],
            ]);
    }

    #[Test]
    public function it_returns_404_when_order_not_found(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/orders/999999');

        // Assert
        $response->assertStatus(404);
    }

    #[Test]
    public function it_cannot_view_other_users_order(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $order = Order::factory()
            ->for($otherUser)
            ->create();

        // Act
        $response = $this->actingAs($this->user)
            ->getJson("/api/v1/orders/{$order->id}");

        // Assert
        $response->assertStatus(403);
    }

    // ==================== CREATE ORDER ====================

    #[Test]
    public function it_can_create_order(): void
    {
        // Arrange
        $product1 = Product::factory()->create(['price' => 10000, 'quantity' => 100]);
        $product2 = Product::factory()->create(['price' => 20000, 'quantity' => 100]);

        $orderData = [
            'items' => [
                ['product_id' => $product1->id, 'quantity' => 2],
                ['product_id' => $product2->id, 'quantity' => 1],
            ],
            'shipping_address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'address' => '123 Main St',
                'city' => 'Istanbul',
                'state' => 'Kadıköy',
                'zipCode' => '34000',
                'country' => 'Türkiye',
                'phone' => '5551234567',
            ],
            'billing_address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'address' => '123 Main St',
                'city' => 'Istanbul',
                'state' => 'Kadıköy',
                'zipCode' => '34000',
                'country' => 'Türkiye',
                'phone' => '5551234567',
            ],
            'notes' => 'Please leave at door',
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $orderData);

        // Assert
        $response
            ->assertStatus(201)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('order_items', [
            'product_id' => $product1->id,
            'quantity' => 2,
        ]);
    }

    #[Test]
    public function it_validates_required_fields_when_creating_order(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', []);

        // Assert
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['items', 'shipping_address', 'billing_address']);
    }

    #[Test]
    public function it_validates_product_exists_when_creating_order(): void
    {
        // Arrange
        $orderData = [
            'items' => [
                ['product_id' => 999999, 'quantity' => 1],
            ],
            'shipping_address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'address' => '123 Main St',
                'city' => 'Istanbul',
                'state' => 'Kadıköy',
                'zipCode' => '34000',
                'country' => 'Türkiye',
                'phone' => '5551234567',
            ],
            'billing_address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'address' => '123 Main St',
                'city' => 'Istanbul',
                'state' => 'Kadıköy',
                'zipCode' => '34000',
                'country' => 'Türkiye',
                'phone' => '5551234567',
            ],
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $orderData);

        // Assert
        $response->assertStatus(422);
    }

    #[Test]
    public function it_validates_quantity_when_creating_order(): void
    {
        // Arrange
        $product = Product::factory()->create(['quantity' => 100]);

        $orderData = [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 0],
            ],
            'shipping_address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'address' => '123 Main St',
                'city' => 'Istanbul',
                'state' => 'Kadıköy',
                'zipCode' => '34000',
                'country' => 'Türkiye',
                'phone' => '5551234567',
            ],
            'billing_address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'address' => '123 Main St',
                'city' => 'Istanbul',
                'state' => 'Kadıköy',
                'zipCode' => '34000',
                'country' => 'Türkiye',
                'phone' => '5551234567',
            ],
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $orderData);

        // Assert
        $response->assertStatus(422);
    }

    #[Test]
    public function it_decreases_product_stock_when_creating_order(): void
    {
        // Arrange
        $product = Product::factory()->create(['price' => 10000, 'quantity' => 100]);

        $orderData = [
            'items' => [
                ['product_id' => $product->id, 'quantity' => 5],
            ],
            'shipping_address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'address' => '123 Main St',
                'city' => 'Istanbul',
                'state' => 'Kadıköy',
                'zipCode' => '34000',
                'country' => 'Türkiye',
                'phone' => '5551234567',
            ],
            'billing_address' => [
                'firstName' => 'John',
                'lastName' => 'Doe',
                'address' => '123 Main St',
                'city' => 'Istanbul',
                'state' => 'Kadıköy',
                'zipCode' => '34000',
                'country' => 'Türkiye',
                'phone' => '5551234567',
            ],
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/orders', $orderData);

        // Assert
        $response->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 95,
        ]);
    }

    // ==================== CANCEL ORDER ====================

    #[Test]
    public function it_can_cancel_pending_order(): void
    {
        // Arrange
        $product = Product::factory()->create(['quantity' => 95]);
        $order = Order::factory()
            ->for($this->user)
            ->create(['status' => 'pending']);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'cancelled');

        // Stock should be restored
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'quantity' => 100,
        ]);
    }

    #[Test]
    public function it_cannot_cancel_shipped_order(): void
    {
        // Arrange
        $order = Order::factory()
            ->for($this->user)
            ->create(['status' => 'shipped']);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        // Assert
        $response->assertStatus(422);
    }

    #[Test]
    public function it_cannot_cancel_other_users_order(): void
    {
        // Arrange
        $otherUser = User::factory()->create();
        $order = Order::factory()
            ->for($otherUser)
            ->create(['status' => 'pending']);

        // Act
        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/orders/{$order->id}/cancel");

        // Assert
        $response->assertStatus(403);
    }
}
