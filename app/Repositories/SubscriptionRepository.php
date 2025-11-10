<?php

namespace App\Repositories;

use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use App\Traits\Filterable;
use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionRepository extends BaseRepository implements SubscriptionRepositoryInterface
{
    use Filterable, HasShopScope;

    public function __construct(Subscription $model)
    {
        parent::__construct($model);
    }

    /**
     * Apply filters to the query.
     */
    protected function applyFilters(Builder $query, array $filters): Builder
    {
        // Apply shop scoping for multi-tenancy
        $query = $this->applyShopScope($query);

        // Filter by ID (for single record lookup)
        if (! empty($filters['id'])) {
            $query->where('id', $filters['id']);
        }

        // Search filter (searches across plan details)
        if (! empty($filters['search_term']) || ! empty($filters['search'])) {
            $search = $filters['search_term'] ?? $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('plan', 'like', "%{$search}%")
                    ->orWhere('cancel_reason', 'like', "%{$search}%");
            });
        }

        // Filter by shop
        if (! empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        // Filter by plan
        if (! empty($filters['plan'])) {
            $query->where('plan', $filters['plan']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by billing cycle
        if (! empty($filters['billing_cycle'])) {
            $query->where('billing_cycle', $filters['billing_cycle']);
        }

        // Filter expiring soon (within next 7 days)
        if (! empty($filters['expiring_soon']) && (bool) $filters['expiring_soon']) {
            $query->where('status', 'active')
                ->whereBetween('current_period_end', [now(), now()->addDays(7)]);
        }

        // Filter expired
        if (! empty($filters['expired']) && (bool) $filters['expired']) {
            $query->where('status', 'expired')
                ->where('current_period_end', '<', now());
        }

        // Filter cancelled but still active
        if (! empty($filters['pending_cancellation']) && (bool) $filters['pending_cancellation']) {
            $query->where('cancel_at_period_end', true)
                ->where('current_period_end', '>', now());
        }

        // Filter by amount range
        if (! empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (! empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        // Period end date range
        if (! empty($filters['from_date']) || ! empty($filters['to_date'])) {
            $query = $this->filterDateRange(
                $query,
                'current_period_end',
                $filters['from_date'] ?? null,
                $filters['to_date'] ?? null
            );
        }

        // Eager load relationships if specified
        if (! empty($filters['with'])) {
            $relations = is_array($filters['with']) ? $filters['with'] : explode(',', $filters['with']);
            $query->with($relations);
        } else {
            // Default relationships to load
            $query->with(['shop', 'payments']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'current_period_end';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Secondary sort by created_at
        if ($sortBy !== 'created_at') {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}
