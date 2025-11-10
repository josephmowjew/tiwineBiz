<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProductReportService extends BaseReportService
{
    /**
     * Get top selling products report.
     */
    public function getTopSellingProducts(?string $startDate = null, ?string $endDate = null, ?string $branchId = null, int $limit = 20): array
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $cacheKey = $this->getCacheKey('top_selling_products', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'branch' => $branchId,
            'limit' => $limit,
        ]);

        return $this->cacheReport($cacheKey, function () use ($start, $end, $branchId, $limit) {
            $query = SaleItem::query()
                ->select([
                    'product_id',
                    'product_name',
                    'product_sku',
                    DB::raw('SUM(quantity) as total_quantity_sold'),
                    DB::raw('COUNT(DISTINCT sale_id) as number_of_sales'),
                    DB::raw('SUM(total) as total_revenue'),
                    DB::raw('SUM(total - (unit_cost * quantity)) as total_profit'),
                    DB::raw('AVG(unit_price) as average_selling_price'),
                ])
                ->whereHas('sale', function ($saleQuery) use ($start, $end, $branchId) {
                    $saleQuery->whereBetween('sale_date', [$start, $end])
                        ->whereNull('cancelled_at');
                    $this->applyBranchFilter($saleQuery, $branchId);
                })
                ->groupBy('product_id', 'product_name', 'product_sku')
                ->orderByDesc('total_quantity_sold')
                ->limit($limit)
                ->get();

            $products = $query->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'product_sku' => $item->product_sku,
                    'total_quantity_sold' => (int) $item->total_quantity_sold,
                    'number_of_sales' => (int) $item->number_of_sales,
                    'total_revenue' => $this->formatCurrency($item->total_revenue),
                    'total_profit' => $this->formatCurrency($item->total_profit),
                    'average_selling_price' => $this->formatCurrency($item->average_selling_price),
                    'profit_margin' => $item->total_revenue > 0
                        ? round(($item->total_profit / $item->total_revenue) * 100, 2)
                        : 0,
                ];
            });

            return $this->formatReportData([
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ],
                'top_products' => $products,
            ]);
        });
    }

    /**
     * Get slow moving products report.
     */
    public function getSlowMovingProducts(?string $startDate = null, ?string $endDate = null, ?string $branchId = null, int $limit = 20): array
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $cacheKey = $this->getCacheKey('slow_moving_products', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'branch' => $branchId,
            'limit' => $limit,
        ]);

        return $this->cacheReport($cacheKey, function () use ($start, $end, $limit) {
            $accessibleBranchIds = $this->getAccessibleBranches();

            if ($accessibleBranchIds->isEmpty()) {
                return $this->formatReportData([
                    'period' => [
                        'start_date' => $start->toDateString(),
                        'end_date' => $end->toDateString(),
                    ],
                    'slow_moving_products' => [],
                ]);
            }

            // Get products with their sales data
            $query = Product::query()
                ->select([
                    'products.id',
                    'products.name',
                    'products.sku',
                    'products.quantity as current_stock',
                    'products.cost_price',
                    'products.selling_price',
                    'products.last_sold_at',
                    DB::raw('COALESCE(SUM(sale_items.quantity), 0) as quantity_sold'),
                    DB::raw('COUNT(DISTINCT sale_items.sale_id) as number_of_sales'),
                ])
                ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
                ->leftJoin('sales', function ($join) use ($start, $end) {
                    $join->on('sale_items.sale_id', '=', 'sales.id')
                        ->whereBetween('sales.sale_date', [$start, $end])
                        ->whereNull('sales.cancelled_at');
                })
                ->whereIn('products.branch_id', $accessibleBranchIds)
                ->where('products.quantity', '>', 0)
                ->groupBy(
                    'products.id',
                    'products.name',
                    'products.sku',
                    'products.quantity',
                    'products.cost_price',
                    'products.selling_price',
                    'products.last_sold_at'
                )
                ->orderBy('quantity_sold', 'asc')
                ->orderBy('products.last_sold_at', 'asc')
                ->limit($limit)
                ->get();

            $products = $query->map(function ($product) {
                $daysSinceLastSale = $product->last_sold_at
                    ? Carbon::parse($product->last_sold_at)->diffInDays(now())
                    : null;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'current_stock' => (int) $product->current_stock,
                    'quantity_sold' => (int) $product->quantity_sold,
                    'number_of_sales' => (int) $product->number_of_sales,
                    'cost_price' => $this->formatCurrency($product->cost_price),
                    'selling_price' => $this->formatCurrency($product->selling_price),
                    'stock_value' => $this->formatCurrency($product->current_stock * $product->cost_price),
                    'last_sold_at' => $product->last_sold_at,
                    'days_since_last_sale' => $daysSinceLastSale,
                ];
            });

            return $this->formatReportData([
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ],
                'slow_moving_products' => $products,
            ]);
        });
    }

    /**
     * Get product performance report.
     */
    public function getProductPerformance(string $productId, ?string $startDate = null, ?string $endDate = null): array
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $cacheKey = $this->getCacheKey('product_performance', [
            'product' => $productId,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ]);

        return $this->cacheReport($cacheKey, function () use ($productId, $start, $end) {
            $product = Product::find($productId);

            if (! $product) {
                return $this->formatReportData([
                    'error' => 'Product not found',
                ]);
            }

            // Verify access to product's branch
            $accessibleBranchIds = $this->getAccessibleBranches();
            if (! $accessibleBranchIds->contains($product->branch_id)) {
                return $this->formatReportData([
                    'error' => 'Access denied to this product',
                ]);
            }

            // Get sales data
            $salesData = SaleItem::query()
                ->whereHas('sale', function ($query) use ($start, $end) {
                    $query->whereBetween('sale_date', [$start, $end])
                        ->whereNull('cancelled_at');
                })
                ->where('product_id', $productId)
                ->selectRaw('
                    COUNT(DISTINCT sale_id) as number_of_sales,
                    SUM(quantity) as total_quantity_sold,
                    SUM(total) as total_revenue,
                    SUM(total - (unit_cost * quantity)) as total_profit,
                    AVG(unit_price) as average_selling_price,
                    MIN(unit_price) as min_selling_price,
                    MAX(unit_price) as max_selling_price
                ')
                ->first();

            // Daily sales breakdown
            $dailySales = SaleItem::query()
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sale_items.product_id', $productId)
                ->whereBetween('sales.sale_date', [$start, $end])
                ->whereNull('sales.cancelled_at')
                ->selectRaw('
                    DATE(sales.sale_date) as date,
                    SUM(sale_items.quantity) as quantity_sold,
                    SUM(sale_items.total) as revenue
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'quantity_sold' => (int) $item->quantity_sold,
                        'revenue' => $this->formatCurrency($item->revenue),
                    ];
                });

            return $this->formatReportData([
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ],
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'current_stock' => $product->quantity,
                    'cost_price' => $this->formatCurrency($product->cost_price),
                    'selling_price' => $this->formatCurrency($product->selling_price),
                ],
                'performance' => [
                    'number_of_sales' => $salesData->number_of_sales ?? 0,
                    'total_quantity_sold' => $salesData->total_quantity_sold ?? 0,
                    'total_revenue' => $this->formatCurrency($salesData->total_revenue ?? 0),
                    'total_profit' => $this->formatCurrency($salesData->total_profit ?? 0),
                    'profit_margin' => $salesData->total_revenue > 0
                        ? round(($salesData->total_profit / $salesData->total_revenue) * 100, 2)
                        : 0,
                    'average_selling_price' => $this->formatCurrency($salesData->average_selling_price ?? 0),
                    'min_selling_price' => $this->formatCurrency($salesData->min_selling_price ?? 0),
                    'max_selling_price' => $this->formatCurrency($salesData->max_selling_price ?? 0),
                ],
                'daily_breakdown' => $dailySales,
            ]);
        });
    }

    /**
     * Get low stock products report.
     */
    public function getLowStockProducts(?string $branchId = null, int $threshold = 10): array
    {
        $cacheKey = $this->getCacheKey('low_stock_products', [
            'branch' => $branchId,
            'threshold' => $threshold,
        ]);

        return $this->cacheReport($cacheKey, function () use ($branchId, $threshold) {
            $query = Product::query()
                ->select([
                    'id',
                    'name',
                    'sku',
                    'quantity',
                    'reorder_level',
                    'cost_price',
                    'selling_price',
                    'last_sold_at',
                ])
                ->where(function ($q) use ($threshold) {
                    $q->whereRaw('quantity <= reorder_level')
                        ->orWhere('quantity', '<=', $threshold);
                });

            $query = $this->applyBranchFilter($query, $branchId);

            $products = $query->orderBy('quantity', 'asc')
                ->get()
                ->map(function ($product) {
                    $stockStatus = 'low';
                    if ($product->quantity <= 0) {
                        $stockStatus = 'out_of_stock';
                    } elseif ($product->quantity <= $product->reorder_level) {
                        $stockStatus = 'below_reorder_level';
                    }

                    return [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'current_stock' => (int) $product->quantity,
                        'reorder_level' => (int) $product->reorder_level,
                        'stock_status' => $stockStatus,
                        'cost_price' => $this->formatCurrency($product->cost_price),
                        'selling_price' => $this->formatCurrency($product->selling_price),
                        'last_sold_at' => $product->last_sold_at,
                    ];
                });

            $summary = [
                'out_of_stock' => $products->where('stock_status', 'out_of_stock')->count(),
                'below_reorder_level' => $products->where('stock_status', 'below_reorder_level')->count(),
                'low_stock' => $products->where('stock_status', 'low')->count(),
                'total_products' => $products->count(),
            ];

            return $this->formatReportData([
                'summary' => $summary,
                'products' => $products->values(),
            ]);
        });
    }

    /**
     * Get product category performance.
     */
    public function getCategoryPerformance(?string $startDate = null, ?string $endDate = null, ?string $branchId = null): array
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $cacheKey = $this->getCacheKey('category_performance', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'branch' => $branchId,
        ]);

        return $this->cacheReport($cacheKey, function () use ($start, $end, $branchId) {
            $query = SaleItem::query()
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->whereHas('sale', function ($saleQuery) use ($start, $end, $branchId) {
                    $saleQuery->whereBetween('sale_date', [$start, $end])
                        ->whereNull('cancelled_at');
                    $this->applyBranchFilter($saleQuery, $branchId);
                })
                ->select([
                    'categories.id as category_id',
                    'categories.name as category_name',
                    DB::raw('COUNT(DISTINCT sale_items.product_id) as number_of_products'),
                    DB::raw('SUM(sale_items.quantity) as total_quantity_sold'),
                    DB::raw('COUNT(DISTINCT sale_items.sale_id) as number_of_sales'),
                    DB::raw('SUM(sale_items.total) as total_revenue'),
                    DB::raw('SUM(sale_items.total - (sale_items.unit_cost * sale_items.quantity)) as total_profit'),
                ])
                ->groupBy('categories.id', 'categories.name')
                ->orderByDesc('total_revenue')
                ->get();

            $categories = $query->map(function ($item) {
                return [
                    'category_id' => $item->category_id,
                    'category_name' => $item->category_name,
                    'number_of_products' => (int) $item->number_of_products,
                    'total_quantity_sold' => (int) $item->total_quantity_sold,
                    'number_of_sales' => (int) $item->number_of_sales,
                    'total_revenue' => $this->formatCurrency($item->total_revenue),
                    'total_profit' => $this->formatCurrency($item->total_profit),
                    'profit_margin' => $item->total_revenue > 0
                        ? round(($item->total_profit / $item->total_revenue) * 100, 2)
                        : 0,
                ];
            });

            return $this->formatReportData([
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ],
                'categories' => $categories,
            ]);
        });
    }
}
