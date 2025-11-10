<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;

interface BranchRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Get all branches for a specific shop.
     */
    public function findByShop(string $shopId): Collection;

    /**
     * Get active branches only.
     */
    public function getActiveBranches(array $filters = []): Collection;

    /**
     * Get main branch for a shop.
     */
    public function getMainBranch(string $shopId);

    /**
     * Assign user to branch with role.
     */
    public function assignUser(string $branchId, string $userId, string $roleId, array $permissions = []): bool;

    /**
     * Remove user from branch.
     */
    public function removeUser(string $branchId, string $userId): bool;

    /**
     * Get users assigned to a branch.
     */
    public function getBranchUsers(string $branchId): Collection;
}
