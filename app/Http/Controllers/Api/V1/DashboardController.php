<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Reports\InventoryReportService;
use App\Services\Reports\ProductReportService;
use App\Services\Reports\SalesReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        protected SalesReportService $salesReportService,
        protected ProductReportService $productReportService,
        protected InventoryReportService $inventoryReportService
    ) {}

    /**
     * Get comprehensive dashboard statistics.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $branchId = $request->input('branch_id');

        // Get today's sales
        $todaySales = $this->salesReportService->getDailyReport(null, $branchId);

        // Get this week's comparison
        $weekComparison = $this->salesReportService->getComparisonReport('week', $branchId);

        // Get this month's comparison
        $monthComparison = $this->salesReportService->getComparisonReport('month', $branchId);

        // Get low stock alerts
        $stockAlerts = $this->inventoryReportService->getStockAlerts($branchId);

        // Get top 5 selling products this week
        $topProducts = $this->productReportService->getTopSellingProducts(
            now()->startOfWeek()->toDateString(),
            now()->endOfWeek()->toDateString(),
            $branchId,
            5
        );

        // Get inventory valuation summary
        $inventoryValuation = $this->inventoryReportService->getInventoryValuation($branchId);

        return response()->json([
            'success' => true,
            'data' => [
                'today' => $todaySales['data'],
                'week_comparison' => $weekComparison['data'],
                'month_comparison' => $monthComparison['data'],
                'stock_alerts' => $stockAlerts['data']['summary'],
                'top_products' => $topProducts['data']['top_products'],
                'inventory_summary' => $inventoryValuation['data']['summary'],
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get sales overview statistics.
     */
    public function salesOverview(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'period' => 'nullable|string|in:today,week,month',
        ]);

        $branchId = $request->input('branch_id');
        $period = $request->input('period', 'today');

        $currentData = match ($period) {
            'today' => $this->salesReportService->getDailyReport(null, $branchId),
            'week' => $this->salesReportService->getWeeklyReport(null, $branchId),
            'month' => $this->salesReportService->getMonthlyReport(null, null, $branchId),
            default => $this->salesReportService->getDailyReport(null, $branchId),
        };

        $comparison = $this->salesReportService->getComparisonReport($period, $branchId);

        return response()->json([
            'success' => true,
            'data' => [
                'current' => $currentData['data'],
                'comparison' => $comparison['data']['comparison'],
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get inventory overview statistics.
     */
    public function inventoryOverview(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $branchId = $request->input('branch_id');

        $valuation = $this->inventoryReportService->getInventoryValuation($branchId);
        $alerts = $this->inventoryReportService->getStockAlerts($branchId);
        $lowStock = $this->productReportService->getLowStockProducts($branchId, 10);

        return response()->json([
            'success' => true,
            'data' => [
                'valuation' => $valuation['data']['summary'],
                'alerts' => $alerts['data']['summary'],
                'low_stock_products' => $lowStock['data']['products'],
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get product insights.
     */
    public function productInsights(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'days' => 'nullable|integer|min:1|max:90',
        ]);

        $branchId = $request->input('branch_id');
        $days = $request->input('days', 7);

        $startDate = now()->subDays($days)->toDateString();
        $endDate = now()->toDateString();

        $topSelling = $this->productReportService->getTopSellingProducts(
            $startDate,
            $endDate,
            $branchId,
            10
        );

        $slowMoving = $this->productReportService->getSlowMovingProducts(
            $startDate,
            $endDate,
            $branchId,
            10
        );

        $categoryPerformance = $this->productReportService->getCategoryPerformance(
            $startDate,
            $endDate,
            $branchId
        );

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days' => $days,
                ],
                'top_selling' => $topSelling['data']['top_products'],
                'slow_moving' => $slowMoving['data']['slow_moving_products'],
                'category_performance' => $categoryPerformance['data']['categories'],
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get quick stats for mobile app.
     */
    public function quickStats(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $branchId = $request->input('branch_id');

        $todaySales = $this->salesReportService->getDailyReport(null, $branchId);
        $weekSales = $this->salesReportService->getWeeklyReport(null, $branchId);
        $stockAlerts = $this->inventoryReportService->getStockAlerts($branchId);
        $inventoryVal = $this->inventoryReportService->getInventoryValuation($branchId);

        return response()->json([
            'success' => true,
            'data' => [
                'today_sales_count' => $todaySales['data']['summary']['total_sales'] ?? 0,
                'today_revenue' => $todaySales['data']['summary']['total_revenue'] ?? 0,
                'week_sales_count' => $weekSales['data']['summary']['total_sales'] ?? 0,
                'week_revenue' => $weekSales['data']['summary']['total_revenue'] ?? 0,
                'critical_alerts' => $stockAlerts['data']['summary']['critical'] ?? 0,
                'total_stock_value' => $inventoryVal['data']['summary']['total_cost_value'] ?? 0,
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * Get teller-specific statistics for cashiers.
     */
    public function tellerStats(Request $request): JsonResponse
    {
        $user = $request->user();
        $shopId = $request->header('X-Shop-ID') ?? $user->shop_id;
        $today = now()->startOfDay();

        // Get today's sales for this teller
        $todaySales = \App\Models\Sale::where('served_by', $user->id)
            ->where('shop_id', $shopId)
            ->where('created_at', '>=', $today)
            ->whereNull('cancelled_at');

        $todaySalesCount = $todaySales->count();
        $todayRevenue = $todaySales->sum('total_amount');

        // Get active shift
        $activeShift = \App\Models\Shift::forUser($user->id)
            ->forShop($shopId)
            ->active()
            ->first();

        // Calculate shift duration
        $shiftDuration = null;
        if ($activeShift) {
            $shiftDuration = $activeShift->formatted_duration;
        }

        // Get sales target (default: MWK 500,000)
        $salesTarget = 500000;

        // Calculate progress
        $targetProgress = $salesTarget > 0
            ? round(($todayRevenue / $salesTarget) * 100, 2)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'today_sales' => $todaySalesCount,
                'today_revenue' => $todayRevenue,
                'transaction_count' => $todaySalesCount,
                'sales_target' => $salesTarget,
                'target_progress' => $targetProgress,
                'shift_active' => ! is_null($activeShift),
                'shift_start_time' => $activeShift?->start_time,
                'shift_duration' => $shiftDuration,
                'cash_in_drawer' => $activeShift?->current_balance ?? 0,
            ],
        ]);
    }
}
