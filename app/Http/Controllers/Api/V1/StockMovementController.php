<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockMovement\StoreStockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Product;
use App\Models\Shop;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class StockMovementController extends Controller
{
    /**
     * Display a listing of stock movements.
     *
     * Supports filtering by:
     * - shop_id: Filter by specific shop
     * - product_id: Filter by specific product
     * - movement_type: Filter by movement type (sale, purchase, etc.)
     * - from_date: Filter movements from this date
     * - to_date: Filter movements to this date
     * - reference_type: Filter by reference type (sale, purchase_order, etc.)
     * - reference_id: Filter by reference ID
     *
     * Supports sorting by:
     * - created_at (default: desc)
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        // Get accessible shop IDs for multi-tenant filtering
        $accessibleShopIds = Shop::where('owner_id', $user->id)
            ->orWhereHas('shopUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id');

        $query = StockMovement::query()
            ->with(['shop', 'product', 'batch', 'creator'])
            ->whereIn('shop_id', $accessibleShopIds);

        // Filter by shop
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by movement type
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        // Filter by reference
        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->filled('reference_id')) {
            $query->where('reference_id', $request->reference_id);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $stockMovements = $query->paginate(15);

        return StockMovementResource::collection($stockMovements);
    }

    /**
     * Store a newly created stock movement.
     *
     * IMPORTANT: Stock movements are IMMUTABLE - once created, they cannot be updated or deleted.
     * This ensures audit trail integrity.
     */
    public function store(StoreStockMovementRequest $request): StockMovementResource
    {
        $user = $request->user();

        // Verify user has access to the shop
        $shop = Shop::where('id', $request->shop_id)
            ->where(function ($query) use ($user) {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('shopUsers', function ($q) use ($user) {
                        $q->where('user_id', $user->id);
                    });
            })
            ->firstOrFail();

        // Verify product belongs to the shop
        $product = Product::where('id', $request->product_id)
            ->where('shop_id', $request->shop_id)
            ->firstOrFail();

        // Determine if movement increases or decreases stock
        $decreaseTypes = [
            'sale', 'return_to_supplier', 'adjustment_decrease',
            'damage', 'theft', 'expired', 'transfer_out',
        ];

        $quantity = $request->quantity;
        $currentStock = $product->quantity;

        // Calculate quantity_after based on movement type
        if (in_array($request->movement_type, $decreaseTypes)) {
            $quantityAfter = $currentStock - $quantity;
        } else {
            $quantityAfter = $currentStock + $quantity;
        }

        // Calculate total cost
        $totalCost = null;
        if ($request->filled('unit_cost')) {
            $totalCost = $quantity * $request->unit_cost;
        }

        // Create stock movement (IMMUTABLE - no updates allowed)
        $stockMovement = StockMovement::create([
            'shop_id' => $request->shop_id,
            'product_id' => $request->product_id,
            'batch_id' => $request->batch_id,
            'movement_type' => $request->movement_type,
            'quantity' => $quantity,
            'quantity_before' => $currentStock,
            'quantity_after' => $quantityAfter,
            'unit_cost' => $request->unit_cost,
            'total_cost' => $totalCost,
            'reference_type' => $request->reference_type,
            'reference_id' => $request->reference_id,
            'reason' => $request->reason,
            'notes' => $request->notes,
            'from_location' => $request->from_location,
            'to_location' => $request->to_location,
            'created_by' => $user->id,
            'created_at' => now(),
        ]);

        // Update product stock quantity
        $product->update([
            'quantity' => $quantityAfter,
        ]);

        return new StockMovementResource($stockMovement->load(['shop', 'product', 'batch', 'creator']));
    }

    /**
     * Display the specified stock movement.
     */
    public function show(Request $request, StockMovement $stockMovement): StockMovementResource
    {
        $user = $request->user();

        // Verify user has access to this stock movement's shop
        $accessibleShopIds = Shop::where('owner_id', $user->id)
            ->orWhereHas('shopUsers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->pluck('id');

        if (! $accessibleShopIds->contains($stockMovement->shop_id)) {
            abort(Response::HTTP_FORBIDDEN, 'You do not have access to this stock movement.');
        }

        return new StockMovementResource($stockMovement->load(['shop', 'product', 'batch', 'creator']));
    }
}
