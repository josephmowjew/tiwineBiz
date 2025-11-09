<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Http\Resources\PaymentResource;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments scoped to user's shops.
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

        $query = Payment::query()->whereIn('shop_id', $shopIds);

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

        // Filter by sale
        if ($request->filled('sale_id')) {
            $query->where('sale_id', $request->sale_id);
        }

        // Filter by credit
        if ($request->filled('credit_id')) {
            $query->where('credit_id', $request->credit_id);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->where('payment_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->where('payment_date', '<=', $request->to_date);
        }

        // Search by payment number or transaction reference
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('transaction_reference', 'like', "%{$search}%");
            });
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
        if (str_contains($includes, 'credit')) {
            $query->with('credit');
        }
        if (str_contains($includes, 'receivedBy')) {
            $query->with('receivedBy');
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'payment_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $payments = $query->paginate($request->input('per_page', 15));

        return response()->json([
            'data' => PaymentResource::collection($payments->items()),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ]);
    }

    /**
     * Store a newly created payment.
     */
    public function store(StorePaymentRequest $request): JsonResponse
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

        // Calculate amount in base currency if different currency
        $amountInBaseCurrency = $request->amount;
        if ($request->currency !== 'MWK') {
            $amountInBaseCurrency = $request->amount * $request->exchange_rate;
        }

        // Generate payment number
        $paymentNumber = 'PAY-'.now()->format('Ymd').'-'.str_pad(
            Payment::where('shop_id', $request->shop_id)->whereDate('created_at', today())->count() + 1,
            4,
            '0',
            STR_PAD_LEFT
        );

        $payment = Payment::create([
            ...$request->validated(),
            'payment_number' => $paymentNumber,
            'amount_in_base_currency' => $amountInBaseCurrency,
            'received_by' => $user->id,
        ]);

        return response()->json([
            'message' => 'Payment recorded successfully.',
            'data' => new PaymentResource($payment),
        ], 201);
    }

    /**
     * Display the specified payment.
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

        $query = Payment::query()
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
        if (str_contains($includes, 'credit')) {
            $query->with('credit');
        }
        if (str_contains($includes, 'receivedBy')) {
            $query->with('receivedBy');
        }

        $payment = $query->first();

        if (! $payment) {
            return response()->json([
                'message' => 'Payment not found or you do not have access to it.',
            ], 404);
        }

        return response()->json([
            'data' => new PaymentResource($payment),
        ]);
    }
}
