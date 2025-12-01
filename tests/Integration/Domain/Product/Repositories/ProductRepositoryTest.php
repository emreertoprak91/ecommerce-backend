<?php

declare(strict_types=1);

namespace Tests\Integration\Domain\Product\Repositories;

use App\Domain\Product\DTOs\ListProductsDTO;
use App\Domain\Product\Models\Category;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\EloquentProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * EloquentProductRepository Integration Tests
 */
final class ProductRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private EloquentProductRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new EloquentProductRepository(new Product());
    }

    #[Test]
    public function it_can_find_product_by_id(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $found = $this->repository->findById($product->id);

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals($product->id, $found->id);
        $this->assertEquals($product->sku, $found->sku);
    }

    #[Test]
    public function it_returns_null_when_product_not_found(): void
    {
        // Act
        $found = $this->repository->findById(999);

        // Assert
        $this->assertNull($found);
    }

    #[Test]
    public function it_can_find_product_by_uuid(): void
    {
        // Arrange
        $product = Product::factory()->create();

        // Act
        $found = $this->repository->findByUuid($product->uuid);

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals($product->uuid, $found->uuid);
    }

    #[Test]
    public function it_can_find_product_by_slug(): void
    {
        // Arrange
        $product = Product::factory()->create(['slug' => 'unique-slug']);

        // Act
        $found = $this->repository->findBySlug('unique-slug');

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals('unique-slug', $found->slug);
    }

    #[Test]
    public function it_can_find_product_by_sku(): void
    {
        // Arrange
        $product = Product::factory()->create(['sku' => 'UNIQUE-SKU']);

        // Act
        $found = $this->repository->findBySku('UNIQUE-SKU');

        // Assert
        $this->assertNotNull($found);
        $this->assertEquals('UNIQUE-SKU', $found->sku);
    }

    #[Test]
    public function it_can_paginate_products(): void
    {
        // Arrange
        Product::factory()->count(25)->create();
        $filters = new ListProductsDTO(perPage: 10);

        // Act
        $paginator = $this->repository->paginate($filters);

        // Assert
        $this->assertEquals(10, $paginator->perPage());
        $this->assertEquals(25, $paginator->total());
        $this->assertEquals(3, $paginator->lastPage());
    }

    #[Test]
    public function it_can_filter_products_by_active_status(): void
    {
        // Arrange
        Product::factory()->count(5)->create(['is_active' => true]);
        Product::factory()->count(3)->create(['is_active' => false]);

        $filters = new ListProductsDTO(isActive: true);

        // Act
        $paginator = $this->repository->paginate($filters);

        // Assert
        $this->assertEquals(5, $paginator->total());
    }

    #[Test]
    public function it_can_search_products(): void
    {
        // Arrange
        Product::factory()->create(['name' => 'iPhone 15 Pro']);
        Product::factory()->create(['name' => 'Samsung Galaxy']);
        Product::factory()->create(['name' => 'Google Pixel']);

        $filters = new ListProductsDTO(search: 'iPhone');

        // Act
        $paginator = $this->repository->paginate($filters);

        // Assert
        $this->assertEquals(1, $paginator->total());
        $this->assertEquals('iPhone 15 Pro', $paginator->items()[0]->name);
    }

    #[Test]
    public function it_can_filter_products_by_price_range(): void
    {
        // Arrange
        Product::factory()->create(['price' => 5000]);
        Product::factory()->create(['price' => 10000]);
        Product::factory()->create(['price' => 15000]);
        Product::factory()->create(['price' => 20000]);

        $filters = new ListProductsDTO(minPrice: 8000, maxPrice: 18000);

        // Act
        $paginator = $this->repository->paginate($filters);

        // Assert
        $this->assertEquals(2, $paginator->total());
    }

    #[Test]
    public function it_can_save_a_new_product(): void
    {
        // Arrange
        $product = new Product([
            'name' => 'New Product',
            'slug' => 'new-product',
            'sku' => 'NEW-001',
            'price' => 9999,
            'quantity' => 10,
            'is_active' => true,
        ]);

        // Act
        $saved = $this->repository->save($product);

        // Assert
        $this->assertNotNull($saved->id);
        $this->assertDatabaseHas('products', [
            'sku' => 'NEW-001',
            'name' => 'New Product',
        ]);
    }

    #[Test]
    public function it_can_update_an_existing_product(): void
    {
        // Arrange
        $product = Product::factory()->create(['name' => 'Original Name']);
        $product->name = 'Updated Name';

        // Act
        $updated = $this->repository->save($product);

        // Assert
        $this->assertEquals('Updated Name', $updated->name);
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Updated Name',
        ]);
    }

    #[Test]
    public function it_can_delete_a_product(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $productId = $product->id;

        // Act
        $result = $this->repository->delete($product);

        // Assert
        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $productId]);
    }

    #[Test]
    public function it_can_check_if_sku_exists(): void
    {
        // Arrange
        Product::factory()->create(['sku' => 'EXISTING-SKU']);

        // Act & Assert
        $this->assertTrue($this->repository->skuExists('EXISTING-SKU'));
        $this->assertFalse($this->repository->skuExists('NON-EXISTING-SKU'));
    }

    #[Test]
    public function it_can_exclude_product_when_checking_sku(): void
    {
        // Arrange
        $product = Product::factory()->create(['sku' => 'MY-SKU']);

        // Act & Assert
        $this->assertTrue($this->repository->skuExists('MY-SKU'));
        $this->assertFalse($this->repository->skuExists('MY-SKU', $product->id));
    }

    #[Test]
    public function it_can_sync_categories(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $categories = Category::factory()->count(3)->create();
        $categoryIds = $categories->pluck('id')->toArray();

        // Act
        $this->repository->syncCategories($product, $categoryIds);

        // Assert
        $this->assertCount(3, $product->fresh()->categories);
    }

    #[Test]
    public function it_can_sort_products(): void
    {
        // Arrange
        Product::factory()->create(['name' => 'B Product', 'price' => 200]);
        Product::factory()->create(['name' => 'A Product', 'price' => 100]);
        Product::factory()->create(['name' => 'C Product', 'price' => 300]);

        $filters = new ListProductsDTO(sortBy: 'name', sortOrder: 'asc');

        // Act
        $paginator = $this->repository->paginate($filters);
        $items = $paginator->items();

        // Assert
        $this->assertEquals('A Product', $items[0]->name);
        $this->assertEquals('B Product', $items[1]->name);
        $this->assertEquals('C Product', $items[2]->name);
    }
}
