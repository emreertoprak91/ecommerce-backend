<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1;

use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Product API Feature Tests
 *
 * End-to-end tests for the Product API endpoints.
 */
final class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // ==================== LIST PRODUCTS ====================

    #[Test]
    public function it_can_list_products(): void
    {
        // Arrange
        Product::factory()->count(5)->create();

        // Act
        $response = $this->getJson('/api/v1/products');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'uuid', 'name', 'slug', 'sku', 'price'],
                ],
                'meta' => ['timestamp', 'trace_id', 'pagination'],
                'links',
            ])
            ->assertJson(['success' => true]);
    }

    #[Test]
    public function it_can_paginate_products(): void
    {
        // Arrange
        Product::factory()->count(25)->create();

        // Act
        $response = $this->getJson('/api/v1/products?per_page=10');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('meta.pagination.per_page', 10)
            ->assertJsonPath('meta.pagination.total', 25);
    }

    #[Test]
    public function it_can_search_products(): void
    {
        // Arrange
        Product::factory()->create(['name' => 'iPhone 15 Pro Max']);
        Product::factory()->create(['name' => 'Samsung Galaxy S24']);

        // Act
        $response = $this->getJson('/api/v1/products?search=iPhone');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('meta.pagination.total', 1)
            ->assertJsonPath('data.0.name', 'iPhone 15 Pro Max');
    }

    #[Test]
    public function it_can_filter_products_by_price(): void
    {
        // Arrange
        Product::factory()->create(['price' => 5000]);
        Product::factory()->create(['price' => 15000]);
        Product::factory()->create(['price' => 25000]);

        // Act
        $response = $this->getJson('/api/v1/products?min_price=10000&max_price=20000');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('meta.pagination.total', 1);
    }

    // ==================== GET SINGLE PRODUCT ====================

    #[Test]
    public function it_can_get_a_single_product(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->getJson("/api/v1/products/{$product->id}");

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'uuid',
                    'name',
                    'slug',
                    'sku',
                    'price' => ['amount', 'formatted', 'currency'],
                    'quantity',
                    'in_stock',
                    'is_active',
                ],
            ])
            ->assertJsonPath('data.id', $product->id);
    }

    #[Test]
    public function it_returns_404_for_non_existent_product(): void
    {
        // Act
        $response = $this->getJson('/api/v1/products/999');

        // Assert
        $response
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    }

    #[Test]
    public function it_can_get_product_by_slug(): void
    {
        // Arrange
        $product = Product::factory()->create(['slug' => 'test-product-slug']);

        // Act
        $response = $this->getJson('/api/v1/products/slug/test-product-slug');

        // Assert
        $response
            ->assertStatus(200)
            ->assertJsonPath('data.slug', 'test-product-slug');
    }

    // ==================== CREATE PRODUCT ====================

    #[Test]
    public function authenticated_user_can_create_product(): void
    {
        // Arrange
        $payload = [
            'name' => 'New Product',
            'slug' => 'new-product',
            'sku' => 'NEW-001',
            'price' => 9999,
            'description' => 'A great new product',
            'quantity' => 50,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/products', $payload);

        // Assert
        $response
            ->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'Product created successfully',
            ])
            ->assertJsonPath('data.name', 'New Product')
            ->assertJsonPath('data.sku', 'NEW-001');

        $this->assertDatabaseHas('products', [
            'sku' => 'NEW-001',
            'name' => 'New Product',
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_create_product(): void
    {
        // Arrange
        $payload = [
            'name' => 'New Product',
            'slug' => 'new-product',
            'sku' => 'NEW-001',
            'price' => 9999,
        ];

        // Act
        $response = $this->postJson('/api/v1/products', $payload);

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_validates_required_fields_when_creating(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/products', []);

        // Assert
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'slug', 'sku', 'price']);
    }

    #[Test]
    public function it_prevents_duplicate_sku(): void
    {
        // Arrange
        Product::factory()->create(['sku' => 'EXISTING-SKU']);

        $payload = [
            'name' => 'New Product',
            'slug' => 'new-product',
            'sku' => 'EXISTING-SKU',
            'price' => 9999,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/products', $payload);

        // Assert
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    #[Test]
    public function it_can_create_product_with_categories(): void
    {
        // Arrange
        $categories = Category::factory()->count(2)->create();
        $categoryIds = $categories->pluck('id')->toArray();

        $payload = [
            'name' => 'New Product',
            'slug' => 'new-product',
            'sku' => 'NEW-001',
            'price' => 9999,
            'categories' => $categoryIds,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/products', $payload);

        // Assert
        $response->assertStatus(201);

        $product = Product::where('sku', 'NEW-001')->first();
        $this->assertCount(2, $product->categories);
    }

    // ==================== UPDATE PRODUCT ====================

    #[Test]
    public function authenticated_user_can_update_product(): void
    {
        // Arrange
        $product = Product::factory()->create(['name' => 'Old Name']);

        $payload = [
            'name' => 'Updated Name',
            'price' => 12999,
        ];

        // Act
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/products/{$product->id}", $payload);

        // Assert
        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product updated successfully',
            ])
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
            'price' => 12999,
        ]);
    }

    #[Test]
    public function unauthenticated_user_cannot_update_product(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->putJson("/api/v1/products/{$product->id}", [
            'name' => 'Updated Name',
        ]);

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_prevents_updating_to_existing_sku(): void
    {
        // Arrange
        Product::factory()->create(['sku' => 'EXISTING-SKU']);
        $product = Product::factory()->create(['sku' => 'MY-SKU']);

        // Act
        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/products/{$product->id}", [
                'sku' => 'EXISTING-SKU',
            ]);

        // Assert
        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sku']);
    }

    // ==================== DELETE PRODUCT ====================

    #[Test]
    public function authenticated_user_can_delete_product(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/products/{$product->id}");

        // Assert
        $response
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);

        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    #[Test]
    public function unauthenticated_user_cannot_delete_product(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        // Assert
        $response->assertStatus(401);
    }

    #[Test]
    public function it_returns_404_when_deleting_non_existent_product(): void
    {
        // Act
        $response = $this->actingAs($this->user)
            ->deleteJson('/api/v1/products/999');

        // Assert
        $response->assertStatus(404);
    }
}
