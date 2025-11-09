<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Credit\StoreCreditRequest;
use App\Http\Requests\Credit\UpdateCreditRequest;
use App\Http\Resources\CreditResource;
use App\Models\Credit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CreditController extends Controller
{
    /**
     * Display a listing of credits scoped to user's shops.
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

        $query = Credit::query()->whereIn('shop_id', $shopIds);

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

        // Filter by customer
        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by overdue
        if ($request->filled('is_overdue') && $request->boolean('is_overdue')) {
            $query->where('status', 'overdue');
        }

        // Filter by due date range
        if ($request->filled('due_from')) {
            $query->where('due_date', '>=', $request->due_from);
        }
        if ($request->filled('due_to')) {
            $query->where('due_date', '<=', $request->due_to);
        }

        // Search by credit number
        if ($request->filled('search')) {
            $query->where('credit_number', 'like', "%{$request->search}%");
        }

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'shop')) {
            $query->with('shop');
        }
        if (str_contains($includes, 'customer')) {
            $query->with('customer');
        }
        if (str_contains($includes, 'sale')) {
            $query->with('sale');
        }
        if (str_contains($includes, 'payments')) {
            $query->with('payments');
        }
        if (str_contains($includes, 'creator')) {
            $query->with('creator');
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'due_date');
        $sortOrder = $request->input('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $credits = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => CreditResource::collection($credits->items()),
            'meta' => [
                'current_page' => $credits->currentPage(),
                'last_page' => $credits->lastPage(),
                'per_page' => $credits->perPage(),
                'total' => $credits->total(),
            ],
        ]);
    }

    /**
     * Store a newly created credit.
     */
    public function store(StoreCreditRequest $request): JsonResponse
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

        // Generate credit number
        $creditNumber = 'CREDIT-'.now()->format('Ymd').'-'.str_pad(
            Credit::where('shop_id', $request->shop_id)->whereDate('created_at', today())->count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

        // Calculate balance
        $balance = $request->original_amount - ($request->amount_paid ?? 0);

        // Determine status
        $status = 'pending';
        if ($balance == 0) {
            $status = 'paid';
        } elseif ($request->amount_paid > 0) {
            $status = 'partial';
        }

        $credit = Credit::create([
            ...$request->validated(),
            'credit_number' => $creditNumber,
            'balance' => $balance,
            'status' => $status,
            'created_by' => $user->id,
            'paid_at' => $balance == 0 ? now() : null,
        ]);

        return response()->json([
            'message' => 'Credit created successfully.',
            'data' => new CreditResource($credit),
        ], 201);
    }

    /**
     * Display the specified credit.
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

        $query = Credit::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds);

        // Load relationships if requested
        $includes = $request->input('include', '');
        if (str_contains($includes, 'shop')) {
            $query->with('shop');
        }
        if (str_contains($includes, 'customer')) {
            $query->with('customer');
        }
        if (str_contains($includes, 'sale')) {
            $query->with('sale');
        }
        if (str_contains($includes, 'payments')) {
            $query->with('payments');
        }
        if (str_contains($includes, 'creator')) {
            $query->with('creator');
        }

        $credit = $query->first();

        if (! $credit) {
            return response()->json([
                'message' => 'Credit not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'data' => new CreditResource($credit),
        ]);
    }

    /**
     * Update the specified credit.
     */
    public function update(UpdateCreditRequest $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $credit = Credit::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $credit) {
            return response()->json([
                'message' => 'Credit not found or you do not have access to it.',
            ], 404);
        }

        // Recalculate balance if amount_paid changed
        $data = $request->validated();
        if (isset($data['amount_paid'])) {
            $data['balance'] = $credit->original_amount - $data['amount_paid'];

            // Update status based on new balance
            if ($data['balance'] == 0) {
                $data['status'] = 'paid';
                $data['paid_at'] = now();
            } elseif ($data['amount_paid'] > 0) {
                $data['status'] = 'partial';
            }
        }

        $credit->update($data);

        return response()->json([
            'message' => 'Credit updated successfully.',
            'data' => new CreditResource($credit->fresh()),
        ]);
    }

    /**
     * Write off the specified credit.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();

        // Get shop IDs the user has access to
        $shopIds = $user->shops()
            ->pluck('shops.id')
            ->merge([$user->ownedShops()->pluck('id')])
            ->flatten()
            ->unique()
            ->values();

        $credit = Credit::query()
            ->where('id', $id)
            ->whereIn('shop_id', $shopIds)
            ->first();

        if (! $credit) {
            return response()->json([
                'message' => 'Credit not found or you do not have access to it.',
            ], 404);
        }

        $credit->update([
            'status' => 'written_off',
            'written_off_at' => now(),
            'written_off_by' => $user->id,
            'write_off_reason' => $request->input('reason', 'Written off by user'),
        ]);

        return response()->json([
            'message' => 'Credit written off successfully.',
        ]);
    }
}
