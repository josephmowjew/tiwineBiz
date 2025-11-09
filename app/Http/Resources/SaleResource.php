<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
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
            'sale_number' => $this->sale_number,
            'customer_id' => $this->customer_id,
            'subtotal' => $this->subtotal,
            'discount_amount' => $this->discount_amount,
            'discount_percentage' => $this->discount_percentage,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'amount_paid' => $this->amount_paid,
            'balance' => $this->balance,
            'change_given' => $this->change_given,
            'payment_status' => $this->payment_status,
            'payment_methods' => $this->payment_methods,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'amount_in_base_currency' => $this->amount_in_base_currency,
            'is_fiscalized' => $this->is_fiscalized,
            'efd_device_id' => $this->efd_device_id,
            'efd_receipt_number' => $this->efd_receipt_number,
            'efd_qr_code' => $this->efd_qr_code,
            'efd_fiscal_signature' => $this->efd_fiscal_signature,
            'efd_transmitted_at' => $this->efd_transmitted_at?->toIso8601String(),
            'efd_response' => $this->efd_response,
            'sale_type' => $this->sale_type,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'sale_date' => $this->sale_date?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'refunded_at' => $this->refunded_at?->toIso8601String(),
            'refund_amount' => $this->refund_amount,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships (only when loaded)
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'served_by' => new UserResource($this->whenLoaded('servedBy')),
            'cancelled_by' => new UserResource($this->whenLoaded('cancelledBy')),
            'items' => SaleItemResource::collection($this->whenLoaded('items')),
        ];
    }
}
