<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
            'shop_id' => $this->shop_id,
            'customer_number' => $this->customer_number,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'whatsapp_number' => $this->whatsapp_number,
            'physical_address' => $this->physical_address,
            'city' => $this->city,
            'district' => $this->district,
            'credit_limit' => $this->credit_limit,
            'current_balance' => $this->current_balance,
            'total_spent' => $this->total_spent,
            'total_credit_issued' => $this->total_credit_issued,
            'total_credit_collected' => $this->total_credit_collected,
            'trust_level' => $this->trust_level,
            'payment_behavior_score' => $this->payment_behavior_score,
            'purchase_count' => $this->purchase_count,
            'last_purchase_date' => $this->last_purchase_date?->toDateString(),
            'average_purchase_value' => $this->average_purchase_value,
            'preferred_language' => $this->preferred_language,
            'preferred_contact_method' => $this->preferred_contact_method,
            'notes' => $this->notes,
            'tags' => $this->tags,
            'is_active' => $this->is_active,
            'blocked_at' => $this->blocked_at?->toIso8601String(),
            'block_reason' => $this->block_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships (only when loaded)
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'created_by' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
