<?php

declare(strict_types=1);

namespace App\Domain\Product\DTOs;

/**
 * List Products Filter DTO
 *
 * Immutable data transfer object for product listing filters.
 */
final readonly class ListProductsDTO
{
    public function __construct(
        public int $perPage = 15,
        public ?string $search = null,
        public ?bool $isActive = null,
        public ?int $minPrice = null,
        public ?int $maxPrice = null,
        public ?array $categories = null,
        public string $sortBy = 'created_at',
        public string $sortOrder = 'desc',
    ) {}

    /**
     * Create DTO from request array.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            perPage: (int) ($data['per_page'] ?? 15),
            search: $data['search'] ?? null,
            isActive: isset($data['is_active']) ? filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) : null,
            minPrice: isset($data['min_price']) ? (int) $data['min_price'] : null,
            maxPrice: isset($data['max_price']) ? (int) $data['max_price'] : null,
            categories: $data['categories'] ?? null,
            sortBy: $data['sort_by'] ?? 'created_at',
            sortOrder: $data['sort_order'] ?? 'desc',
        );
    }
}
