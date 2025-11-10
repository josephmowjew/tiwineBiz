<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Reports\ProductReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductReportController extends Controller
{
    public function __construct(
        protected ProductReportService $productReportService
    ) {}

    /**
     * Get top selling products report.
     */
    public function topSelling(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $report = $this->productReportService->getTopSellingProducts(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('branch_id'),
            $request->input('limit', 20)
        );

        return response()->json($report);
    }

    /**
     * Get slow moving products report.
     */
    public function slowMoving(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $report = $this->productReportService->getSlowMovingProducts(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('branch_id'),
            $request->input('limit', 20)
        );

        return response()->json($report);
    }

    /**
     * Get product performance report.
     */
    public function performance(Request $request, string $productId): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $report = $this->productReportService->getProductPerformance(
            $productId,
            $request->input('start_date'),
            $request->input('end_date')
        );

        return response()->json($report);
    }

    /**
     * Get low stock products report.
     */
    public function lowStock(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'threshold' => 'nullable|integer|min:0|max:1000',
        ]);

        $report = $this->productReportService->getLowStockProducts(
            $request->input('branch_id'),
            $request->input('threshold', 10)
        );

        return response()->json($report);
    }

    /**
     * Get category performance report.
     */
    public function categoryPerformance(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $report = $this->productReportService->getCategoryPerformance(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('branch_id')
        );

        return response()->json($report);
    }
}
