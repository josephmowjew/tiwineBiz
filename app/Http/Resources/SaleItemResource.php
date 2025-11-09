<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleItemResource extends JsonResource
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
            'sale_id' => $this->sale_id,
            'product_id' => $this->product_id,
            'batch_id' => $this->batch_id,
            'product_name' => $this->product_name,
            'product_name_chichewa' => $this->product_name_chichewa,
            'product_sku' => $this->product_sku,
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'unit_cost' => $this->unit_cost,
            'unit_price' => $this->unit_price,
            'discount_amount' => $this->discount_amount,
            'discount_percentage' => $this->discount_percentage,
            'is_taxable' => $this->is_taxable,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'notes' => $this->notes,

            // Relationships (only when loaded)
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
