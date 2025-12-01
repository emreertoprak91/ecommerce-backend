<?php

declare(strict_types=1);

namespace App\Domain\Product\DTOs;

use App\Http\Requests\Api\V1\Product\CreateProductRequest;

/**
 * Create Product DTO
 *
 * Immutable data transfer object for product creation.
 */
final readonly class CreateProductDTO
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $sku,
        public int $price,
        public ?string $description = null,
        public ?int $comparePrice = null,
        public ?int $cost = null,
        public int $quantity = 0,
        public bool $isActive = true,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public array $categories = [],
        public array $attributes = [],
    ) {}

    /**
     * Create DTO from Form Request.
     */
    public static function fromRequest(CreateProductRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            sku: $request->validated('sku'),
            price: $request->validated('price'),
            description: $request->validated('description'),
            comparePrice: $request->validated('compare_price'),
            cost: $request->validated('cost'),
            quantity: $request->validated('quantity', 0),
            isActive: $request->validated('is_active', true),
            metaTitle: $request->validated('meta_title'),
            metaDescription: $request->validated('meta_description'),
            categories: $request->validated('categories', []),
            attributes: $request->validated('attributes', []),
        );
    }

    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            slug: $data['slug'],
            sku: $data['sku'],
            price: $data['price'],
            description: $data['description'] ?? null,
            comparePrice: $data['compare_price'] ?? null,
            cost: $data['cost'] ?? null,
            quantity: $data['quantity'] ?? 0,
            isActive: $data['is_active'] ?? true,
            metaTitle: $data['meta_title'] ?? null,
            metaDescription: $data['meta_description'] ?? null,
            categories: $data['categories'] ?? [],
            attributes: $data['attributes'] ?? [],
        );
    }

    /**
     * Convert DTO to array.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'price' => $this->price,
            'description' => $this->description,
            'compare_price' => $this->comparePrice,
            'cost' => $this->cost,
            'quantity' => $this->quantity,
            'is_active' => $this->isActive,
            'meta_title' => $this->metaTitle,
            'meta_description' => $this->metaDescription,
        ];
    }
}
