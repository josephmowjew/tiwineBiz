<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShopResource extends JsonResource
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
            'owner_id' => $this->owner_id,
            'name' => $this->name,
            'business_type' => $this->business_type,
            'legal_name' => $this->legal_name,
            'registration_number' => $this->registration_number,
            'tpin' => $this->tpin,
            'vrn' => $this->vrn,
            'is_vat_registered' => $this->is_vat_registered,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'address' => $this->address,
            'city' => $this->city,
            'district' => $this->district,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'logo_url' => $this->logo_url,
            'primary_color' => $this->primary_color,
            'default_currency' => $this->default_currency,
            'fiscal_year_start_month' => $this->fiscal_year_start_month,
            'subscription_tier' => $this->subscription_tier,
            'subscription_status' => $this->subscription_status,
            'subscription_started_at' => $this->subscription_started_at?->toIso8601String(),
            'subscription_expires_at' => $this->subscription_expires_at?->toIso8601String(),
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'features' => $this->features,
            'limits' => $this->limits,
            'settings' => $this->settings,
            'is_active' => $this->is_active,
            'deactivated_at' => $this->deactivated_at?->toIso8601String(),
            'deactivation_reason' => $this->deactivation_reason,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships (only when loaded)
            'owner' => new UserResource($this->whenLoaded('owner')),
            'users' => UserResource::collection($this->whenLoaded('users')),
        ];
    }
}
