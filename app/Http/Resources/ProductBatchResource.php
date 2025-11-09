<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBatchResource extends JsonResource
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
            'product_id' => $this->product_id,
            'purchase_order_id' => $this->purchase_order_id,
            'supplier_id' => $this->supplier_id,
            'batch_number' => $this->batch_number,
            'lot_number' => $this->lot_number,
            'initial_quantity' => $this->initial_quantity,
            'remaining_quantity' => $this->remaining_quantity,
            'unit_cost' => $this->unit_cost,
            'currency' => $this->currency,
            'product_cost' => $this->product_cost,
            'freight_cost' => $this->freight_cost,
            'customs_duty' => $this->customs_duty,
            'clearing_fee' => $this->clearing_fee,
            'other_costs' => $this->other_costs,
            'total_landed_cost' => $this->total_landed_cost,
            'purchase_date' => $this->purchase_date?->toDateString(),
            'manufacture_date' => $this->manufacture_date?->toDateString(),
            'expiry_date' => $this->expiry_date?->toDateString(),
            'is_depleted' => $this->is_depleted,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
