<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EfdTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'efd_device_id' => $this->efd_device_id,
            'efd_device_serial' => $this->efd_device_serial,
            'sale_id' => $this->sale_id,
            'fiscal_receipt_number' => $this->fiscal_receipt_number,
            'fiscal_day_counter' => $this->fiscal_day_counter,
            'fiscal_signature' => $this->fiscal_signature,
            'qr_code_data' => $this->qr_code_data,
            'verification_url' => $this->verification_url,
            'total_amount' => $this->total_amount,
            'vat_amount' => $this->vat_amount,
            'mra_response_code' => $this->mra_response_code,
            'mra_response_message' => $this->mra_response_message,
            'mra_acknowledgement' => $this->mra_acknowledgement,
            'transmitted_at' => $this->transmitted_at?->toIso8601String(),
            'transmission_status' => $this->transmission_status,
            'retry_count' => $this->retry_count,
            'last_retry_at' => $this->last_retry_at?->toIso8601String(),
            'next_retry_at' => $this->next_retry_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),

            // Relationships
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'sale' => new SaleResource($this->whenLoaded('sale')),
        ];
    }
}
