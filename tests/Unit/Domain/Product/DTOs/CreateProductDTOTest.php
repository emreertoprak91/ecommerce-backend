<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Product\DTOs;

use App\Domain\Product\DTOs\CreateProductDTO;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * CreateProductDTO Unit Tests
 */
final class CreateProductDTOTest extends TestCase
{
    #[Test]
    public function it_can_be_created_with_required_properties(): void
    {
        $dto = new CreateProductDTO(
            name: 'Test Product',
            slug: 'test-product',
            sku: 'TEST-001',
            price: 9999
        );

        $this->assertEquals('Test Product', $dto->name);
        $this->assertEquals('test-product', $dto->slug);
        $this->assertEquals('TEST-001', $dto->sku);
        $this->assertEquals(9999, $dto->price);
        $this->assertNull($dto->description);
        $this->assertEquals(0, $dto->quantity);
        $this->assertTrue($dto->isActive);
    }

    #[Test]
    public function it_can_be_created_with_all_properties(): void
    {
        $dto = new CreateProductDTO(
            name: 'Test Product',
            slug: 'test-product',
            sku: 'TEST-001',
            price: 9999,
            description: 'A test product description',
            comparePrice: 12999,
            cost: 5000,
            quantity: 50,
            isActive: false,
            metaTitle: 'SEO Title',
            metaDescription: 'SEO Description',
            categories: [1, 2, 3],
            attributes: [['key' => 'color', 'value' => 'red']]
        );

        $this->assertEquals('Test Product', $dto->name);
        $this->assertEquals('A test product description', $dto->description);
        $this->assertEquals(12999, $dto->comparePrice);
        $this->assertEquals(5000, $dto->cost);
        $this->assertEquals(50, $dto->quantity);
        $this->assertFalse($dto->isActive);
        $this->assertEquals('SEO Title', $dto->metaTitle);
        $this->assertEquals([1, 2, 3], $dto->categories);
    }

    #[Test]
    public function it_can_be_created_from_array(): void
    {
        $data = [
            'name' => 'Test Product',
            'slug' => 'test-product',
            'sku' => 'TEST-001',
            'price' => 9999,
            'description' => 'Test description',
            'quantity' => 10,
        ];

        $dto = CreateProductDTO::fromArray($data);

        $this->assertEquals('Test Product', $dto->name);
        $this->assertEquals('test-product', $dto->slug);
        $this->assertEquals('TEST-001', $dto->sku);
        $this->assertEquals(9999, $dto->price);
        $this->assertEquals('Test description', $dto->description);
        $this->assertEquals(10, $dto->quantity);
    }

    #[Test]
    public function it_can_convert_to_array(): void
    {
        $dto = new CreateProductDTO(
            name: 'Test Product',
            slug: 'test-product',
            sku: 'TEST-001',
            price: 9999,
            description: 'Test description'
        );

        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('Test Product', $array['name']);
        $this->assertEquals('test-product', $array['slug']);
        $this->assertEquals('TEST-001', $array['sku']);
        $this->assertEquals(9999, $array['price']);
        $this->assertEquals('Test description', $array['description']);
    }

    #[Test]
    public function it_is_immutable(): void
    {
        $dto = new CreateProductDTO(
            name: 'Test Product',
            slug: 'test-product',
            sku: 'TEST-001',
            price: 9999
        );

        // Readonly properties cannot be modified after construction
        $this->assertTrue((new \ReflectionClass($dto))->isReadOnly());
    }
}
