<?php

declare(strict_types=1);

namespace App\Domain\Product\DTOs;

use App\Http\Requests\Api\V1\Product\UpdateProductRequest;

/**
 * Update Product DTO
 *
 * Immutable data transfer object for product updates.
 */
final readonly class UpdateProductDTO
{
    public function __construct(
        public ?string $name = null,
        public ?string $slug = null,
        public ?string $sku = null,
        public ?int $price = null,
        public ?string $description = null,
        public ?int $comparePrice = null,
        public ?int $cost = null,
        public ?int $quantity = null,
        public ?bool $isActive = null,
        public ?string $metaTitle = null,
        public ?string $metaDescription = null,
        public ?array $categories = null,
        public ?array $attributes = null,
    ) {}

    /**
     * Create DTO from Form Request.
     */
    public static function fromRequest(UpdateProductRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            sku: $request->validated('sku'),
            price: $request->validated('price'),
            description: $request->validated('description'),
            comparePrice: $request->validated('compare_price'),
            cost: $request->validated('cost'),
            quantity: $request->validated('quantity'),
            isActive: $request->validated('is_active'),
            metaTitle: $request->validated('meta_title'),
            metaDescription: $request->validated('meta_description'),
            categories: $request->validated('categories'),
            attributes: $request->validated('attributes'),
        );
    }

    /**
     * Create DTO from array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            slug: $data['slug'] ?? null,
            sku: $data['sku'] ?? null,
            price: isset($data['price']) ? (int) $data['price'] : null,
            description: $data['description'] ?? null,
            comparePrice: isset($data['compare_price']) ? (int) $data['compare_price'] : null,
            cost: isset($data['cost']) ? (int) $data['cost'] : null,
            quantity: isset($data['quantity']) ? (int) $data['quantity'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            metaTitle: $data['meta_title'] ?? null,
            metaDescription: $data['meta_description'] ?? null,
            categories: $data['categories'] ?? null,
            attributes: $data['attributes'] ?? null,
        );
    }

    /**
     * Convert to array, excluding null values.
     */
    public function toArray(): array
    {
        return array_filter([
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
        ], fn ($value) => $value !== null);
    }
}
