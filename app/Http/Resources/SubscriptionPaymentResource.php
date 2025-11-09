<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'shop_id' => $this->shop_id,
            'payment_number' => $this->payment_number,
            'invoice_number' => $this->invoice_number,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method' => $this->payment_method,
            'transaction_reference' => $this->transaction_reference,
            'status' => $this->status,
            'period_start' => $this->period_start?->toIso8601String(),
            'period_end' => $this->period_end?->toIso8601String(),
            'payment_date' => $this->payment_date?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'confirmed_by' => $this->confirmed_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'confirmed_by_user' => new UserResource($this->whenLoaded('confirmedBy')),
        ];
    }
}
