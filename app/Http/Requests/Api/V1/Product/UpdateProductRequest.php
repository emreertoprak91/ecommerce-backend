<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Domain\Product\DTOs\UpdateProductDTO;

/**
 * Update Product Request
 *
 * Handles validation for product updates.
 */
final class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products', 'slug')->ignore($productId),
            ],
            'sku' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('products', 'sku')->ignore($productId),
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['sometimes', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'integer', 'min:0'],
            'cost' => ['nullable', 'integer', 'min:0'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'categories' => ['sometimes', 'array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'attributes' => ['sometimes', 'array'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'slug.unique' => 'This slug is already in use by another product.',
            'sku.unique' => 'This SKU is already in use by another product.',
            'price.min' => 'Price cannot be negative.',
        ];
    }

    /**
     * Convert request to DTO.
     */
    public function toDTO(): UpdateProductDTO
    {
        return UpdateProductDTO::fromRequest($this);
    }
}
