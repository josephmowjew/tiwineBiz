<?php

namespace App\Services\Reports;

use App\Models\Sale;
use Carbon\Carbon;

class SalesReportService extends BaseReportService
{
    /**
     * Get sales summary for a date range.
     */
    public function getSummary(?string $startDate = null, ?string $endDate = null, ?string $branchId = null): array
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $cacheKey = $this->getCacheKey('sales_summary', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'branch' => $branchId,
        ]);

        return $this->cacheReport($cacheKey, function () use ($start, $end, $branchId) {
            $query = Sale::whereBetween('sale_date', [$start, $end]);
            $query = $this->applyBranchFilter($query, $branchId);

            $sales = $query->get();

            $totalSales = $sales->count();
            $totalRevenue = $this->formatCurrency($sales->sum('total_amount'));
            $totalCost = $this->formatCurrency($sales->sum('total_cost'));
            $totalProfit = $this->formatCurrency($totalRevenue - $totalCost);
            $profitMargin = $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0;

            // Payment method breakdown
            $paymentMethods = $sales->groupBy('payment_method')->map(function ($items, $method) {
                return [
                    'method' => $method,
                    'count' => $items->count(),
                    'amount' => $this->formatCurrency($items->sum('total_amount')),
                ];
            })->values();

            // Daily breakdown
            $dailyBreakdown = $sales->groupBy(function ($sale) {
                return Carbon::parse($sale->sale_date)->format('Y-m-d');
            })->map(function ($items, $date) {
                return [
                    'date' => $date,
                    'count' => $items->count(),
                    'revenue' => $this->formatCurrency($items->sum('total_amount')),
                    'profit' => $this->formatCurrency($items->sum('total_amount') - $items->sum('total_cost')),
                ];
            })->values();

            return $this->formatReportData([
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                    'days' => $start->diffInDays($end) + 1,
                ],
                'summary' => [
                    'total_sales' => $totalSales,
                    'total_revenue' => $totalRevenue,
                    'total_cost' => $totalCost,
                    'total_profit' => $totalProfit,
                    'profit_margin' => $profitMargin,
                    'average_sale' => $totalSales > 0 ? $this->formatCurrency($totalRevenue / $totalSales) : 0,
                ],
                'payment_methods' => $paymentMethods,
                'daily_breakdown' => $dailyBreakdown,
            ]);
        });
    }

    /**
     * Get daily sales report.
     */
    public function getDailyReport(?string $date = null, ?string $branchId = null): array
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        return $this->getSummary(
            $date->toDateString(),
            $date->toDateString(),
            $branchId
        );
    }

    /**
     * Get weekly sales report.
     */
    public function getWeeklyReport(?string $week = null, ?string $branchId = null): array
    {
        $date = $week ? Carbon::parse($week) : Carbon::now();
        $startOfWeek = $date->copy()->startOfWeek();
        $endOfWeek = $date->copy()->endOfWeek();

        return $this->getSummary(
            $startOfWeek->toDateString(),
            $endOfWeek->toDateString(),
            $branchId
        );
    }

    /**
     * Get monthly sales report.
     */
    public function getMonthlyReport(?int $month = null, ?int $year = null, ?string $branchId = null): array
    {
        $date = Carbon::create($year ?? Carbon::now()->year, $month ?? Carbon::now()->month, 1);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $report = $this->getSummary(
            $startOfMonth->toDateString(),
            $endOfMonth->toDateString(),
            $branchId
        );

        // Add weekly breakdown for the month
        $weeklyBreakdown = [];
        $currentWeekStart = $startOfMonth->copy();

        while ($currentWeekStart->lte($endOfMonth)) {
            $currentWeekEnd = $currentWeekStart->copy()->endOfWeek();
            if ($currentWeekEnd->gt($endOfMonth)) {
                $currentWeekEnd = $endOfMonth->copy();
            }

            $weekData = $this->getSummary(
                $currentWeekStart->toDateString(),
                $currentWeekEnd->toDateString(),
                $branchId
            );

            $weeklyBreakdown[] = [
                'week_start' => $currentWeekStart->toDateString(),
                'week_end' => $currentWeekEnd->toDateString(),
                'summary' => $weekData['data']['summary'],
            ];

            $currentWeekStart->addWeek()->startOfWeek();
        }

        $report['data']['weekly_breakdown'] = $weeklyBreakdown;

        return $report;
    }

    /**
     * Get sales comparison report.
     */
    public function getComparisonReport(string $period = 'week', ?string $branchId = null): array
    {
        $cacheKey = $this->getCacheKey('sales_comparison', [
            'period' => $period,
            'branch' => $branchId,
        ]);

        return $this->cacheReport($cacheKey, function () use ($period, $branchId) {
            // Current period
            $currentStart = match ($period) {
                'today' => Carbon::today(),
                'week' => Carbon::now()->startOfWeek(),
                'month' => Carbon::now()->startOfMonth(),
                default => Carbon::now()->startOfWeek(),
            };

            $currentEnd = match ($period) {
                'today' => Carbon::today()->endOfDay(),
                'week' => Carbon::now()->endOfWeek(),
                'month' => Carbon::now()->endOfMonth(),
                default => Carbon::now()->endOfWeek(),
            };

            // Previous period
            [$previousStart, $previousEnd] = $this->getComparisonDates($currentStart, $currentEnd);

            $currentData = $this->getSummary(
                $currentStart->toDateString(),
                $currentEnd->toDateString(),
                $branchId
            );

            $previousData = $this->getSummary(
                $previousStart->toDateString(),
                $previousEnd->toDateString(),
                $branchId
            );

            $currentSummary = $currentData['data']['summary'];
            $previousSummary = $previousData['data']['summary'];

            return $this->formatReportData([
                'period' => $period,
                'current' => [
                    'start_date' => $currentStart->toDateString(),
                    'end_date' => $currentEnd->toDateString(),
                    'summary' => $currentSummary,
                ],
                'previous' => [
                    'start_date' => $previousStart->toDateString(),
                    'end_date' => $previousEnd->toDateString(),
                    'summary' => $previousSummary,
                ],
                'comparison' => [
                    'revenue_change' => $this->calculatePercentageChange(
                        $currentSummary['total_revenue'],
                        $previousSummary['total_revenue']
                    ),
                    'profit_change' => $this->calculatePercentageChange(
                        $currentSummary['total_profit'],
                        $previousSummary['total_profit']
                    ),
                    'sales_count_change' => $this->calculatePercentageChange(
                        $currentSummary['total_sales'],
                        $previousSummary['total_sales']
                    ),
                ],
            ]);
        });
    }

    /**
     * Get sales by hour of day (for today or specific date).
     */
    public function getHourlySales(?string $date = null, ?string $branchId = null): array
    {
        $date = $date ? Carbon::parse($date) : Carbon::today();

        $cacheKey = $this->getCacheKey('sales_hourly', [
            'date' => $date->toDateString(),
            'branch' => $branchId,
        ]);

        return $this->cacheReport($cacheKey, function () use ($date, $branchId) {
            $query = Sale::whereDate('sale_date', $date);
            $query = $this->applyBranchFilter($query, $branchId);

            $sales = $query->get();

            $hourlyData = $sales->groupBy(function ($sale) {
                return Carbon::parse($sale->created_at)->format('H');
            })->map(function ($items, $hour) {
                return [
                    'hour' => (int) $hour,
                    'count' => $items->count(),
                    'revenue' => $this->formatCurrency($items->sum('total_amount')),
                ];
            })->values()->sortBy('hour')->values();

            return $this->formatReportData([
                'date' => $date->toDateString(),
                'hourly_sales' => $hourlyData,
                'peak_hour' => $hourlyData->sortByDesc('revenue')->first(),
            ]);
        });
    }

    /**
     * Get top customers by revenue.
     */
    public function getTopCustomers(?string $startDate = null, ?string $endDate = null, ?string $branchId = null, int $limit = 10): array
    {
        [$start, $end] = $this->parseDateRange($startDate, $endDate);

        $cacheKey = $this->getCacheKey('top_customers', [
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
            'branch' => $branchId,
            'limit' => $limit,
        ]);

        return $this->cacheReport($cacheKey, function () use ($start, $end, $branchId, $limit) {
            $query = Sale::with('customer')
                ->whereBetween('sale_date', [$start, $end])
                ->whereNotNull('customer_id');

            $query = $this->applyBranchFilter($query, $branchId);

            $topCustomers = $query->get()
                ->groupBy('customer_id')
                ->map(function ($sales) {
                    $customer = $sales->first()->customer;

                    return [
                        'customer_id' => $customer->id,
                        'customer_name' => $customer->name,
                        'customer_phone' => $customer->phone,
                        'total_purchases' => $sales->count(),
                        'total_spent' => $this->formatCurrency($sales->sum('total_amount')),
                        'average_purchase' => $this->formatCurrency($sales->avg('total_amount')),
                    ];
                })
                ->sortByDesc('total_spent')
                ->take($limit)
                ->values();

            return $this->formatReportData([
                'period' => [
                    'start_date' => $start->toDateString(),
                    'end_date' => $end->toDateString(),
                ],
                'top_customers' => $topCustomers,
            ]);
        });
    }

    /**
     * Generate PDF export for sales report.
     */
    public function generatePdfExport(string $type, array $data): \Barryvdh\DomPDF\PDF
    {
        $viewData = [
            'type' => $type,
            'data' => $data,
            'generated_at' => now()->format('Y-m-d H:i:s'),
        ];

        return $this->generatePdf('reports.sales-pdf', $viewData, "sales-{$type}.pdf");
    }

    /**
     * Prepare data for Excel export.
     */
    public function prepareExcelExport(string $type, array $data): array
    {
        return match ($type) {
            'summary' => $this->prepareSummaryExcelData($data),
            'daily' => $this->prepareDailyExcelData($data),
            'weekly' => $this->prepareWeeklyExcelData($data),
            'monthly' => $this->prepareMonthlyExcelData($data),
            default => ['data' => [], 'headings' => [], 'title' => 'Sales Report'],
        };
    }

    /**
     * Prepare summary report data for Excel.
     */
    protected function prepareSummaryExcelData(array $data): array
    {
        $rows = [
            ['Total Sales', $data['total_sales'] ?? 0],
            ['Total Revenue', $data['total_revenue'] ?? 0],
            ['Average Transaction', $data['average_transaction_value'] ?? 0],
            ['Total Items Sold', $data['total_items_sold'] ?? 0],
        ];

        return [
            'data' => $rows,
            'headings' => ['Metric', 'Value'],
            'title' => 'Sales Summary',
        ];
    }

    /**
     * Prepare daily report data for Excel.
     */
    protected function prepareDailyExcelData(array $data): array
    {
        $rows = [];

        if (isset($data['hourly_breakdown'])) {
            foreach ($data['hourly_breakdown'] as $hour) {
                $rows[] = [
                    $hour['hour'] ?? '',
                    $hour['sales_count'] ?? 0,
                    $hour['revenue'] ?? 0,
                ];
            }
        }

        return [
            'data' => $rows,
            'headings' => ['Hour', 'Sales Count', 'Revenue'],
            'title' => 'Daily Sales Report',
        ];
    }

    /**
     * Prepare weekly report data for Excel.
     */
    protected function prepareWeeklyExcelData(array $data): array
    {
        $rows = [];

        if (isset($data['daily_breakdown'])) {
            foreach ($data['daily_breakdown'] as $day) {
                $rows[] = [
                    $day['date'] ?? '',
                    $day['sales_count'] ?? 0,
                    $day['revenue'] ?? 0,
                ];
            }
        }

        return [
            'data' => $rows,
            'headings' => ['Date', 'Sales Count', 'Revenue'],
            'title' => 'Weekly Sales Report',
        ];
    }

    /**
     * Prepare monthly report data for Excel.
     */
    protected function prepareMonthlyExcelData(array $data): array
    {
        $rows = [];

        if (isset($data['daily_breakdown'])) {
            foreach ($data['daily_breakdown'] as $day) {
                $rows[] = [
                    $day['date'] ?? '',
                    $day['sales_count'] ?? 0,
                    $day['revenue'] ?? 0,
                ];
            }
        }

        return [
            'data' => $rows,
            'headings' => ['Date', 'Sales Count', 'Revenue'],
            'title' => 'Monthly Sales Report',
        ];
    }
}
