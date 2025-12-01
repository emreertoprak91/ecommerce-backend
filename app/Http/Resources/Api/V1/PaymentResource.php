<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'transaction_id' => $this->transaction_id,
            'provider' => $this->provider,
            'status' => $this->status,
            'amount' => [
                'amount' => $this->amount,
                'formatted' => number_format($this->amount / 100, 2) . ' ₺',
            ],
            'currency' => $this->currency,
            'provider_response' => $this->when(
                $request->user()?->is_admin ?? false,
                $this->provider_response
            ),
            'paid_at' => $this->paid_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
