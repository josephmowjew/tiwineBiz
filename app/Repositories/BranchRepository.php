<?php

namespace App\Repositories;

use App\Models\Branch;
use App\Models\BranchUser;
use App\Repositories\Contracts\BranchRepositoryInterface;
use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BranchRepository extends BaseRepository implements BranchRepositoryInterface
{
    use HasShopScope;

    public function __construct(Branch $model)
    {
        parent::__construct($model);
    }

    protected function applyFilters(Builder $query, array $filters): Builder
    {
        // Apply shop scope - users can only see branches from their shops
        $query = $this->applyShopScope($query);

        // Filter by shop_id
        if (! empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        // Filter by is_active
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        // Filter by branch_type
        if (! empty($filters['branch_type'])) {
            $query->where('branch_type', $filters['branch_type']);
        }

        // Filter by manager
        if (! empty($filters['manager_id'])) {
            $query->where('manager_id', $filters['manager_id']);
        }

        // Search by name, code, or city
        if (! empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                    ->orWhere('code', 'like', "%{$filters['search']}%")
                    ->orWhere('city', 'like', "%{$filters['search']}%");
            });
        }

        // Eager load relationships
        $query->with(['shop', 'manager', 'branchUsers']);

        return $query;
    }

    public function findByShop(string $shopId): Collection
    {
        // Verify user has access to this shop
        $this->verifyShopAccess($shopId);

        return $this->query()
            ->where('shop_id', $shopId)
            ->with(['manager', 'branchUsers'])
            ->get();
    }

    public function getActiveBranches(array $filters = []): Collection
    {
        $filters['is_active'] = true;

        return $this->all($filters);
    }

    public function getMainBranch(string $shopId)
    {
        // Verify user has access to this shop
        $this->verifyShopAccess($shopId);

        return $this->query()
            ->where('shop_id', $shopId)
            ->where('branch_type', 'main')
            ->first();
    }

    public function assignUser(string $branchId, string $userId, string $roleId, array $permissions = []): bool
    {
        // Verify user has access to this branch
        $branch = $this->findOrFail($branchId);
        $this->verifyShopAccess($branch->shop_id);

        // Check if user is already assigned
        $existing = BranchUser::where('branch_id', $branchId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            // Update existing assignment
            $existing->update([
                'role_id' => $roleId,
                'is_active' => true,
                'can_view_reports' => $permissions['can_view_reports'] ?? false,
                'can_manage_stock' => $permissions['can_manage_stock'] ?? false,
                'can_process_sales' => $permissions['can_process_sales'] ?? true,
                'can_manage_customers' => $permissions['can_manage_customers'] ?? true,
                'permissions' => $permissions['permissions'] ?? null,
                'assigned_by' => request()->user()?->id,
                'assigned_at' => now(),
            ]);

            return true;
        }

        // Create new assignment
        BranchUser::create([
            'branch_id' => $branchId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'is_active' => true,
            'can_view_reports' => $permissions['can_view_reports'] ?? false,
            'can_manage_stock' => $permissions['can_manage_stock'] ?? false,
            'can_process_sales' => $permissions['can_process_sales'] ?? true,
            'can_manage_customers' => $permissions['can_manage_customers'] ?? true,
            'permissions' => $permissions['permissions'] ?? null,
            'assigned_by' => request()->user()?->id,
            'assigned_at' => now(),
        ]);

        return true;
    }

    public function removeUser(string $branchId, string $userId): bool
    {
        // Verify user has access to this branch
        $branch = $this->findOrFail($branchId);
        $this->verifyShopAccess($branch->shop_id);

        return BranchUser::where('branch_id', $branchId)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    public function getBranchUsers(string $branchId): Collection
    {
        // Verify user has access to this branch
        $branch = $this->findOrFail($branchId);
        $this->verifyShopAccess($branch->shop_id);

        return BranchUser::where('branch_id', $branchId)
            ->with(['user', 'role'])
            ->get();
    }
}
