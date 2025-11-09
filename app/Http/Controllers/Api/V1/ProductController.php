<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of products scoped to user's shops.
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

        $query = Product::query()->whereIn('shop_id', $shopIds);

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

        // Filter by category
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by supplier
        if ($request->filled('supplier_id')) {
            $query->where('primary_supplier_id', $request->supplier_id);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by low stock
        if ($request->filled('low_stock') && $request->boolean('low_stock')) {
            $query->whereColumn('quantity', '<=', 'min_stock_level');
        }

        // Search by name, SKU, barcode
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_chichewa', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'category')) {
            $query->with('category');
        }
        if (str_contains($includes, 'primarySupplier')) {
            $query->with('primarySupplier');
        }
        if (str_contains($includes, 'shop')) {
            $query->with('shop');
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $products = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => ProductResource::collection($products->items()),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ],
        ]);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
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

        $product = Product::create([
            ...$request->validated(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product->load('category', 'primarySupplier')),
        ], 201);
    }

    /**
     * Display the specified product.
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

        $query = Product::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds);

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'category')) {
            $query->with('category');
        }
        if (str_contains($includes, 'primarySupplier')) {
            $query->with('primarySupplier');
        }
        if (str_contains($includes, 'shop')) {
            $query->with('shop');
        }
        if (str_contains($includes, 'createdBy')) {
            $query->with('createdBy');
        }

        $product = $query->first();

        if (! $product) {
            return response()->json([
                'message' => 'Product not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    /**
     * Update the specified product.
     */
    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $product = Product::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'Product not found or you do not have access to it.',
            ], 404);
        }

        $product->update([
            ...$request->validated(),
            'updated_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product->fresh()),
        ]);
    }

    /**
     * Remove the specified product (soft delete).
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

        $product = Product::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'Product not found or you do not have access to it.',
            ], 404);
        }

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
