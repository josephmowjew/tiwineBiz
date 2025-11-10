<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        protected ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Display a listing of products scoped to user's shops.
     */
    public function index(Request $request): JsonResponse
    {
        // Verify shop access if filtering by specific shop
        if ($request->filled('shop_id')) {
            $user = $request->user();
            $shopIds = $user->shops()
                ->pluck('shops.id')
                ->merge([$user->ownedShops()->pluck('id')])
                ->flatten()
                ->unique()
                ->values();

            if (! in_array($request->shop_id, $shopIds->toArray())) {
                return response()->json([
                    'message' => 'You do not have access to this shop.',
                ], 403);
            }
        }

        // Prepare filters from request
        $filters = [
            'shop_id' => $request->input('shop_id'),
            'category_id' => $request->input('category_id'),
            'supplier_id' => $request->input('supplier_id'),
            'is_active' => $request->input('is_active'),
            'low_stock' => $request->boolean('low_stock'),
            'search' => $request->input('search'),
            'search_term' => $request->input('search_term'),
            'with' => $request->input('include'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_direction' => $request->input('sort_order', 'desc'),
        ];

        // Use repository with device-aware pagination
        $products = $this->productRepository->autoPaginate(
            $request->input('per_page'),
            $filters
        );

        // Build response based on pagination type
        $response = [
            'data' => ProductResource::collection($products->items()),
        ];

        // Add pagination meta (works for both offset and cursor pagination)
        if (method_exists($products, 'currentPage')) {
            // Offset pagination (web)
            $response['meta'] = [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'per_page' => $products->perPage(),
                'total' => $products->total(),
            ];
        } else {
            // Cursor pagination (mobile)
            $response['meta'] = [
                'per_page' => $products->perPage(),
                'next_cursor' => $products->nextCursor()?->encode(),
                'prev_cursor' => $products->previousCursor()?->encode(),
                'has_more' => $products->hasMorePages(),
            ];
        }

        return response()->json($response);
    }

    /**
     * Store a newly created product.
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verify user has access to the shop
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        if (! in_array($request->shop_id, $shopIds->toArray())) {
            return response()->json([
                'message' => 'You do not have access to this shop.',
            ], 403);
        }

        // Create product using repository
        $product = $this->productRepository->create([
            ...$request->validated(),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        // Load relationships for response
        $product->load('category', 'primarySupplier');

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product),
        ], 201);
    }

    /**
     * Display the specified product.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        // Prepare filters including shop scope and relationships
        $filters = [
            'id' => $id,
            'with' => $request->input('include'),
        ];

        // Use repository to find product with filters
        $products = $this->productRepository->all($filters);
        $product = $products->first();

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

        // Verify product exists and user has access (via shop scope)
        $filters = ['id' => $id];
        $products = $this->productRepository->all($filters);

        if ($products->isEmpty()) {
            return response()->json([
                'message' => 'Product not found or you do not have access to it.',
            ], 404);
        }

        // Update product using repository
        $product = $this->productRepository->update($id, [
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
        // Verify product exists and user has access (via shop scope)
        $filters = ['id' => $id];
        $products = $this->productRepository->all($filters);

        if ($products->isEmpty()) {
            return response()->json([
                'message' => 'Product not found or you do not have access to it.',
            ], 404);
        }

        // Delete product using repository
        $this->productRepository->delete($id);

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
