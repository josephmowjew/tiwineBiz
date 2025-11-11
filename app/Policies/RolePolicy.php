<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view roles from shops they have access to
        return $user->ownedShops()->exists() || $user->shops()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        // User can view if they have access to the role's shop
        return $this->hasShopAccess($user, $role);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Users who own shops or are shop members can create roles
        return $user->ownedShops()->exists() || $user->shops()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        // Cannot update system roles
        if ($role->is_system_role) {
            return false;
        }

        // Only shop owner or authorized members can update roles
        return $this->hasShopAccess($user, $role);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        // Cannot delete system roles
        if ($role->is_system_role) {
            return false;
        }

        // Only shop owner can delete roles
        return $role->shop->owner_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Role $role): bool
    {
        // Only shop owner can restore roles
        return $role->shop->owner_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Role $role): bool
    {
        // Never allow permanent deletion
        return false;
    }

    /**
     * Check if user has access to the role's shop.
     */
    protected function hasShopAccess(User $user, Role $role): bool
    {
        // Owner access
        if ($role->shop->owner_id === $user->id) {
            return true;
        }

        // Member access
        return $user->shops()->where('shops.id', $role->shop_id)->exists();
    }
}
