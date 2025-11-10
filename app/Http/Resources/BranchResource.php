<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BranchResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'branch_type' => $this->branch_type,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'district' => $this->district,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'manager_id' => $this->manager_id,
            'is_active' => $this->is_active,
            'opened_at' => $this->opened_at,
            'closed_at' => $this->closed_at,
            'settings' => $this->settings,
            'features' => $this->features,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Relationships (loaded conditionally)
            'shop' => $this->whenLoaded('shop'),
            'manager' => $this->whenLoaded('manager', fn () => [
                'id' => $this->manager->id,
                'name' => $this->manager->name,
                'email' => $this->manager->email,
            ]),
            'users_count' => $this->whenCounted('branchUsers'),
        ];
    }
}
