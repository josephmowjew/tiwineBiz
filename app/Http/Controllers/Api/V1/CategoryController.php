<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories scoped to user's shops.
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

        $query = Category::query()
            ->where(function ($q) use ($shopIds) {
                $q->whereIn('shop_id', $shopIds)
                    ->orWhereNull('shop_id'); // Include system categories
            });

        // Filter by specific shop if provided
        if ($request->filled('shop_id')) {
            if (in_array($request->shop_id, $shopIds->toArray())) {
                $query->where(function ($q) use ($request) {
                    $q->where('shop_id', $request->shop_id)
                        ->orWhereNull('shop_id');
                });
            } else {
                return response()->json([
                    'message' => 'You do not have access to this shop.',
                ], 403);
            }
        }

        // Filter by parent category (hierarchical)
        if ($request->filled('parent_id')) {
            if ($request->parent_id === 'null' || $request->parent_id === null) {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent_id);
            }
        }

        // Filter system vs shop-specific categories
        if ($request->filled('system_only') && $request->boolean('system_only')) {
            $query->whereNull('shop_id');
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search by name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('name_chichewa', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'parent')) {
            $query->with('parent');
        }
        if (str_contains($includes, 'children')) {
            $query->with('children');
        }
        if (str_contains($includes, 'products')) {
            $query->with('products');
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'display_order');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $categories = $query->paginate($request->input('per_page', 50));

        return response()->json([
            'data' => CategoryResource::collection($categories->items()),
            'meta' => [
                'current_page' => $categories->currentPage(),
                'last_page' => $categories->lastPage(),
                'per_page' => $categories->perPage(),
                'total' => $categories->total(),
            ],
        ]);
    }

    /**
     * Store a newly created category.
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verify user has access to the shop if shop_id is provided
        if ($request->filled('shop_id')) {
            $hasAccess = $user->shops()->where('shops.id', $request->shop_id)->exists()
                || $user->ownedShops()->where('id', $request->shop_id)->exists();

            if (! $hasAccess) {
                return response()->json([
                    'message' => 'You do not have access to this shop.',
                ], 403);
            }
        }

        $data = $request->validated();

        // Auto-generate slug if not provided
        if (! isset($data['slug']) || empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Calculate depth and path if parent is set
        if (isset($data['parent_id']) && $data['parent_id']) {
            $parent = Category::find($data['parent_id']);
            if ($parent) {
                $data['depth'] = $parent->depth + 1;
                $data['path'] = $parent->path.'/'.$data['slug'];
            }
        } else {
            $data['depth'] = 0;
            $data['path'] = '/'.$data['slug'];
        }

        $category = Category::create($data);

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => new CategoryResource($category->load('parent')),
        ], 201);
    }

    /**
     * Display the specified category.
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

        $query = Category::query()
            ->where('id', $id)
            ->where(function ($q) use ($shopIds) {
                $q->whereIn('shop_id', $shopIds)
                    ->orWhereNull('shop_id'); // Include system categories
            });

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'parent')) {
            $query->with('parent');
        }
        if (str_contains($includes, 'children')) {
            $query->with('children');
        }
        if (str_contains($includes, 'products')) {
            $query->with('products');
        }

        $category = $query->first();

        if (! $category) {
            return response()->json([
                'message' => 'Category not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'data' => new CategoryResource($category),
        ]);
    }

    /**
     * Update the specified category.
     */
    public function update(UpdateCategoryRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $category = Category::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $category) {
            return response()->json([
                'message' => 'Category not found or you do not have access to it.',
            ], 404);
        }

        $data = $request->validated();

        // Recalculate depth and path if parent changed
        if (isset($data['parent_id'])) {
            if ($data['parent_id']) {
                $parent = Category::find($data['parent_id']);
                if ($parent) {
                    $data['depth'] = $parent->depth + 1;
                    $slug = $data['slug'] ?? $category->slug;
                    $data['path'] = $parent->path.'/'.$slug;
                }
            } else {
                $data['depth'] = 0;
                $slug = $data['slug'] ?? $category->slug;
                $data['path'] = '/'.$slug;
            }
        }

        $category->update($data);

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => new CategoryResource($category->fresh()),
        ]);
    }

    /**
     * Remove the specified category (deactivate).
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

        $category = Category::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $category) {
            return response()->json([
                'message' => 'Category not found or you do not have access to it.',
            ], 404);
        }

        // Check if category has children
        if ($category->children()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with subcategories. Please delete subcategories first.',
            ], 422);
        }

        // Check if category has products
        if ($category->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete category with products. Please reassign products first.',
            ], 422);
        }

        $category->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Category deactivated successfully.',
        ]);
    }
}
