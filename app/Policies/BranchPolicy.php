<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Users can view branches from shops they own or are members of
        return $user->ownedShops()->exists() || $user->shops()->exists();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Branch $branch): bool
    {
        // User can view if they have access to the shop
        return $this->hasShopAccess($user, $branch);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only shop owners or admins can create branches
        return $user->ownedShops()->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Branch $branch): bool
    {
        // Only shop owner can update branches
        return $branch->shop->owner_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Branch $branch): bool
    {
        // Only shop owner can delete branches
        // Cannot delete main branch
        if ($branch->branch_type === 'main') {
            return false;
        }

        return $branch->shop->owner_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Branch $branch): bool
    {
        return $branch->shop->owner_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Branch $branch): bool
    {
        return false; // Never allow permanent deletion
    }

    /**
     * Determine whether the user can assign users to the branch.
     */
    public function assignUsers(User $user, Branch $branch): bool
    {
        // Shop owner or branch manager can assign users
        return $branch->shop->owner_id === $user->id || $branch->manager_id === $user->id;
    }

    /**
     * Check if user has access to the branch's shop.
     */
    protected function hasShopAccess(User $user, Branch $branch): bool
    {
        // Owner access
        if ($branch->shop->owner_id === $user->id) {
            return true;
        }

        // Member access
        return $user->shops()->where('shops.id', $branch->shop_id)->exists();
    }
}
