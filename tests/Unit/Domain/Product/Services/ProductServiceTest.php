<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Product\Services;

use App\Domain\Product\DTOs\CreateProductDTO;
use App\Domain\Product\DTOs\ListProductsDTO;
use App\Domain\Product\DTOs\UpdateProductDTO;
use App\Domain\Product\Events\ProductCreatedEvent;
use App\Domain\Product\Events\ProductDeletedEvent;
use App\Domain\Product\Events\ProductUpdatedEvent;
use App\Domain\Product\Exceptions\DuplicateSkuException;
use App\Domain\Product\Exceptions\ProductNotFoundException;
use App\Domain\Product\Models\Product;
use App\Domain\Product\Repositories\ProductRepositoryInterface;
use App\Domain\Product\Services\ProductService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Event;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Tests\TestCase;

/**
 * ProductService Unit Tests
 */
final class ProductServiceTest extends TestCase
{
    private ProductService $service;
    private MockInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(ProductRepositoryInterface::class);
        $this->service = new ProductService(
            $this->repository,
            new NullLogger()
        );

        Event::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[Test]
    public function it_can_list_products(): void
    {
        // Arrange
        $filters = new ListProductsDTO(perPage: 15);
        $paginator = new LengthAwarePaginator([], 0, 15);

        $this->repository
            ->shouldReceive('paginate')
            ->once()
            ->with($filters)
            ->andReturn($paginator);

        // Act
        $result = $this->service->listProducts($filters);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    #[Test]
    public function it_can_get_a_product_by_id(): void
    {
        // Arrange
        $product = new Product(['id' => 1, 'name' => 'Test']);
        $product->id = 1;

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($product);

        // Act
        $result = $this->service->getProduct(1);

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals(1, $result->id);
    }

    #[Test]
    public function it_throws_exception_when_product_not_found(): void
    {
        // Arrange
        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(999)
            ->andReturn(null);

        // Assert & Act
        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage("Product with identifier '999' not found.");

        $this->service->getProduct(999);
    }

    #[Test]
    public function it_can_create_a_product(): void
    {
        // Arrange
        $dto = new CreateProductDTO(
            name: 'Test Product',
            slug: 'test-product',
            sku: 'TEST-001',
            price: 9999
        );

        $product = Mockery::mock(Product::class)->makePartial();
        $product->id = 1;
        $product->uuid = 'test-uuid';
        $product->name = 'Test Product';
        $product->slug = 'test-product';
        $product->sku = 'TEST-001';
        $product->price = 9999;

        // Mock the load method to return itself
        $product->shouldReceive('load')
            ->with('categories')
            ->andReturnSelf();

        $this->repository
            ->shouldReceive('skuExists')
            ->once()
            ->with('TEST-001')
            ->andReturn(false);

        $this->repository
            ->shouldReceive('save')
            ->once()
            ->andReturn($product);

        // Act
        $result = $this->service->createProduct($dto);

        // Assert
        $this->assertEquals(1, $result->id);
        Event::assertDispatched(ProductCreatedEvent::class);
    }

    #[Test]
    public function it_throws_exception_when_sku_already_exists(): void
    {
        // Arrange
        $dto = new CreateProductDTO(
            name: 'Test Product',
            slug: 'test-product',
            sku: 'EXISTING-SKU',
            price: 9999
        );

        $this->repository
            ->shouldReceive('skuExists')
            ->once()
            ->with('EXISTING-SKU')
            ->andReturn(true);

        // Assert & Act
        $this->expectException(DuplicateSkuException::class);
        $this->expectExceptionMessage("Product with SKU 'EXISTING-SKU' already exists.");

        $this->service->createProduct($dto);
    }

    #[Test]
    public function it_can_update_a_product(): void
    {
        // Arrange
        $product = Mockery::mock(Product::class)->makePartial();
        $product->shouldReceive('getAttribute')
            ->with('id')
            ->andReturn(1);
        $product->shouldReceive('getAttribute')
            ->with('uuid')
            ->andReturn('test-uuid');
        $product->shouldReceive('getAttribute')
            ->with('sku')
            ->andReturn('OLD-SKU');

        // Mock methods
        $product->shouldReceive('getAttributes')
            ->andReturn([
                'id' => 1,
                'name' => 'Old Name',
                'slug' => 'old-slug',
                'sku' => 'OLD-SKU',
                'price' => 5000,
            ]);

        $product->shouldReceive('fill')
            ->andReturnSelf();

        $product->shouldReceive('getDirty')
            ->andReturn(['name' => 'New Name', 'price' => 7500]);

        $product->shouldReceive('load')
            ->with('categories')
            ->andReturnSelf();

        $dto = new UpdateProductDTO(name: 'New Name', price: 7500);

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($product);

        $this->repository
            ->shouldReceive('save')
            ->once()
            ->andReturn($product);

        // Act
        $result = $this->service->updateProduct(1, $dto);

        // Assert
        $this->assertEquals(1, $result->id);
        Event::assertDispatched(ProductUpdatedEvent::class);
    }

    #[Test]
    public function it_throws_exception_when_updating_to_existing_sku(): void
    {
        // Arrange
        $product = new Product([
            'name' => 'Test',
            'slug' => 'test',
            'sku' => 'CURRENT-SKU',
            'price' => 5000,
        ]);
        $product->id = 1;

        $dto = new UpdateProductDTO(sku: 'EXISTING-SKU');

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($product);

        $this->repository
            ->shouldReceive('skuExists')
            ->once()
            ->with('EXISTING-SKU', 1)
            ->andReturn(true);

        // Assert & Act
        $this->expectException(DuplicateSkuException::class);

        $this->service->updateProduct(1, $dto);
    }

    #[Test]
    public function it_can_delete_a_product(): void
    {
        // Arrange
        $product = new Product([
            'name' => 'Test',
            'slug' => 'test',
            'sku' => 'TEST-001',
            'price' => 5000,
        ]);
        $product->id = 1;
        $product->uuid = 'test-uuid';

        $this->repository
            ->shouldReceive('findById')
            ->once()
            ->with(1)
            ->andReturn($product);

        $this->repository
            ->shouldReceive('delete')
            ->once()
            ->with($product)
            ->andReturn(true);

        // Act
        $result = $this->service->deleteProduct(1);

        // Assert
        $this->assertTrue($result);
        Event::assertDispatched(ProductDeletedEvent::class);
    }

    #[Test]
    public function it_can_get_product_by_slug(): void
    {
        // Arrange
        $product = new Product([
            'name' => 'Test',
            'slug' => 'test-slug',
            'sku' => 'TEST-001',
            'price' => 5000,
        ]);
        $product->id = 1;

        $this->repository
            ->shouldReceive('findBySlug')
            ->once()
            ->with('test-slug')
            ->andReturn($product);

        // Act
        $result = $this->service->getProductBySlug('test-slug');

        // Assert
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals('test-slug', $result->slug);
    }
}
