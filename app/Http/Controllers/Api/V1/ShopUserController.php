<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\ShopUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopUserController extends Controller
{
    /**
     * Get all users for a specific shop
     */
    public function index(Request $request, Shop $shop): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 20);
            $page = $request->input('page', 1);

            // Get shop users with role information
            $query = ShopUser::where('shop_id', $shop->id)
                ->with(['user', 'role']);

            $shopUsers = $query->paginate($perPage, ['*'], 'page', $page);

            // Format response
            $data = $shopUsers->map(function ($shopUser) {
                $response = [
                    'id' => $shopUser->id,
                    'shop_id' => $shopUser->shop_id,
                    'user_id' => $shopUser->user_id,
                    'role_id' => $shopUser->role_id,
                    'is_active' => (bool) $shopUser->is_active,
                    'joined_at' => $shopUser->joined_at ? $shopUser->joined_at->toISOString() : null,
                    'user' => [
                        'id' => $shopUser->user->id,
                        'name' => $shopUser->user->name,
                        'email' => $shopUser->user->email,
                        'phone' => $shopUser->user->phone,
                        'profile_photo_url' => $shopUser->user->profile_photo_url,
                        'is_active' => (bool) $shopUser->user->is_active,
                        'last_login_at' => $shopUser->user->last_login_at?->toISOString(),
                    ],
                    'role' => $shopUser->role ? [
                        'id' => $shopUser->role->id,
                        'name' => $shopUser->role->name,
                        'display_name' => $shopUser->role->display_name,
                        'description' => $shopUser->role->description,
                        'is_system_role' => (bool) $shopUser->role->is_system_role,
                        'permissions' => $shopUser->role->permissions,
                    ] : null,
                ];

                return $response;
            })->values();

            return response()->json([
                'data' => $data,
                'meta' => [
                    'current_page' => $shopUsers->currentPage(),
                    'last_page' => $shopUsers->lastPage(),
                    'per_page' => $shopUsers->perPage(),
                    'total' => $shopUsers->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch shop users.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific shop user
     */
    public function show(Shop $shop, User $user): JsonResponse
    {
        try {
            $shopUser = ShopUser::where('shop_id', $shop->id)
                ->where('user_id', $user->id)
                ->with(['user', 'role'])
                ->firstOrFail();

            return response()->json([
                'data' => [
                    'id' => $shopUser->id,
                    'shop_id' => $shopUser->shop_id,
                    'user_id' => $shopUser->user_id,
                    'role_id' => $shopUser->role_id,
                    'is_active' => (bool) $shopUser->is_active,
                    'joined_at' => $shopUser->joined_at?->toISOString(),
                    'created_at' => $shopUser->created_at->toISOString(),
                    'updated_at' => $shopUser->updated_at->toISOString(),
                    'user' => [
                        'id' => $shopUser->user->id,
                        'name' => $shopUser->user->name,
                        'email' => $shopUser->user->email,
                        'phone' => $shopUser->user->phone,
                        'profile_photo_url' => $shopUser->user->profile_photo_url,
                    ],
                    'role' => [
                        'id' => $shopUser->role->id,
                        'name' => $shopUser->role->name,
                        'display_name' => $shopUser->role->display_name,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Shop user not found.',
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Add a user to a shop
     */
    public function store(Request $request, Shop $shop): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|uuid|exists:users,id',
                'role_id' => 'required|uuid|exists:roles,id',
            ]);

            // Check if user is already in the shop
            $existingShopUser = ShopUser::where('shop_id', $shop->id)
                ->where('user_id', $validated['user_id'])
                ->first();

            if ($existingShopUser) {
                return response()->json([
                    'message' => 'User is already a member of this shop.',
                ], 409);
            }

            $shopUser = ShopUser::create([
                'id' => \Illuminate\Support\Str::uuid(),
                'shop_id' => $shop->id,
                'user_id' => $validated['user_id'],
                'role_id' => $validated['role_id'],
                'is_active' => true,
                'joined_at' => now(),
            ]);

            $shopUser->load(['user', 'role']);

            return response()->json([
                'message' => 'User added to shop successfully.',
                'data' => [
                    'id' => $shopUser->id,
                    'shop_id' => $shopUser->shop_id,
                    'user_id' => $shopUser->user_id,
                    'role_id' => $shopUser->role_id,
                    'is_active' => (bool) $shopUser->is_active,
                    'joined_at' => $shopUser->joined_at->toISOString(),
                    'user' => [
                        'id' => $shopUser->user->id,
                        'name' => $shopUser->user->name,
                        'email' => $shopUser->user->email,
                    ],
                    'role' => [
                        'id' => $shopUser->role->id,
                        'name' => $shopUser->role->name,
                        'display_name' => $shopUser->role->display_name,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to add user to shop.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update user role in shop
     */
    public function update(Request $request, Shop $shop, User $user): JsonResponse
    {
        try {
            $validated = $request->validate([
                'role_id' => 'required|uuid|exists:roles,id',
            ]);

            $shopUser = ShopUser::where('shop_id', $shop->id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $shopUser->update([
                'role_id' => $validated['role_id'],
            ]);

            $shopUser->load(['user', 'role']);

            return response()->json([
                'message' => 'User role updated successfully.',
                'data' => [
                    'id' => $shopUser->id,
                    'shop_id' => $shopUser->shop_id,
                    'user_id' => $shopUser->user_id,
                    'role_id' => $shopUser->role_id,
                    'is_active' => (bool) $shopUser->is_active,
                    'user' => [
                        'id' => $shopUser->user->id,
                        'name' => $shopUser->user->name,
                        'email' => $shopUser->user->email,
                    ],
                    'role' => [
                        'id' => $shopUser->role->id,
                        'name' => $shopUser->role->name,
                        'display_name' => $shopUser->role->display_name,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update user role.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove user from shop
     */
    public function destroy(Shop $shop, User $user): JsonResponse
    {
        try {
            $shopUser = ShopUser::where('shop_id', $shop->id)
                ->where('user_id', $user->id)
                ->firstOrFail();

            $shopUser->delete();

            return response()->json([
                'message' => 'User removed from shop successfully.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to remove user from shop.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
