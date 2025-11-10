<?php

namespace App\Repositories;

use App\Models\EfdTransaction;
use App\Repositories\Contracts\EfdTransactionRepositoryInterface;
use App\Traits\Filterable;
use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Builder;

class EfdTransactionRepository extends BaseRepository implements EfdTransactionRepositoryInterface
{
    use Filterable, HasShopScope;

    public function __construct(EfdTransaction $model)
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

        // Search filter (searches across device IDs, receipt numbers, response messages)
        if (! empty($filters['search_term']) || ! empty($filters['search'])) {
            $search = $filters['search_term'] ?? $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('efd_device_id', 'like', "%{$search}%")
                    ->orWhere('efd_device_serial', 'like', "%{$search}%")
                    ->orWhere('fiscal_receipt_number', 'like', "%{$search}%")
                    ->orWhere('mra_response_message', 'like', "%{$search}%");
            });
        }

        // Filter by shop
        if (! empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        // Filter by EFD device
        if (! empty($filters['efd_device_id'])) {
            $query->where('efd_device_id', $filters['efd_device_id']);
        }

        // Filter by sale
        if (! empty($filters['sale_id'])) {
            $query->where('sale_id', $filters['sale_id']);
        }

        // Filter by transmission status
        if (! empty($filters['transmission_status'])) {
            $query->where('transmission_status', $filters['transmission_status']);
        }

        // Filter by fiscal receipt number
        if (! empty($filters['fiscal_receipt_number'])) {
            $query->where('fiscal_receipt_number', 'like', "%{$filters['fiscal_receipt_number']}%");
        }

        // Filter transactions pending retry
        if (! empty($filters['pending_retry']) && (bool) $filters['pending_retry']) {
            $query->whereNotNull('next_retry_at')
                ->where('next_retry_at', '<=', now())
                ->where('transmission_status', '!=', 'success');
        }

        // Filter failed transactions with exhausted retries
        if (! empty($filters['retry_exhausted']) && (bool) $filters['retry_exhausted']) {
            $query->where('transmission_status', 'failed')
                ->where('retry_count', '>=', 3);
        }

        // Filter by transmitted date range
        if (! empty($filters['from_date']) || ! empty($filters['to_date'])) {
            $query = $this->filterDateRange(
                $query,
                'transmitted_at',
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
            $query->with(['shop', 'sale']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'transmitted_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Secondary sort by created_at
        if ($sortBy !== 'created_at') {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}
