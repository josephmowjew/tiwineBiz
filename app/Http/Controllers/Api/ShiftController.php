<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftController extends Controller
{
    /**
     * Start a new shift.
     */
    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        // Get shop from header or user's first active shop
        $shopId = $request->header('X-Shop-ID');
        if (!$shopId) {
            $shopUser = $user->shopUsers()->where('is_active', true)->first();
            if (!$shopUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not assigned to any shop.',
                ], 403);
            }
            $shopId = $shopUser->shop_id;
        }

        // Check if user already has an active shift
        $activeShift = Shift::forUser($user->id)
            ->forShop($shopId)
            ->active()
            ->first();

        if ($activeShift) {
            throw ValidationException::withMessages([
                'shift' => ['You already have an active shift. Please end it before starting a new one.'],
            ]);
        }

        try {
            DB::beginTransaction();

            $shift = Shift::create([
                'id' => str()->uuid(),
                'user_id' => $user->id,
                'user_name' => $user->name,
                'shop_id' => $shopId,
                'branch_id' => $user->branch_id,
                'start_time' => now(),
                'opening_balance' => 0,
                'current_balance' => 0,
                'transaction_count' => 0,
                'sales_amount' => 0,
                'status' => 'active',
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Shift started successfully',
                'data' => $shift,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to start shift',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * End a shift.
     */
    public function end(Request $request, string $shiftId): JsonResponse
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $user = Auth::user();

        /** @var Shift $shift */
        $shift = Shift::where('id', $shiftId)
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => false,
                'message' => 'Active shift not found',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $shift->end_time = now();
            $shift->closing_balance = $shift->current_balance;
            $shift->expected_balance = $shift->current_balance;
            $shift->discrepancy = 0;
            $shift->status = 'completed';

            // Append end notes if provided
            if (!empty($validated['notes'])) {
                $shift->notes = ($shift->notes ?? '')."\n\nEnd: ".$validated['notes'];
            }

            $shift->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Shift ended successfully',
                'data' => $shift->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to end shift',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active shift for current user.
     */
    public function getActive(): JsonResponse
    {
        $user = Auth::user();

        // Get shop from header or user's first active shop
        $shopId = request()->header('X-Shop-ID');
        if (!$shopId) {
            $shopUser = $user->shopUsers()->where('is_active', true)->first();
            if (!$shopUser) {
                return response()->json([
                    'success' => true,
                    'data' => null,
                ]);
            }
            $shopId = $shopUser->shop_id;
        }

        $shift = Shift::forUser($user->id)
            ->forShop($shopId)
            ->active()
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => true,
                'data' => null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $shift,
        ]);
    }

    /**
     * Get shift history.
     */
    public function history(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'page' => 'nullable|integer|min:1',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $user = Auth::user();
        $shopId = $request->header('X-Shop-ID') ?? $user->shop_id;

        $query = Shift::forUser($user->id)
            ->forShop($shopId)
            ->dateRange($validated['start_date'], $validated['end_date'])
            ->orderBy('start_time', 'desc');

        $limit = $validated['limit'] ?? 15;
        $shifts = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $shifts->items(),
            'meta' => [
                'current_page' => $shifts->currentPage(),
                'last_page' => $shifts->lastPage(),
                'per_page' => $shifts->perPage(),
                'total' => $shifts->total(),
            ],
        ]);
    }

    /**
     * Update shift balance (called after each sale).
     */
    public function updateBalance(Request $request, string $shiftId): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
        ]);

        $user = Auth::user();

        /** @var Shift $shift */
        $shift = Shift::where('id', $shiftId)
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => false,
                'message' => 'Active shift not found',
            ], 404);
        }

        try {
            DB::beginTransaction();

            $shift->current_balance += $validated['amount'];
            $shift->sales_amount += $validated['amount'];
            $shift->transaction_count += 1;
            $shift->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $shift->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update balance',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get shift report.
     */
    public function report(string $shiftId): JsonResponse
    {
        $user = Auth::user();

        /** @var Shift $shift */
        $shift = Shift::where('id', $shiftId)
            ->where('user_id', $user->id)
            ->first();

        if (! $shift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift not found',
            ], 404);
        }

        $sales = $shift->sales()->with(['items', 'customer', 'payments'])->get();

        // Group sales by payment method
        $paymentBreakdown = $sales->flatMap(function ($sale) {
            return $sale->payments->map(function ($payment) use ($sale) {
                return [
                    'method' => $payment->payment_method,
                    'amount' => $payment->amount,
                    'sale_id' => $sale->id,
                    'sale_number' => $sale->sale_number,
                ];
            });
        })->groupBy('payment_method')->map(function ($group) {
            return [
                'count' => $group->count(),
                'total' => $group->sum('amount'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'shift' => $shift,
                'sales' => $sales,
                'payment_breakdown' => $paymentBreakdown,
                'summary' => [
                    'total_sales' => $sales->count(),
                    'total_revenue' => $shift->sales_amount,
                    'average_sale' => $sales->count() > 0 ? $shift->sales_amount / $sales->count() : 0,
                    'duration' => $shift->formatted_duration,
                    'discrepancy' => $shift->discrepancy,
                ],
            ],
        ]);
    }
}
