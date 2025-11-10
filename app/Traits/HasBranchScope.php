<?php

namespace App\Traits;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait HasBranchScope
{
    use HasShopScope;

    /**
     * Get branch IDs accessible by the authenticated user.
     */
    protected function getAccessibleBranchIds(?string $shopId = null): Collection
    {
        $user = auth()->user();

        if (! $user) {
            return collect();
        }

        return $user->getAccessibleBranchIds($shopId);
    }

    /**
     * Apply branch scope to query.
     * For models that are always branch-specific (sales, stock_movements).
     */
    protected function applyBranchScope(Builder $query, bool $requireBranch = true): Builder
    {
        $branchIds = $this->getAccessibleBranchIds();

        if ($branchIds->isEmpty()) {
            // If no accessible branches, return empty result
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('branch_id', $branchIds);
    }

    /**
     * Apply branch scope with shop-level fallback.
     * For models that can be branch-specific OR shop-level (customers).
     */
    protected function applyBranchScopeWithShopFallback(Builder $query): Builder
    {
        $branchIds = $this->getAccessibleBranchIds();
        $shopIds = $this->getAccessibleShopIds();

        if ($branchIds->isEmpty() && $shopIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        // Include records that belong to accessible branches OR shop-level records (branch_id IS NULL)
        return $query->where(function ($q) use ($branchIds, $shopIds) {
            $q->whereIn('branch_id', $branchIds)
                ->orWhere(function ($q) use ($shopIds) {
                    $q->whereNull('branch_id')
                        ->whereIn('shop_id', $shopIds);
                });
        });
    }

    /**
     * Verify that a branch belongs to the authenticated user's accessible branches.
     */
    protected function verifyBranchAccess(string $branchId): Branch
    {
        $accessibleBranchIds = $this->getAccessibleBranchIds();

        return Branch::whereIn('id', $accessibleBranchIds)
            ->where('id', $branchId)
            ->firstOrFail();
    }

    /**
     * Verify that a model belongs to user's accessible branches.
     */
    protected function verifyModelBranchAccess($model): void
    {
        if (! property_exists($model, 'branch_id') && ! isset($model->branch_id)) {
            return;
        }

        // Allow NULL branch_id (shop-level records)
        if (is_null($model->branch_id)) {
            $this->verifyModelShopAccess($model);

            return;
        }

        $this->verifyBranchAccess($model->branch_id);
    }
}
