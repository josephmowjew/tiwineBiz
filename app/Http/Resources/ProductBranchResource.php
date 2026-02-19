<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBranchResource extends JsonResource
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
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch?->name,

            // Stock information
            'quantity' => (float) $this->quantity,
            'min_stock_level' => $this->min_stock_level,
            'max_stock_level' => $this->max_stock_level,
            'reorder_point' => $this->reorder_point,

            // Pricing (can vary per branch)
            'cost_price' => $this->cost_price,
            'landing_cost' => $this->landing_cost,

            // Status
            'is_active' => $this->is_active,
            'stock_status' => $this->getStockStatus(),
            'is_low_stock' => $this->isLowStock(),
            'is_critically_low' => $this->isCriticallyLowStock(),
            'needs_reorder' => $this->needsReorder(),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships (only when loaded)
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
