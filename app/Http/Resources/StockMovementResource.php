<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
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
            'product_id' => $this->product_id,
            'batch_id' => $this->batch_id,
            'movement_type' => $this->movement_type,
            'quantity' => $this->quantity,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'unit_cost' => $this->unit_cost,
            'total_cost' => $this->total_cost,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reason' => $this->reason,
            'notes' => $this->notes,
            'from_location' => $this->from_location,
            'to_location' => $this->to_location,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),

            // Relationships (only when loaded)
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'product' => new ProductResource($this->whenLoaded('product')),
            'batch' => $this->whenLoaded('batch', fn () => $this->batch ? new ProductBatchResource($this->batch) : null),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
