<?php

namespace App\Repositories;

use App\Models\MobileMoneyTransaction;
use App\Repositories\Contracts\MobileMoneyTransactionRepositoryInterface;
use App\Traits\Filterable;
use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Builder;

class MobileMoneyTransactionRepository extends BaseRepository implements MobileMoneyTransactionRepositoryInterface
{
    use Filterable, HasShopScope;

    public function __construct(MobileMoneyTransaction $model)
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

        // Search filter (searches across transaction ID, MSISDN, sender/receiver names)
        if (! empty($filters['search_term']) || ! empty($filters['search'])) {
            $search = $filters['search_term'] ?? $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhere('msisdn', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%")
                    ->orWhere('receiver_name', 'like', "%{$search}%");
            });
        }

        // Filter by shop
        if (! empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        // Filter by provider
        if (! empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }

        // Filter by transaction type
        if (! empty($filters['transaction_type'])) {
            $query->where('transaction_type', $filters['transaction_type']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by MSISDN
        if (! empty($filters['msisdn'])) {
            $query->where('msisdn', 'like', "%{$filters['msisdn']}%");
        }

        // Filter by reference type and ID
        if (! empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        if (! empty($filters['reference_id'])) {
            $query->where('reference_id', $filters['reference_id']);
        }

        // Filter by amount range
        if (! empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (! empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        // Filter by date range
        if (! empty($filters['from_date']) || ! empty($filters['to_date'])) {
            $query = $this->filterDateRange(
                $query,
                'transaction_date',
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
            $query->with(['shop']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'transaction_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Secondary sort by created_at
        if ($sortBy !== 'created_at') {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}
