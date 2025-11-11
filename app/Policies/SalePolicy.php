<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view sales from shops they have access to
        return $user->ownedShops()->exists() || $user->shops()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Sale $sale): bool
    {
        // User can view if they have access to the sale's shop
        return $this->hasShopAccess($user, $sale);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users who have access to shops can create sales
        return $user->ownedShops()->exists() || $user->shops()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Sale $sale): bool
    {
        // Sales are generally immutable, but allow updates for specific cases
        // User can update if they have access to the sale's shop
        return $this->hasShopAccess($user, $sale);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Sale $sale): bool
    {
        // Only shop owner can void/delete sales (for corrections)
        return $sale->shop->owner_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Sale $sale): bool
    {
        // Only shop owner can restore sales
        return $sale->shop->owner_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Sale $sale): bool
    {
        // Never allow permanent deletion of sales
        return false;
    }

    /**
     * Check if user has access to the sale's shop.
     */
    protected function hasShopAccess(User $user, Sale $sale): bool
    {
        // Owner access
        if ($sale->shop->owner_id === $user->id) {
            return true;
        }

        // Member access
        return $user->shops()->where('shops.id', $sale->shop_id)->exists();
    }
}
