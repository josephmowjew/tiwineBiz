<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Reports\InventoryReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryReportController extends Controller
{
    public function __construct(
        protected InventoryReportService $inventoryReportService
    ) {}

    /**
     * Get inventory valuation report.
     */
    public function valuation(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $report = $this->inventoryReportService->getInventoryValuation(
            $request->input('branch_id')
        );

        return response()->json($report);
    }

    /**
     * Get stock movements report.
     */
    public function movements(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'branch_id' => 'nullable|uuid|exists:branches,id',
            'movement_type' => 'nullable|string|in:purchase,sale,adjustment,transfer,return,damage,expired',
        ]);

        $report = $this->inventoryReportService->getStockMovements(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('branch_id'),
            $request->input('movement_type')
        );

        return response()->json($report);
    }

    /**
     * Get inventory aging report.
     */
    public function aging(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $report = $this->inventoryReportService->getInventoryAging(
            $request->input('branch_id')
        );

        return response()->json($report);
    }

    /**
     * Get stock alerts report.
     */
    public function alerts(Request $request): JsonResponse
    {
        $request->validate([
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $report = $this->inventoryReportService->getStockAlerts(
            $request->input('branch_id')
        );

        return response()->json($report);
    }

    /**
     * Get inventory turnover report.
     */
    public function turnover(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'branch_id' => 'nullable|uuid|exists:branches,id',
        ]);

        $report = $this->inventoryReportService->getInventoryTurnover(
            $request->input('start_date'),
            $request->input('end_date'),
            $request->input('branch_id')
        );

        return response()->json($report);
    }
}
