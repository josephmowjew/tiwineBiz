<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExchangeRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'base_currency' => $this->base_currency,
            'target_currency' => $this->target_currency,
            'official_rate' => $this->official_rate,
            'street_rate' => $this->street_rate,
            'rate_used' => $this->rate_used,
            'effective_date' => $this->effective_date?->toDateString(),
            'valid_until' => $this->valid_until?->toDateString(),
            'source' => $this->source,
            'created_at' => $this->created_at?->toIso8601String(),
            'created_by' => $this->created_by,

            // Relationships
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];
    }
}
