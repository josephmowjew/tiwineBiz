<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupplierResource extends JsonResource
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
            'name' => $this->name,
            'legal_name' => $this->legal_name,
            'supplier_code' => $this->supplier_code,
            'contact_person' => $this->contact_person,
            'contact_phone' => $this->contact_phone,
            'contact_email' => $this->contact_email,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'physical_address' => $this->physical_address,
            'city' => $this->city,
            'country' => $this->country,
            'payment_terms' => $this->payment_terms,
            'credit_days' => $this->credit_days,
            'bank_account_name' => $this->bank_account_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_name' => $this->bank_name,
            'tax_id' => $this->tax_id,
            'total_orders' => $this->total_orders,
            'total_order_value' => $this->total_order_value,
            'average_delivery_days' => $this->average_delivery_days,
            'reliability_score' => $this->reliability_score,
            'last_order_date' => $this->last_order_date?->toDateString(),
            'is_active' => $this->is_active,
            'is_preferred' => $this->is_preferred,
            'notes' => $this->notes,
            'tags' => $this->tags,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships (only when loaded)
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'products' => ProductResource::collection($this->whenLoaded('products')),
        ];
    }
}
