<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Domain\Order\Models\Order
 */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'shipping_amount' => (float) $this->shipping_amount,
            'discount_amount' => (float) $this->discount_amount,
            'total_amount' => (float) $this->total_amount,
            'formatted_total' => $this->formatted_total,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status,
            'paid_at' => $this->paid_at?->toISOString(),
            'shipping' => [
                'name' => $this->shipping_name,
                'phone' => $this->shipping_phone,
                'address' => $this->shipping_address,
                'city' => $this->shipping_city,
                'district' => $this->shipping_district,
                'postal_code' => $this->shipping_postal_code,
                'country' => $this->shipping_country,
            ],
            'billing' => [
                'name' => $this->billing_name,
                'phone' => $this->billing_phone,
                'address' => $this->billing_address,
                'city' => $this->billing_city,
                'district' => $this->billing_district,
                'postal_code' => $this->billing_postal_code,
                'country' => $this->billing_country,
            ],
            'notes' => $this->notes,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->items->count(),
            'can_be_cancelled' => $this->canBeCancelled(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
