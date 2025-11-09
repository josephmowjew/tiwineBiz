<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'plan' => $this->plan,
            'billing_cycle' => $this->billing_cycle,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'started_at' => $this->started_at?->toIso8601String(),
            'current_period_start' => $this->current_period_start?->toIso8601String(),
            'current_period_end' => $this->current_period_end?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancel_reason' => $this->cancel_reason,
            'cancel_at_period_end' => $this->cancel_at_period_end,
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'features' => $this->features,
            'limits' => $this->limits,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'payments' => SubscriptionPaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
