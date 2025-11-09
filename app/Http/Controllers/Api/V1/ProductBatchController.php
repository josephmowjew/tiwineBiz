<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductBatch\StoreProductBatchRequest;
use App\Http\Requests\ProductBatch\UpdateProductBatchRequest;
use App\Http\Resources\ProductBatchResource;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProductBatchController extends Controller
{
    /**
     * Display a listing of product batches.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get shops accessible by this user
        $shopIds = Shop::where('owner_id', $user->id)
            ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $query = ProductBatch::query()
            ->whereHas('product', fn ($q) => $q->whereIn('shop_id', $shopIds));

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        // Filter by purchase order
        if ($request->filled('purchase_order_id')) {
            $query->where('purchase_order_id', $request->purchase_order_id);
        }

        // Filter by depletion status
        if ($request->filled('is_depleted')) {
            $query->where('is_depleted', filter_var($request->is_depleted, FILTER_VALIDATE_BOOLEAN));
        }

        // Filter by expiry status
        if ($request->filled('expiry_status')) {
            switch ($request->expiry_status) {
                case 'expired':
                    $query->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '<', now());
                    break;
                case 'expiring_soon':
                    $query->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '>=', now())
                        ->whereDate('expiry_date', '<=', now()->addDays(30));
                    break;
                case 'valid':
                    $query->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '>', now()->addDays(30));
                    break;
                case 'no_expiry':
                    $query->whereNull('expiry_date');
                    break;
            }
        }

        // Eager load relationships
        $query->with(['product', 'supplier', 'purchaseOrder']);

        // Sort by purchase_date by default
        $query->orderBy('purchase_date', 'desc');

        $batches = $query->paginate($request->per_page ?? 15);

        return ProductBatchResource::collection($batches);
    }

    /**
     * Store a newly created product batch.
     */
    public function store(StoreProductBatchRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Verify product belongs to user's shop
        $product = Product::findOrFail($data['product_id']);
        $shop = Shop::where('id', $product->shop_id)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        // Auto-generate batch number if not provided
        if (! isset($data['batch_number'])) {
            $data['batch_number'] = 'BATCH-'.now()->format('Ymd').'-'.str_pad(
                ProductBatch::where('product_id', $product->id)
                    ->whereDate('created_at', today())
                    ->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );
        }

        // Calculate total landed cost if not provided
        if (! isset($data['total_landed_cost'])) {
            $data['total_landed_cost'] = ($data['product_cost'] ?? 0)
                + ($data['freight_cost'] ?? 0)
                + ($data['customs_duty'] ?? 0)
                + ($data['clearing_fee'] ?? 0)
                + ($data['other_costs'] ?? 0);
        }

        // Set remaining quantity to initial quantity if not specified
        if (! isset($data['remaining_quantity'])) {
            $data['remaining_quantity'] = $data['initial_quantity'];
        }

        // Set is_depleted based on remaining quantity
        $data['is_depleted'] = $data['remaining_quantity'] <= 0;

        DB::transaction(function () use ($data, $product, &$batch) {
            // Create the batch
            $batch = ProductBatch::create($data);

            // Update product's last_restocked_at and quantity
            $product->increment('quantity', $data['initial_quantity']);
            $product->update(['last_restocked_at' => now()]);
        });

        return new ProductBatchResource($batch->load(['product', 'supplier', 'purchaseOrder']));
    }

    /**
     * Display the specified product batch.
     */
    public function show(Request $request, ProductBatch $productBatch)
    {
        $user = $request->user();

        // Verify batch belongs to user's shop
        $product = $productBatch->product;
        $shop = Shop::where('id', $product->shop_id)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        return new ProductBatchResource(
            $productBatch->load(['product', 'supplier', 'purchaseOrder', 'stockMovements', 'saleItems'])
        );
    }

    /**
     * Update the specified product batch.
     */
    public function update(UpdateProductBatchRequest $request, ProductBatch $productBatch)
    {
        $user = $request->user();

        // Verify batch belongs to user's shop
        $product = $productBatch->product;
        $shop = Shop::where('id', $product->shop_id)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        $data = $request->validated();

        // Recalculate total landed cost if cost fields are updated
        if (isset($data['product_cost']) || isset($data['freight_cost']) || isset($data['customs_duty']) || isset($data['clearing_fee']) || isset($data['other_costs'])) {
            $data['total_landed_cost'] = ($data['product_cost'] ?? $productBatch->product_cost)
                + ($data['freight_cost'] ?? $productBatch->freight_cost)
                + ($data['customs_duty'] ?? $productBatch->customs_duty)
                + ($data['clearing_fee'] ?? $productBatch->clearing_fee)
                + ($data['other_costs'] ?? $productBatch->other_costs);
        }

        $productBatch->update($data);

        return new ProductBatchResource($productBatch->load(['product', 'supplier', 'purchaseOrder']));
    }

    /**
     * Remove the specified product batch.
     */
    public function destroy(Request $request, ProductBatch $productBatch)
    {
        $user = $request->user();

        // Verify batch belongs to user's shop
        $product = $productBatch->product;
        $shop = Shop::where('id', $product->shop_id)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        // Prevent deletion if batch has sale items or stock movements
        if ($productBatch->saleItems()->exists() || $productBatch->stockMovements()->exists()) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Cannot delete batch with associated sales or stock movements.');
        }

        $productBatch->delete();

        return response()->noContent();
    }
}
