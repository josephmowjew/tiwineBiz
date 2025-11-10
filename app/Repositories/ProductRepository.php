<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Traits\Filterable;
use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Builder;

class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    use Filterable, HasShopScope;

    public function __construct(Product $model)
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

        // Search filter (searches across name, sku, barcode, description)
        if (! empty($filters['search_term']) || ! empty($filters['search'])) {
            $search = $filters['search_term'] ?? $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Filter by shop
        if (! empty($filters['shop_id'])) {
            $query->where('shop_id', $filters['shop_id']);
        }

        // Filter by category
        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Filter by status (active/inactive)
        if (isset($filters['is_active'])) {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        // Filter by stock status
        if (! empty($filters['stock_status'])) {
            match ($filters['stock_status']) {
                'in_stock' => $query->where('quantity', '>', 0),
                'out_of_stock' => $query->where('quantity', '<=', 0),
                'low_stock' => $query->whereColumn('quantity', '<=', 'low_stock_threshold'),
                default => null,
            };
        }

        // Low stock filter
        if (! empty($filters['low_stock']) && (bool) $filters['low_stock']) {
            $query->whereColumn('quantity', '<=', 'min_stock_level');
        }

        // Filter by trackable products
        if (isset($filters['track_stock'])) {
            $query->where('track_stock', (bool) $filters['track_stock']);
        }

        // Price range filter
        if (! empty($filters['min_price'])) {
            $query->where('price', '>=', $filters['min_price']);
        }

        if (! empty($filters['max_price'])) {
            $query->where('price', '<=', $filters['max_price']);
        }

        // Created date range
        if (! empty($filters['from_date']) || ! empty($filters['to_date'])) {
            $query = $this->filterDateRange(
                $query,
                'created_at',
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
            $query->with(['category', 'shop']);
        }

        // Sorting
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query;
    }
}
