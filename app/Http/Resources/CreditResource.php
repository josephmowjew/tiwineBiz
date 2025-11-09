<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditResource extends JsonResource
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
            'sale_id' => $this->sale_id,
            'credit_number' => $this->credit_number,
            'original_amount' => $this->original_amount,
            'amount_paid' => $this->amount_paid,
            'balance' => $this->balance,
            'issue_date' => $this->issue_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'payment_term' => $this->payment_term,
            'status' => $this->status,
            'last_reminder_sent_at' => $this->last_reminder_sent_at?->toIso8601String(),
            'reminder_count' => $this->reminder_count,
            'next_reminder_date' => $this->next_reminder_date?->toDateString(),
            'collection_attempts' => $this->collection_attempts,
            'last_collection_attempt_at' => $this->last_collection_attempt_at?->toIso8601String(),
            'escalation_level' => $this->escalation_level,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'created_by' => $this->created_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'paid_at' => $this->paid_at?->toIso8601String(),
            'written_off_at' => $this->written_off_at?->toIso8601String(),
            'written_off_by' => $this->written_off_by,
            'write_off_reason' => $this->write_off_reason,

            // Relationships (only when loaded)
            'shop' => new ShopResource($this->whenLoaded('shop')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'sale' => new SaleResource($this->whenLoaded('sale')),
            'creator' => new UserResource($this->whenLoaded('creator')),
            'writtenOffBy' => new UserResource($this->whenLoaded('writtenOffBy')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
        ];
    }
}
