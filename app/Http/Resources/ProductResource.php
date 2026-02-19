<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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

            // Legacy fields - deprecated, use stock_by_branch instead
            'branch_id' => $this->branch_id,
            'branch_name' => $this->branch?->name,
            'quantity' => $this->quantity ?? 0,

            // Product master data
            'name' => $this->name,
            'name_chichewa' => $this->name_chichewa,
            'description' => $this->description,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'manufacturer_code' => $this->manufacturer_code,
            'category_id' => $this->category_id,

            // Pricing
            'cost_price' => $this->cost_price,
            'landing_cost' => $this->landing_cost,
            'total_cost_price' => $this->getTotalCostPriceAttribute(),
            'selling_price' => $this->selling_price,
            'min_price' => $this->calculateMinimumPrice(),
            'base_currency' => $this->base_currency,
            'base_currency_price' => $this->base_currency_price,
            'last_exchange_rate_snapshot' => $this->last_exchange_rate_snapshot,

            // NEW: Multi-branch stock information
            'total_quantity' => $this->total_quantity ?? $this->quantity ?? 0,
            'stock_by_branch' => collect($this->getStockByBranch())->toArray(),

            // Legacy stock fields (for backward compatibility)
            'unit' => $this->unit,
            'min_stock_level' => $this->min_stock_level,
            'max_stock_level' => $this->max_stock_level,
            'reorder_point' => $this->reorder_point,
            'reorder_quantity' => $this->reorder_quantity,
            'storage_location' => $this->storage_location,
            'shelf' => $this->shelf,
            'bin' => $this->bin,

            // Tax
            'is_vat_applicable' => $this->is_vat_applicable,
            'vat_rate' => $this->vat_rate,
            'tax_category' => $this->tax_category,

            // Supplier
            'primary_supplier_id' => $this->primary_supplier_id,

            // Product attributes
            'attributes' => $this->attributes,
            'images' => $this->images,
            'track_batches' => $this->track_batches,
            'track_serial_numbers' => $this->track_serial_numbers,
            'has_expiry' => $this->has_expiry,

            // Sales data
            'total_sold' => $this->total_sold,
            'total_revenue' => $this->total_revenue,
            'last_sold_at' => $this->last_sold_at?->toIso8601String(),
            'last_restocked_at' => $this->last_restocked_at?->toIso8601String(),

            // Status
            'is_active' => $this->is_active,
            'discontinued_at' => $this->discontinued_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Discount guidance for POS
            'discount_guidance' => $this->getDiscountGuidance(1),

            // Relationships (only when loaded)
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'primary_supplier' => new SupplierResource($this->whenLoaded('primarySupplier')),
            'product_branches' => ProductBranchResource::collection($this->whenLoaded('productBranches')),
            'created_by' => new UserResource($this->whenLoaded('createdBy')),
            'updated_by' => new UserResource($this->whenLoaded('updatedBy')),
        ];
    }
}
