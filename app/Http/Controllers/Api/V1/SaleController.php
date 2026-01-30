<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sale\RefundSaleRequest;
use App\Http\Requests\Sale\StoreSaleRequest;
use App\Http\Requests\Sale\UpdateSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\MraEisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    /**
     * Display a listing of sales scoped to user's shops.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $query = Sale::query()->whereIn('shop_id', $shopIds);

        // Filter by specific shop if provided
        if ($request->filled('shop_id')) {
            if (in_array($request->shop_id, $shopIds->toArray())) {
                $query->where('shop_id', $request->shop_id);
            } else {
                return response()->json([
                    'message' => 'You do not have access to this shop.',
                ], 403);
            }
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by sale type
        if ($request->filled('sale_type')) {
            $query->where('sale_type', $request->sale_type);
        }

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('sale_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('sale_date', '<=', $request->to_date);
        }

        // Filter by cancelled status
        if ($request->filled('include_cancelled')) {
            if (! $request->boolean('include_cancelled')) {
                $query->whereNull('cancelled_at');
            }
        } else {
            $query->whereNull('cancelled_at');
        }

        // Search by sale number
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'customer')) {
            $query->with('customer');
        }
        if (str_contains($includes, 'items')) {
            $query->with('items.product');
        }
        if (str_contains($includes, 'servedBy')) {
            $query->with('servedBy');
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'sale_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $sales = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => SaleResource::collection($sales->items()),
            'meta' => [
                'current_page' => $sales->currentPage(),
                'last_page' => $sales->lastPage(),
                'per_page' => $sales->perPage(),
                'total' => $sales->total(),
            ],
        ]);
    }

    /**
     * Store a newly created sale with items.
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verify user has access to the shop
        $hasAccess = $user->shops()->where('shops.id', $request->shop_id)->exists()
            || $user->ownedShops()->where('id', $request->shop_id)->exists();

        if (! $hasAccess) {
            return response()->json([
                'message' => 'You do not have access to this shop.',
            ], 403);
        }

        DB::beginTransaction();

        try {
            // Generate sale number
            $saleNumber = 'SALE-'.strtoupper(uniqid());

            // Create sale
            $saleData = $request->except('items');
            $saleData['sale_number'] = $saleNumber;
            $saleData['served_by'] = $user->id;
            $saleData['sale_date'] = $saleData['sale_date'] ?? now();
            $saleData['completed_at'] = $request->payment_status === 'paid' ? now() : null;

            $sale = Sale::create($saleData);

            // Attach active shift if exists
            $activeShift = $user->activeShift();
            if ($activeShift) {
                $sale->shift_id = $activeShift->id;
                $sale->save();

                // Update shift balance and counters
                $activeShift->current_balance += $sale->total_amount;
                $activeShift->sales_amount += $sale->total_amount;
                $activeShift->transaction_count += 1;
                $activeShift->save();
            }

            // Create sale items
            foreach ($request->items as $itemData) {
                // Get product details
                $product = Product::find($itemData['product_id']);

                if (! $product) {
                    DB::rollBack();

                    return response()->json([
                        'message' => "Product {$itemData['product_id']} not found.",
                    ], 404);
                }

                // Calculate item totals
                $quantity = $itemData['quantity'];
                $unitPrice = $itemData['unit_price'];
                $discountAmount = $itemData['discount_amount'] ?? 0;
                $discountPercentage = $itemData['discount_percentage'] ?? 0;

                if ($discountPercentage > 0) {
                    $discountAmount = ($unitPrice * $quantity * $discountPercentage) / 100;
                }

                $subtotal = ($unitPrice * $quantity) - $discountAmount;

                $isTaxable = $itemData['is_taxable'] ?? $product->is_vat_applicable;
                $taxRate = $itemData['tax_rate'] ?? $product->vat_rate ?? 0;
                $taxAmount = $isTaxable ? ($subtotal * $taxRate) / 100 : 0;

                $total = $subtotal + $taxAmount;

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'batch_id' => $itemData['batch_id'] ?? null,
                    'product_name' => $product->name,
                    'product_name_chichewa' => $product->name_chichewa,
                    'product_sku' => $product->sku,
                    'quantity' => $quantity,
                    'unit' => $product->unit,
                    'unit_cost' => $product->cost_price,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $discountAmount,
                    'discount_percentage' => $discountPercentage,
                    'is_taxable' => $isTaxable,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'subtotal' => $subtotal,
                    'total' => $total,
                    'notes' => $itemData['notes'] ?? null,
                ]);

                // Update product quantity
                $product->decrement('quantity', $quantity);
                $product->increment('total_sold', $quantity);
                $product->increment('total_revenue', $total);
                $product->update(['last_sold_at' => now()]);
            }

            DB::commit();

            // Auto-fiscalize with MRA EIS if enabled
            if (config('services.mra_eis.auto_fiscalize') && $request->payment_status === 'paid') {
                $mraService = new MraEisService;
                $fiscalizationResult = $mraService->fiscalizeInvoice($sale->fresh());

                // Log if fiscalization fails but don't fail the sale creation
                if (! $fiscalizationResult['success']) {
                    \Log::warning('MRA EIS auto-fiscalization failed for sale', [
                        'sale_id' => $sale->id,
                        'error' => $fiscalizationResult['message'] ?? 'Unknown error',
                    ]);
                }
            }

            return response()->json([
                'message' => 'Sale created successfully.',
                'data' => new SaleResource($sale->load('items', 'customer')),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create sale.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified sale.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $query = Sale::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds);

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'customer')) {
            $query->with('customer');
        }
        if (str_contains($includes, 'items')) {
            $query->with('items.product');
        }
        if (str_contains($includes, 'servedBy')) {
            $query->with('servedBy');
        }
        if (str_contains($includes, 'payments')) {
            $query->with('payments');
        }

        $sale = $query->first();

        if (! $sale) {
            return response()->json([
                'message' => 'Sale not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'data' => new SaleResource($sale),
        ]);
    }

    /**
     * Update the specified sale (limited fields).
     */
    public function update(UpdateSaleRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $sale = Sale::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $sale) {
            return response()->json([
                'message' => 'Sale not found or you do not have access to it.',
            ], 404);
        }

        // Prevent updating cancelled sales
        if ($sale->cancelled_at) {
            return response()->json([
                'message' => 'Cannot update a cancelled sale.',
            ], 422);
        }

        $sale->update($request->validated());

        return response()->json([
            'message' => 'Sale updated successfully.',
            'data' => new SaleResource($sale->fresh()),
        ]);
    }

    /**
     * Cancel the specified sale.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $sale = Sale::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->with('items.product')
            ->first();

        if (! $sale) {
            return response()->json([
                'message' => 'Sale not found or you do not have access to it.',
            ], 404);
        }

        if ($sale->cancelled_at) {
            return response()->json([
                'message' => 'Sale is already cancelled.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Restore product quantities
            foreach ($sale->items as $item) {
                if ($item->product) {
                    $item->product->increment('quantity', $item->quantity);
                    $item->product->decrement('total_sold', $item->quantity);
                    $item->product->decrement('total_revenue', $item->total);
                }
            }

            // Mark sale as cancelled
            $sale->update([
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancellation_reason' => $request->input('reason', 'Cancelled by user'),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Sale cancelled successfully.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to cancel sale.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process a full or partial refund for a sale.
     */
    public function refund(RefundSaleRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $sale = Sale::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->with('items.product')
            ->first();

        if (! $sale) {
            return response()->json([
                'message' => 'Sale not found or you do not have access to it.',
            ], 404);
        }

        // Prevent refunding cancelled sales
        if ($sale->cancelled_at) {
            return response()->json([
                'message' => 'Cannot refund a cancelled sale.',
            ], 422);
        }

        // Prevent refunding already refunded sales
        if ($sale->refunded_at) {
            return response()->json([
                'message' => 'This sale has already been refunded.',
            ], 422);
        }

        // Validate refund amount doesn't exceed total amount
        if ($request->refund_amount > $sale->total_amount) {
            return response()->json([
                'message' => 'Refund amount cannot exceed the sale total amount.',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // If items are provided, process partial refund by restocking specific items
            if ($request->has('items') && ! empty($request->items)) {
                foreach ($request->items as $refundItem) {
                    $saleItem = SaleItem::where('id', $refundItem['sale_item_id'])
                        ->where('sale_id', $sale->id)
                        ->first();

                    if (! $saleItem) {
                        DB::rollBack();

                        return response()->json([
                            'message' => "Sale item {$refundItem['sale_item_id']} not found in this sale.",
                        ], 404);
                    }

                    // Validate quantity doesn't exceed sold quantity
                    if ($refundItem['quantity'] > $saleItem->quantity) {
                        DB::rollBack();

                        return response()->json([
                            'message' => "Refund quantity for item {$saleItem->product_name} cannot exceed sold quantity of {$saleItem->quantity}.",
                        ], 422);
                    }

                    // Restore product quantities proportional to refund
                    if ($saleItem->product) {
                        $saleItem->product->increment('quantity', $refundItem['quantity']);
                        $saleItem->product->decrement('total_sold', $refundItem['quantity']);
                        // Calculate proportional revenue to deduct
                        $proportionalRevenue = ($saleItem->total / $saleItem->quantity) * $refundItem['quantity'];
                        $saleItem->product->decrement('total_revenue', $proportionalRevenue);
                    }
                }
            } else {
                // Full refund - restore all product quantities
                foreach ($sale->items as $item) {
                    if ($item->product) {
                        $item->product->increment('quantity', $item->quantity);
                        $item->product->decrement('total_sold', $item->quantity);
                        $item->product->decrement('total_revenue', $item->total);
                    }
                }
            }

            // Mark sale as refunded
            $sale->update([
                'refunded_at' => now(),
                'refund_amount' => $request->refund_amount,
            ]);

            // Store refund metadata in internal_notes
            $refundMetadata = [
                'refund_date' => now()->toDateTimeString(),
                'refund_amount' => $request->refund_amount,
                'refund_method' => $request->refund_method,
                'refund_reason' => $request->refund_reason,
                'refund_notes' => $request->notes,
                'refunded_by' => $user->id,
                'refund_items' => $request->items ?? null,
            ];

            $sale->update([
                'internal_notes' => json_encode($refundMetadata),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Sale refunded successfully.',
                'data' => [
                    'sale_id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                    'refund_amount' => $request->refund_amount,
                    'refund_method' => $request->refund_method,
                    'refunded_at' => $sale->refunded_at,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to refund sale.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Manually fiscalize a sale with MRA EIS.
     */
    public function fiscalize(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $sale = Sale::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $sale) {
            return response()->json([
                'message' => 'Sale not found or you do not have access to it.',
            ], 404);
        }

        // Check if already fiscalized
        if ($sale->is_fiscalized) {
            return response()->json([
                'message' => 'This sale has already been fiscalized.',
                'fiscal_receipt_number' => $sale->efd_receipt_number,
                'qr_code' => $sale->efd_qr_code,
            ], 422);
        }

        // Check if sale is paid
        if ($sale->payment_status !== 'paid') {
            return response()->json([
                'message' => 'Only fully paid sales can be fiscalized.',
            ], 422);
        }

        try {
            $mraService = new MraEisService;
            $result = $mraService->fiscalizeInvoice($sale);

            if ($result['success']) {
                return response()->json([
                    'message' => 'Sale fiscalized successfully with MRA EIS.',
                    'data' => [
                        'sale_id' => $sale->id,
                        'fiscal_receipt_number' => $result['fiscal_receipt_number'] ?? null,
                        'qr_code' => $result['qr_code'] ?? null,
                        'verification_url' => $result['verification_url'] ?? null,
                    ],
                ]);
            }

            return response()->json([
                'message' => $result['message'] ?? 'Failed to fiscalize sale.',
                'error_details' => $result['error_details'] ?? null,
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Exception during fiscalization.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
