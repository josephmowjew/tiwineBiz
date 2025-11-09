<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'supplier_id' => $this->supplier_id,
            'po_number' => $this->po_number,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'freight_cost' => $this->freight_cost,
            'insurance_cost' => $this->insurance_cost,
            'customs_duty' => $this->customs_duty,
            'clearing_fee' => $this->clearing_fee,
            'transport_cost' => $this->transport_cost,
            'other_charges' => $this->other_charges,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'amount_in_base_currency' => $this->amount_in_base_currency,
            'status' => $this->status,
            'order_date' => $this->order_date?->toDateString(),
            'expected_delivery_date' => $this->expected_delivery_date?->toDateString(),
            'actual_delivery_date' => $this->actual_delivery_date?->toDateString(),
            'shipping_method' => $this->shipping_method,
            'tracking_number' => $this->tracking_number,
            'border_point' => $this->border_point,
            'clearing_agent_name' => $this->clearing_agent_name,
            'clearing_agent_phone' => $this->clearing_agent_phone,
            'customs_entry_number' => $this->customs_entry_number,
            'documents' => $this->documents,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'created_by' => $this->created_by,
            'approved_by' => $this->approved_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),

            'shop' => new ShopResource($this->whenLoaded('shop')),
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'approver' => new UserResource($this->whenLoaded('approver')),
            'items' => PurchaseOrderItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
