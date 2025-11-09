<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'customer_id' => $this->customer_id,
            'credit_id' => $this->credit_id,
            'sale_id' => $this->sale_id,
            'payment_number' => $this->payment_number,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'exchange_rate' => $this->exchange_rate,
            'amount_in_base_currency' => $this->amount_in_base_currency,
            'payment_method' => $this->payment_method,
            'transaction_reference' => $this->transaction_reference,
            'mobile_money_details' => $this->mobile_money_details,
            'bank_name' => $this->bank_name,
            'cheque_number' => $this->cheque_number,
            'cheque_date' => $this->cheque_date?->toDateString(),
            'payment_date' => $this->payment_date?->toIso8601String(),
            'cleared_at' => $this->cleared_at?->toIso8601String(),
            'notes' => $this->notes,
            'receipt_sent' => $this->receipt_sent,
            'receipt_sent_at' => $this->receipt_sent_at?->toIso8601String(),
            'received_by' => $this->received_by,
            'created_at' => $this->created_at?->toIso8601String(),

            // Relationships (only when loaded)
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'credit' => new CreditResource($this->whenLoaded('credit')),
            'sale' => new SaleResource($this->whenLoaded('sale')),
            'receivedBy' => new UserResource($this->whenLoaded('receivedBy')),
        ];
    }
}
