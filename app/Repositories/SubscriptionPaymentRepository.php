<?php

namespace App\Repositories;

use App\Models\SubscriptionPayment;
use App\Repositories\Contracts\SubscriptionPaymentRepositoryInterface;
use App\Traits\Filterable;
use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Builder;

class SubscriptionPaymentRepository extends BaseRepository implements SubscriptionPaymentRepositoryInterface
{
    use Filterable, HasShopScope;

    public function __construct(SubscriptionPayment $model)
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

        // Search filter (searches across payment number, invoice number, transaction reference)
        if (! empty($filters['search_term']) || ! empty($filters['search'])) {
            $search = $filters['search_term'] ?? $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('invoice_number', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%");
            });
        }

        // Filter by shop
        if (! empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        // Filter by subscription
        if (! empty($filters['subscription_id'])) {
            $query->where('subscription_id', $filters['subscription_id']);
        }

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by payment method
        if (! empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        // Filter by amount range
        if (! empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }

        if (! empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        // Filter by payment date range
        if (! empty($filters['from_date']) || ! empty($filters['to_date'])) {
            $query = $this->filterDateRange(
                $query,
                'payment_date',
                $filters['from_date'] ?? null,
                $filters['to_date'] ?? null
            );
        }

        // Filter unconfirmed payments
        if (! empty($filters['unconfirmed']) && (bool) $filters['unconfirmed']) {
            $query->where('status', 'pending')
                ->whereNull('confirmed_at');
        }

        // Filter payments awaiting confirmation
        if (! empty($filters['awaiting_confirmation']) && (bool) $filters['awaiting_confirmation']) {
            $query->where('status', 'pending')
                ->whereNotNull('payment_date');
        }

        // Eager load relationships if specified
        if (! empty($filters['with'])) {
            $relations = is_array($filters['with']) ? $filters['with'] : explode(',', $filters['with']);
            $query->with($relations);
        } else {
            // Default relationships to load
            $query->with(['subscription', 'shop', 'confirmedBy']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'payment_date';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        // Secondary sort by created_at
        if ($sortBy !== 'created_at') {
            $query->orderBy('created_at', 'desc');
        }

        return $query;
    }
}
