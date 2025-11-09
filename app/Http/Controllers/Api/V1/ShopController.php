<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\StoreShopRequest;
use App\Http\Requests\Shop\UpdateShopRequest;
use App\Http\Resources\ShopResource;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    /**
     * Display a listing of shops accessible by the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Shop::query()
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('users', fn ($query) => $query->where('user_id', $user->id));
            });

        // Apply filters
        if ($request->filled('business_type')) {
            $query->where('business_type', $request->business_type);
        }

        if ($request->filled('subscription_status')) {
            $query->where('subscription_status', $request->subscription_status);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('legal_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'owner')) {
            $query->with('owner');
        }
        if (str_contains($includes, 'users')) {
            $query->with('users');
        }

        $shops = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'data' => ShopResource::collection($shops->items()),
            'meta' => [
                'current_page' => $shops->currentPage(),
                'last_page' => $shops->lastPage(),
                'per_page' => $shops->perPage(),
                'total' => $shops->total(),
            ],
        ]);
    }

    /**
     * Store a newly created shop.
     */
    public function store(StoreShopRequest $request): JsonResponse
    {
        $shop = Shop::create([
            ...$request->validated(),
            'owner_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Shop created successfully.',
            'data' => new ShopResource($shop->load('owner')),
        ], 201);
    }

    /**
     * Display the specified shop.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        $query = Shop::query()
            ->where('id', $id)
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('users', fn ($query) => $query->where('user_id', $user->id));
            });

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'owner')) {
            $query->with('owner');
        }
        if (str_contains($includes, 'users')) {
            $query->with('users');
        }

        $shop = $query->first();

        if (! $shop) {
            return response()->json([
                'message' => 'Shop not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'data' => new ShopResource($shop),
        ]);
    }

    /**
     * Update the specified shop.
     */
    public function update(UpdateShopRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        $shop = Shop::query()
            ->where('id', $id)
            ->where('owner_id', $user->id)
            ->first();

        if (! $shop) {
            return response()->json([
                'message' => 'Shop not found or you do not have permission to update it.',
            ], 404);
        }

        $shop->update($request->validated());

        return response()->json([
            'message' => 'Shop updated successfully.',
            'data' => new ShopResource($shop->fresh()),
        ]);
    }

    /**
     * Remove the specified shop (soft delete).
     */
    public function destroy(string $id): JsonResponse
    {
        $user = auth()->user();

        $shop = Shop::query()
            ->where('id', $id)
            ->where('owner_id', $user->id)
            ->first();

        if (! $shop) {
            return response()->json([
                'message' => 'Shop not found or you do not have permission to delete it.',
            ], 404);
        }

        $shop->update([
            'is_active' => false,
            'deactivated_at' => now(),
            'deactivation_reason' => 'Deleted by owner',
        ]);

        return response()->json([
            'message' => 'Shop deactivated successfully.',
        ]);
    }
}
