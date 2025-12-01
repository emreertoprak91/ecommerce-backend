<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Annotations as OA;

/**
 * Product Resource
 *
 * Transforms Product model to API response format.
 *
 * @OA\Schema(
 *     schema="ProductResource",
 *     type="object",
 *     title="Product Resource",
 *     description="Product resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="uuid", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="name", type="string", example="iPhone 15 Pro"),
 *     @OA\Property(property="slug", type="string", example="iphone-15-pro"),
 *     @OA\Property(property="sku", type="string", example="IPHONE-15-PRO-256"),
 *     @OA\Property(property="description", type="string", example="Latest Apple iPhone with A17 Pro chip"),
 *     @OA\Property(
 *         property="price",
 *         type="object",
 *         @OA\Property(property="amount", type="integer", example=129900, description="Price in cents"),
 *         @OA\Property(property="formatted", type="string", example="1.299,00 TL"),
 *         @OA\Property(property="currency", type="string", example="TRY")
 *     ),
 *     @OA\Property(property="quantity", type="integer", example=100),
 *     @OA\Property(property="in_stock", type="boolean", example=true),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="is_featured", type="boolean", example=false),
 *     @OA\Property(
 *         property="seo",
 *         type="object",
 *         @OA\Property(property="meta_title", type="string", example="Buy iPhone 15 Pro"),
 *         @OA\Property(property="meta_description", type="string", example="Best price for iPhone 15 Pro")
 *     ),
 *     @OA\Property(property="published_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-15T10:30:00.000000Z")
 * )
 *
 * @mixin \App\Domain\Product\Models\Product
 */
final class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'sku' => $this->sku,
            'description' => $this->description,
            'price' => [
                'amount' => $this->price,
                'formatted' => $this->formatted_price,
                'currency' => 'TRY',
            ],
            'compare_price' => $this->when($this->compare_price, [
                'amount' => $this->compare_price,
                'formatted' => $this->compare_price
                    ? number_format($this->compare_price / 100, 2, ',', '.') . ' TL'
                    : null,
            ]),
            'discount_percentage' => $this->discount_percentage,
            'quantity' => $this->quantity,
            'in_stock' => $this->in_stock,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'seo' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
            ],
            'categories' => CategoryResource::collection($this->whenLoaded('categories')),
            'published_at' => $this->published_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
