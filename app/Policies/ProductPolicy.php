<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view products from shops they have access to
        return $user->ownedShops()->exists() || $user->shops()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Product $product): bool
    {
        // User can view if they have access to the product's shop
        return $this->hasShopAccess($user, $product);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users who have access to shops can create products
        return $user->ownedShops()->exists() || $user->shops()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Product $product): bool
    {
        // User can update if they have access to the product's shop
        return $this->hasShopAccess($user, $product);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Product $product): bool
    {
        // User can delete if they have access to the product's shop
        return $this->hasShopAccess($user, $product);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Product $product): bool
    {
        // User can restore if they have access to the product's shop
        return $this->hasShopAccess($user, $product);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Product $product): bool
    {
        // Never allow permanent deletion
        return false;
    }

    /**
     * Check if user has access to the product's shop.
     */
    protected function hasShopAccess(User $user, Product $product): bool
    {
        // Owner access
        if ($product->shop->owner_id === $user->id) {
            return true;
        }

        // Member access
        return $user->shops()->where('shops.id', $product->shop_id)->exists();
    }
}
