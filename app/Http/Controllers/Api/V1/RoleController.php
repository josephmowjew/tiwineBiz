<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * Display a listing of roles for shops accessible by the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs accessible by the user
        $shopIds = Shop::query()
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('users', fn ($query) => $query->where('user_id', $user->id));
            })
            ->pluck('id');

        // Get system roles (shop_id IS NULL) OR roles for user's shops
        $query = Role::query()
            ->where(function ($q) use ($shopIds) {
                $q->whereNull('shop_id')  // System roles
                    ->orWhereIn('shop_id', $shopIds);  // Shop-specific roles
            });

        // Apply filters
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        if ($request->filled('is_system_role')) {
            $query->where('is_system_role', $request->boolean('is_system_role'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'shop')) {
            $query->with('shop');
        }

        // Count users assigned to each role
        $query->withCount('shopUsers');

        $roles = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'data' => RoleResource::collection($roles->items()),
            'meta' => [
                'current_page' => $roles->currentPage(),
                'last_page' => $roles->lastPage(),
                'per_page' => $roles->perPage(),
                'total' => $roles->total(),
            ],
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verify user has access to the shop
        $shop = Shop::query()
            ->where('id', $request->shop_id)
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('users', fn ($query) => $query->where('user_id', $user->id));
            })
            ->first();

        if (! $shop) {
            return response()->json([
                'message' => 'Shop not found or you do not have access to it.',
            ], 404);
        }

        // Check for duplicate role name within the shop
        $existingRole = Role::query()
            ->where('shop_id', $request->shop_id)
            ->where('name', $request->name)
            ->exists();

        if ($existingRole) {
            return response()->json([
                'message' => 'A role with this name already exists in this shop.',
                'errors' => [
                    'name' => ['A role with this name already exists in this shop.'],
                ],
            ], 422);
        }

        $role = Role::create([
            'shop_id' => $request->shop_id,
            'name' => $request->name,
            'display_name' => $request->display_name,
            'description' => $request->description,
            'is_system_role' => false,
            'permissions' => $request->permissions,
        ]);

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => new RoleResource($role->load('shop')),
        ], 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs accessible by the user
        $shopIds = Shop::query()
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('users', fn ($query) => $query->where('user_id', $user->id));
            })
            ->pluck('id');

        $query = Role::query()
            ->where('id', $id)
            ->where(function ($q) use ($shopIds) {
                $q->whereNull('shop_id')  // System roles
                    ->orWhereIn('shop_id', $shopIds);  // Shop-specific roles
            });

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'shop')) {
            $query->with('shop');
        }

        $query->withCount('shopUsers');

        $role = $query->first();

        if (! $role) {
            return response()->json([
                'message' => 'Role not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'data' => new RoleResource($role),
        ]);
    }

    /**
     * Update the specified role.
     */
    public function update(UpdateRoleRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs accessible by the user
        $shopIds = Shop::query()
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('users', fn ($query) => $query->where('user_id', $user->id));
            })
            ->pluck('id');

        $role = Role::query()
            ->where('id', $id)
            ->where(function ($q) use ($shopIds) {
                $q->whereNull('shop_id')  // System roles
                    ->orWhereIn('shop_id', $shopIds);  // Shop-specific roles
            })
            ->first();

        if (! $role) {
            return response()->json([
                'message' => 'Role not found or you do not have access to it.',
            ], 404);
        }

        // Prevent updating system roles
        if ($role->is_system_role) {
            return response()->json([
                'message' => 'System roles cannot be modified.',
            ], 403);
        }

        // Check for duplicate role name within the shop (excluding current role)
        if ($request->filled('name')) {
            $existingRole = Role::query()
                ->where('shop_id', $role->shop_id)
                ->where('name', $request->name)
                ->where('id', '!=', $id)
                ->exists();

            if ($existingRole) {
                return response()->json([
                    'message' => 'A role with this name already exists in this shop.',
                    'errors' => [
                        'name' => ['A role with this name already exists in this shop.'],
                    ],
                ], 422);
            }
        }

        $role->update($request->validated());

        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => new RoleResource($role->fresh()),
        ]);
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs accessible by the user
        $shopIds = Shop::query()
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('users', fn ($query) => $query->where('user_id', $user->id));
            })
            ->pluck('id');

        $role = Role::query()
            ->where('id', $id)
            ->where(function ($q) use ($shopIds) {
                $q->whereNull('shop_id')  // System roles
                    ->orWhereIn('shop_id', $shopIds);  // Shop-specific roles
            })
            ->withCount('shopUsers')
            ->first();

        if (! $role) {
            return response()->json([
                'message' => 'Role not found or you do not have access to it.',
            ], 404);
        }

        // Prevent deleting system roles
        if ($role->is_system_role) {
            return response()->json([
                'message' => 'System roles cannot be deleted.',
            ], 403);
        }

        // Prevent deleting roles that are assigned to users
        if ($role->shop_users_count > 0) {
            return response()->json([
                'message' => 'Cannot delete role that is assigned to users.',
            ], 422);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }
}
