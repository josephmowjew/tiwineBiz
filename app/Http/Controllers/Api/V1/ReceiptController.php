<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\ReceiptService;
use App\Traits\HasBranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReceiptController extends Controller
{
    use HasBranchScope;

    public function __construct(
        protected ReceiptService $receiptService
    ) {}

    /**
     * View receipt as PDF in browser.
     */
    public function view(Request $request, string $saleId): Response|JsonResponse
    {
        $request->validate([
            'locale' => 'nullable|string|in:en,ny',
        ]);

        $sale = $this->findSaleOrFail($request, $saleId);

        if (! $sale) {
            return response()->json([
                'message' => 'Sale not found or you do not have access.',
            ], 404);
        }

        $locale = $request->input('locale', 'en');

        return $this->receiptService->streamPdf($sale, $locale);
    }

    /**
     * Download receipt as PDF file.
     */
    public function download(Request $request, string $saleId): Response|JsonResponse
    {
        $request->validate([
            'locale' => 'nullable|string|in:en,ny',
        ]);

        $sale = $this->findSaleOrFail($request, $saleId);

        if (! $sale) {
            return response()->json([
                'message' => 'Sale not found or you do not have access.',
            ], 404);
        }

        $locale = $request->input('locale', 'en');

        return $this->receiptService->downloadPdf($sale, $locale);
    }

    /**
     * Get receipt as HTML (for email or preview).
     */
    public function html(Request $request, string $saleId): JsonResponse
    {
        $request->validate([
            'locale' => 'nullable|string|in:en,ny',
        ]);

        $sale = $this->findSaleOrFail($request, $saleId);

        if (! $sale) {
            return response()->json([
                'message' => 'Sale not found or you do not have access.',
            ], 404);
        }

        $locale = $request->input('locale', 'en');
        $html = $this->receiptService->generateHtml($sale, $locale);

        return response()->json([
            'success' => true,
            'data' => [
                'html' => $html,
                'sale_number' => $sale->sale_number,
            ],
        ]);
    }

    /**
     * Email receipt to customer.
     */
    public function email(Request $request, string $saleId): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'locale' => 'nullable|string|in:en,ny',
        ]);

        $sale = $this->findSaleOrFail($request, $saleId);

        if (! $sale) {
            return response()->json([
                'message' => 'Sale not found or you do not have access.',
            ], 404);
        }

        // TODO: Implement email sending in Phase 4 (Notifications System)
        // For now, return a pending response

        return response()->json([
            'success' => true,
            'message' => 'Email functionality will be available in the next release.',
            'data' => [
                'email' => $request->email,
                'sale_number' => $sale->sale_number,
                'status' => 'pending',
            ],
        ], 202);
    }

    /**
     * Print receipt (returns print-optimized HTML).
     */
    public function print(Request $request, string $saleId): Response|JsonResponse
    {
        $request->validate([
            'locale' => 'nullable|string|in:en,ny',
        ]);

        $sale = $this->findSaleOrFail($request, $saleId);

        if (! $sale) {
            return response()->json([
                'message' => 'Sale not found or you do not have access.',
            ], 404);
        }

        $locale = $request->input('locale', 'en');
        $html = $this->receiptService->generateHtml($sale, $locale);

        return response($html)->header('Content-Type', 'text/html');
    }

    /**
     * Find sale and verify access.
     */
    protected function findSaleOrFail(Request $request, string $saleId): ?Sale
    {
        $user = $request->user();

        // Get accessible branch IDs
        $accessibleBranchIds = $this->getAccessibleBranchIds();

        if ($accessibleBranchIds->isEmpty()) {
            return null;
        }

        // Find sale with access check
        return Sale::query()
            ->whereIn('branch_id', $accessibleBranchIds)
            ->where('id', $saleId)
            ->with([
                'shop',
                'branch',
                'customer',
                'items.product',
                'servedBy',
            ])
            ->first();
    }
}
