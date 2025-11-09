<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers scoped to user's shops.
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

        $query = Customer::query()->whereIn('shop_id', $shopIds);

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

        // Filter by trust level
        if ($request->filled('trust_level')) {
            $query->where('trust_level', $request->trust_level);
        }

        // Filter by active status
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        // Filter by blocked status
        if ($request->filled('is_blocked') && $request->boolean('is_blocked')) {
            $query->whereNotNull('blocked_at');
        }

        // Search by name, phone, email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('customer_number', 'like', "%{$search}%");
            });
        }

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'shop')) {
            $query->with('shop');
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $customers = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => CustomerResource::collection($customers->items()),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
            ],
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
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

        $customer = Customer::create([
            ...$request->validated(),
            'created_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Customer created successfully.',
            'data' => new CustomerResource($customer),
        ], 201);
    }

    /**
     * Display the specified customer.
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

        $query = Customer::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds);

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'shop')) {
            $query->with('shop');
        }
        if (str_contains($includes, 'sales')) {
            $query->with('sales');
        }

        $customer = $query->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'data' => new CustomerResource($customer),
        ]);
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $customer = Customer::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found or you do not have access to it.',
            ], 404);
        }

        $customer->update($request->validated());

        return response()->json([
            'message' => 'Customer updated successfully.',
            'data' => new CustomerResource($customer->fresh()),
        ]);
    }

    /**
     * Remove the specified customer (deactivate).
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

        $customer = Customer::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $customer) {
            return response()->json([
                'message' => 'Customer not found or you do not have access to it.',
            ], 404);
        }

        $customer->update([
            'is_active' => false,
        ]);

        return response()->json([
            'message' => 'Customer deactivated successfully.',
        ]);
    }
}
