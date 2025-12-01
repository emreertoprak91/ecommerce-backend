<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;

final class CreateOrderRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],

            'shipping_address' => ['required', 'array'],
            'shipping_address.firstName' => ['required', 'string', 'max:100'],
            'shipping_address.lastName' => ['required', 'string', 'max:100'],
            'shipping_address.address' => ['required', 'string', 'max:500'],
            'shipping_address.city' => ['required', 'string', 'max:100'],
            'shipping_address.state' => ['required', 'string', 'max:100'],
            'shipping_address.zipCode' => ['required', 'string', 'max:20'],
            'shipping_address.country' => ['required', 'string', 'max:100'],
            'shipping_address.phone' => ['required', 'string', 'max:20'],

            'billing_address' => ['required', 'array'],
            'billing_address.firstName' => ['required', 'string', 'max:100'],
            'billing_address.lastName' => ['required', 'string', 'max:100'],
            'billing_address.address' => ['required', 'string', 'max:500'],
            'billing_address.city' => ['required', 'string', 'max:100'],
            'billing_address.state' => ['required', 'string', 'max:100'],
            'billing_address.zipCode' => ['required', 'string', 'max:20'],
            'billing_address.country' => ['required', 'string', 'max:100'],
            'billing_address.phone' => ['required', 'string', 'max:20'],

            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Sipariş için en az bir ürün gereklidir.',
            'items.min' => 'Sipariş için en az bir ürün gereklidir.',
            'items.*.product_id.required' => 'Ürün ID gereklidir.',
            'items.*.product_id.exists' => 'Seçilen ürün bulunamadı.',
            'items.*.quantity.required' => 'Ürün miktarı gereklidir.',
            'items.*.quantity.min' => 'Ürün miktarı en az 1 olmalıdır.',

            'shipping_address.required' => 'Teslimat adresi gereklidir.',
            'shipping_address.firstName.required' => 'Ad gereklidir.',
            'shipping_address.lastName.required' => 'Soyad gereklidir.',
            'shipping_address.address.required' => 'Adres gereklidir.',
            'shipping_address.city.required' => 'İl gereklidir.',
            'shipping_address.state.required' => 'İlçe gereklidir.',
            'shipping_address.zipCode.required' => 'Posta kodu gereklidir.',
            'shipping_address.country.required' => 'Ülke gereklidir.',
            'shipping_address.phone.required' => 'Telefon numarası gereklidir.',

            'billing_address.required' => 'Fatura adresi gereklidir.',
            'billing_address.firstName.required' => 'Ad gereklidir.',
            'billing_address.lastName.required' => 'Soyad gereklidir.',
            'billing_address.address.required' => 'Adres gereklidir.',
            'billing_address.city.required' => 'İl gereklidir.',
            'billing_address.state.required' => 'İlçe gereklidir.',
            'billing_address.zipCode.required' => 'Posta kodu gereklidir.',
            'billing_address.country.required' => 'Ülke gereklidir.',
            'billing_address.phone.required' => 'Telefon numarası gereklidir.',
        ];
    }
}
