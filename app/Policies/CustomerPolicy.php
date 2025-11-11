<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view customers from shops they have access to
        return $user->ownedShops()->exists() || $user->shops()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Customer $customer): bool
    {
        // User can view if they have access to the customer's shop
        return $this->hasShopAccess($user, $customer);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users who have access to shops can create customers
        return $user->ownedShops()->exists() || $user->shops()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Customer $customer): bool
    {
        // User can update if they have access to the customer's shop
        return $this->hasShopAccess($user, $customer);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Customer $customer): bool
    {
        // User can delete if they have access to the customer's shop
        return $this->hasShopAccess($user, $customer);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Customer $customer): bool
    {
        // User can restore if they have access to the customer's shop
        return $this->hasShopAccess($user, $customer);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Customer $customer): bool
    {
        // Never allow permanent deletion
        return false;
    }

    /**
     * Check if user has access to the customer's shop.
     */
    protected function hasShopAccess(User $user, Customer $customer): bool
    {
        // Owner access
        if ($customer->shop->owner_id === $user->id) {
            return true;
        }

        // Member access
        return $user->shops()->where('shops.id', $customer->shop_id)->exists();
    }
}
