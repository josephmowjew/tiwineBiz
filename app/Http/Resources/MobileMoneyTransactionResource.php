<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MobileMoneyTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shop_id' => $this->shop_id,
            'provider' => $this->provider,
            'transaction_id' => $this->transaction_id,
            'transaction_type' => $this->transaction_type,
            'msisdn' => $this->msisdn,
            'sender_name' => $this->sender_name,
            'receiver_name' => $this->receiver_name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'transaction_fee' => $this->transaction_fee,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'status' => $this->status,
            'request_payload' => $this->request_payload,
            'response_payload' => $this->response_payload,
            'webhook_received_at' => $this->webhook_received_at?->toIso8601String(),
            'webhook_payload' => $this->webhook_payload,
            'transaction_date' => $this->transaction_date?->toIso8601String(),
            'confirmed_at' => $this->confirmed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),

            // Relationships
            'shop' => new ShopResource($this->whenLoaded('shop')),
        ];
    }
}
