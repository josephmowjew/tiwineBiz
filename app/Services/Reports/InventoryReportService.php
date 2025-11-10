<?php

namespace App\Services\Reports;

use App\Models\Product;
use App\Models\StockMovement;
use Carbon\Carbon;

class InventoryReportService extends BaseReportService
{
    /**
     * Get inventory valuation report.
     */
    public function getInventoryValuation(?string $branchId = null): array
    {
        $cacheKey = $this->getCacheKey('inventory_valuation', [
            'branch' => $branchId,
        ]);

        return $this->cacheReport($cacheKey, function () use ($branchId) {
            $query = Product::query()
                ->select([
                    'id',
                    'name',
                    'sku',
                    'quantity',
                    'cost_price',
                    'selling_price',
                    'category_id',
                ]);

            $query = $this->applyBranchFilter($query, $branchId);

            $products = $query->with('category:id,name')->get();

            $productsData = $products->map(function ($product) {
                $costValue = $product->quantity * $product->cost_price;
                $sellingValue = $product->quantity * $product->selling_price;
                $potentialProfit = $sellingValue - $costValue;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'category_name' => $product->category?->name,
                    'quantity' => (int) $product->quantity,
                    'cost_price' => $this->formatCurrency($product->cost_price),
                    'selling_price' => $this->formatCurrency($product->selling_price),
                    'cost_value' => $this->formatCurrency($costValue),
                    'selling_value' => $this->formatCurrency($sellingValue),
                    'potential_profit' => $this->formatCurrency($potentialProfit),
                    'profit_margin' => $sellingValue > 0
                        ? round(($potentialProfit / $sellingValue) * 100, 2)
                        : 0,
                ];
            });

            $summary = [
                'total_products' => $products->count(),
                'total_stock_quantity' => $products->sum('quantity'),
                'total_cost_value' => $this->formatCurrency($products->sum(fn ($p) => $p->quantity * $p->cost_price)),
                'total_selling_value' => $this->formatCurrency($products->sum(fn ($p) => $p->quantity * $p->selling_price)),
                'total_potential_profit' => $this->formatCurrency(
                    $products->sum(fn ($p) => ($p->quantity * $p->selling_price) - ($p->quantity * $p->cost_price))
                ),
            ];

            return $this->formatReportData([
                'summary' => $summary,
                'products' => $productsData,
            ]);
        });
    }

    /**
     * Get stock movements report.
     */
    public function getStockMovements(?string $startDate = null, ?string $endDate = null, ?string $branchId = null, ?string $movementType = null): array
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $cacheKey = $this->getCacheKey('stock_movements', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'branch' => $branchId,
            'type' => $movementType,
        ]);

        return $this->cacheReport($cacheKey, function () use ($start, $end, $branchId, $movementType) {
            $query = StockMovement::query()
                ->with(['product:id,name,sku', 'user:id,name'])
                ->whereBetween('movement_date', [$start, $end]);

            $query = $this->applyBranchFilter($query, $branchId);

            if ($movementType) {
                $query->where('movement_type', $movementType);
            }

            $movements = $query->orderByDesc('movement_date')
                ->get()
                ->map(function ($movement) {
                    return [
                        'id' => $movement->id,
                        'movement_date' => $movement->movement_date,
                        'movement_type' => $movement->movement_type,
                        'product_id' => $movement->product_id,
                        'product_name' => $movement->product?->name,
                        'product_sku' => $movement->product?->sku,
                        'quantity' => (int) $movement->quantity,
                        'quantity_before' => (int) $movement->quantity_before,
                        'quantity_after' => (int) $movement->quantity_after,
                        'unit_cost' => $this->formatCurrency($movement->unit_cost ?? 0),
                        'reference_type' => $movement->reference_type,
                        'reference_id' => $movement->reference_id,
                        'notes' => $movement->notes,
                        'created_by' => $movement->user?->name,
                    ];
                });

            // Summary by movement type
            $summary = $movements->groupBy('movement_type')->map(function ($items, $type) {
                $totalQuantity = $items->sum('quantity');

                return [
                    'type' => $type,
                    'count' => $items->count(),
                    'total_quantity' => $totalQuantity,
                ];
            })->values();

            return $this->formatReportData([
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ],
                'summary' => $summary,
                'movements' => $movements,
            ]);
        });
    }

    /**
     * Get inventory aging report.
     */
    public function getInventoryAging(?string $branchId = null): array
    {
        $cacheKey = $this->getCacheKey('inventory_aging', [
            'branch' => $branchId,
        ]);

        return $this->cacheReport($cacheKey, function () use ($branchId) {
            $query = Product::query()
                ->select([
                    'id',
                    'name',
                    'sku',
                    'quantity',
                    'cost_price',
                    'selling_price',
                    'created_at',
                    'last_sold_at',
                    'last_restocked_at',
                ])
                ->where('quantity', '>', 0);

            $query = $this->applyBranchFilter($query, $branchId);

            $products = $query->get()->map(function ($product) {
                $ageInDays = Carbon::parse($product->last_restocked_at ?? $product->created_at)->diffInDays(now());
                $daysSinceLastSale = $product->last_sold_at
                    ? Carbon::parse($product->last_sold_at)->diffInDays(now())
                    : null;

                // Determine age category
                $ageCategory = match (true) {
                    $ageInDays <= 30 => '0-30 days',
                    $ageInDays <= 60 => '31-60 days',
                    $ageInDays <= 90 => '61-90 days',
                    $ageInDays <= 180 => '91-180 days',
                    default => '180+ days',
                };

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => (int) $product->quantity,
                    'cost_price' => $this->formatCurrency($product->cost_price),
                    'stock_value' => $this->formatCurrency($product->quantity * $product->cost_price),
                    'age_in_days' => $ageInDays,
                    'age_category' => $ageCategory,
                    'last_restocked_at' => $product->last_restocked_at ?? $product->created_at,
                    'last_sold_at' => $product->last_sold_at,
                    'days_since_last_sale' => $daysSinceLastSale,
                ];
            });

            // Group by age category
            $ageSummary = $products->groupBy('age_category')->map(function ($items, $category) {
                return [
                    'age_category' => $category,
                    'product_count' => $items->count(),
                    'total_quantity' => $items->sum('quantity'),
                    'total_value' => $this->formatCurrency($items->sum(fn ($p) => $p['quantity'] * $p['cost_price'])),
                ];
            })->values();

            return $this->formatReportData([
                'summary_by_age' => $ageSummary,
                'products' => $products->sortByDesc('age_in_days')->values(),
            ]);
        });
    }

    /**
     * Get stock alerts report.
     */
    public function getStockAlerts(?string $branchId = null): array
    {
        $cacheKey = $this->getCacheKey('stock_alerts', [
            'branch' => $branchId,
        ]);

        return $this->cacheReport($cacheKey, function () use ($branchId) {
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
                ]);

            $query = $this->applyBranchFilter($query, $branchId);

            $products = $query->get()->map(function ($product) {
                $alerts = [];

                // Out of stock
                if ($product->quantity <= 0) {
                    $alerts[] = [
                        'severity' => 'critical',
                        'type' => 'out_of_stock',
                        'message' => 'Product is out of stock',
                    ];
                } elseif ($product->quantity <= $product->reorder_level) {
                    $alerts[] = [
                        'severity' => 'warning',
                        'type' => 'below_reorder_level',
                        'message' => 'Stock is below reorder level',
                    ];
                }

                // Slow moving (no sales in 30+ days)
                if ($product->last_sold_at) {
                    $daysSinceLastSale = Carbon::parse($product->last_sold_at)->diffInDays(now());
                    if ($daysSinceLastSale >= 30 && $product->quantity > 0) {
                        $alerts[] = [
                            'severity' => 'info',
                            'type' => 'slow_moving',
                            'message' => "No sales in {$daysSinceLastSale} days",
                        ];
                    }
                }

                if (empty($alerts)) {
                    return null;
                }

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku,
                    'quantity' => (int) $product->quantity,
                    'reorder_level' => (int) $product->reorder_level,
                    'cost_price' => $this->formatCurrency($product->cost_price),
                    'selling_price' => $this->formatCurrency($product->selling_price),
                    'last_sold_at' => $product->last_sold_at,
                    'alerts' => $alerts,
                ];
            })->filter()->values();

            $alertsSummary = [
                'critical' => $products->filter(fn ($p) => collect($p['alerts'])->contains('severity', 'critical'))->count(),
                'warning' => $products->filter(fn ($p) => collect($p['alerts'])->contains('severity', 'warning'))->count(),
                'info' => $products->filter(fn ($p) => collect($p['alerts'])->contains('severity', 'info'))->count(),
                'total_products_with_alerts' => $products->count(),
            ];

            return $this->formatReportData([
                'summary' => $alertsSummary,
                'products' => $products,
            ]);
        });
    }

    /**
     * Get inventory turnover report.
     */
    public function getInventoryTurnover(?string $startDate = null, ?string $endDate = null, ?string $branchId = null): array
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $cacheKey = $this->getCacheKey('inventory_turnover', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'branch' => $branchId,
        ]);

        return $this->cacheReport($cacheKey, function () use ($start, $end, $branchId) {
            $query = Product::query()
                ->with('category:id,name')
                ->select([
                    'products.id',
                    'products.name',
                    'products.sku',
                    'products.quantity',
                    'products.cost_price',
                    'products.category_id',
                ])
                ->leftJoin('sale_items', 'products.id', '=', 'sale_items.product_id')
                ->leftJoin('sales', function ($join) use ($start, $end) {
                    $join->on('sale_items.sale_id', '=', 'sales.id')
                        ->whereBetween('sales.sale_date', [$start, $end])
                        ->whereNull('sales.cancelled_at');
                })
                ->selectRaw('COALESCE(SUM(sale_items.quantity), 0) as quantity_sold')
                ->selectRaw('COALESCE(SUM(sale_items.total - (sale_items.unit_cost * sale_items.quantity)), 0) as total_profit');

            $query = $this->applyBranchFilter($query, $branchId);

            $products = $query->groupBy(
                'products.id',
                'products.name',
                'products.sku',
                'products.quantity',
                'products.cost_price',
                'products.category_id'
            )
                ->get()
                ->map(function ($product) use ($start, $end) {
                    $averageInventory = ($product->quantity + $product->quantity_sold) / 2;
                    $turnoverRatio = $averageInventory > 0
                        ? round($product->quantity_sold / $averageInventory, 2)
                        : 0;

                    $daysInPeriod = $start->diffInDays($end) + 1;
                    $daysToSell = $turnoverRatio > 0
                        ? round($daysInPeriod / $turnoverRatio, 0)
                        : null;

                    return [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'product_sku' => $product->sku,
                        'category_name' => $product->category?->name,
                        'current_stock' => (int) $product->quantity,
                        'quantity_sold' => (int) $product->quantity_sold,
                        'average_inventory' => round($averageInventory, 2),
                        'turnover_ratio' => $turnoverRatio,
                        'days_to_sell' => $daysToSell,
                        'stock_value' => $this->formatCurrency($product->quantity * $product->cost_price),
                        'total_profit' => $this->formatCurrency($product->total_profit),
                    ];
                })
                ->sortByDesc('turnover_ratio')
                ->values();

            return $this->formatReportData([
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'days' => $start->diffInDays($end) + 1,
                ],
                'products' => $products,
            ]);
        });
    }
}
