<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Domain\Product\DTOs\CreateProductDTO;

/**
 * Create Product Request
 *
 * Handles validation for product creation.
 */
final class CreateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware/policies
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:products,slug'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_price' => ['nullable', 'integer', 'min:0', 'gte:price'],
            'cost' => ['nullable', 'integer', 'min:0'],
            'quantity' => ['integer', 'min:0'],
            'is_active' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'categories' => ['array'],
            'categories.*' => ['integer', 'exists:categories,id'],
            'attributes' => ['array'],
            'attributes.*.key' => ['required_with:attributes', 'string'],
            'attributes.*.value' => ['required_with:attributes', 'string'],
        ];
    }

    /**
     * Get custom error messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'name.max' => 'Product name cannot exceed 255 characters.',
            'slug.required' => 'Product slug is required.',
            'slug.unique' => 'This slug is already in use.',
            'sku.required' => 'Product SKU is required.',
            'sku.unique' => 'This SKU is already in use.',
            'price.required' => 'Product price is required.',
            'price.min' => 'Price cannot be negative.',
            'compare_price.gte' => 'Compare price must be greater than or equal to price.',
            'categories.*.exists' => 'One or more selected categories do not exist.',
        ];
    }

    /**
     * Get custom attribute names.
     */
    public function attributes(): array
    {
        return [
            'name' => 'product name',
            'slug' => 'URL slug',
            'sku' => 'SKU',
            'compare_price' => 'compare at price',
            'is_active' => 'active status',
            'meta_title' => 'SEO title',
            'meta_description' => 'SEO description',
        ];
    }

    /**
     * Convert request to DTO.
     */
    public function toDTO(): CreateProductDTO
    {
        return CreateProductDTO::fromRequest($this);
    }
}
