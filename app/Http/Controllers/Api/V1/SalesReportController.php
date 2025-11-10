<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Reports\SalesReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesReportController extends Controller
{
    public function __construct(
        protected SalesReportService $salesReportService
    ) {}

    /**
     * Get sales summary report for a date range.
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $report = $this->salesReportService->getSummary(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('branch_id')
        );

        return response()->json($report);
    }

    /**
     * Get daily sales report.
     */
    public function daily(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $report = $this->salesReportService->getDailyReport(
            $request->input('date'),
            $request->input('branch_id')
        );

        return response()->json($report);
    }

    /**
     * Get weekly sales report.
     */
    public function weekly(Request $request): JsonResponse
    {
        $request->validate([
            'week' => 'nullable|date',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $report = $this->salesReportService->getWeeklyReport(
            $request->input('week'),
            $request->input('branch_id')
        );

        return response()->json($report);
    }

    /**
     * Get monthly sales report.
     */
    public function monthly(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $report = $this->salesReportService->getMonthlyReport(
            $request->input('month'),
            $request->input('year'),
            $request->input('branch_id')
        );

        return response()->json($report);
    }

    /**
     * Get sales comparison report.
     */
    public function comparison(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'nullable|string|in:today,week,month',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $report = $this->salesReportService->getComparisonReport(
            $request->input('period', 'week'),
            $request->input('branch_id')
        );

        return response()->json($report);
    }

    /**
     * Get hourly sales report.
     */
    public function hourly(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $report = $this->salesReportService->getHourlySales(
            $request->input('date'),
            $request->input('branch_id')
        );

        return response()->json($report);
    }

    /**
     * Get top customers report.
     */
    public function topCustomers(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $report = $this->salesReportService->getTopCustomers(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('branch_id'),
            $request->input('limit', 10)
        );

        return response()->json($report);
    }
}
