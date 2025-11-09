<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    /**
     * Display a listing of suppliers scoped to user's shops.
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

        $query = Supplier::query()->whereIn('shop_id', $shopIds);

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

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by preferred suppliers
        if ($request->filled('is_preferred') && $request->boolean('is_preferred')) {
            $query->where('is_preferred', true);
        }

        // Search by name, contact, email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('supplier_code', 'like', "%{$search}%")
                    ->orWhere('contact_person', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'shop')) {
            $query->with('shop');
        }
        if (str_contains($includes, 'products')) {
            $query->with('products');
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $suppliers = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => SupplierResource::collection($suppliers->items()),
            'meta' => [
                'current_page' => $suppliers->currentPage(),
                'last_page' => $suppliers->lastPage(),
                'per_page' => $suppliers->perPage(),
                'total' => $suppliers->total(),
            ],
        ]);
    }

    /**
     * Store a newly created supplier.
     */
    public function store(StoreSupplierRequest $request): JsonResponse
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

        $supplier = Supplier::create($request->validated());

        return response()->json([
            'message' => 'Supplier created successfully.',
            'data' => new SupplierResource($supplier),
        ], 201);
    }

    /**
     * Display the specified supplier.
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

        $query = Supplier::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds);

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'shop')) {
            $query->with('shop');
        }
        if (str_contains($includes, 'products')) {
            $query->with('products');
        }
        if (str_contains($includes, 'purchaseOrders')) {
            $query->with('purchaseOrders');
        }

        $supplier = $query->first();

        if (! $supplier) {
            return response()->json([
                'message' => 'Supplier not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'data' => new SupplierResource($supplier),
        ]);
    }

    /**
     * Update the specified supplier.
     */
    public function update(UpdateSupplierRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $supplier = Supplier::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $supplier) {
            return response()->json([
                'message' => 'Supplier not found or you do not have access to it.',
            ], 404);
        }

        $supplier->update($request->validated());

        return response()->json([
            'message' => 'Supplier updated successfully.',
            'data' => new SupplierResource($supplier->fresh()),
        ]);
    }

    /**
     * Remove the specified supplier (deactivate).
     */
    public function destroy(string $id): JsonResponse
    {
        $user = auth()->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $supplier = Supplier::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $supplier) {
            return response()->json([
                'message' => 'Supplier not found or you do not have access to it.',
            ], 404);
        }

        $supplier->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Supplier deactivated successfully.',
        ]);
    }
}
