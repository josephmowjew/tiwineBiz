<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EfdTransaction\StoreEfdTransactionRequest;
use App\Http\Resources\EfdTransactionResource;
use App\Models\EfdTransaction;
use App\Models\Shop;
use Illuminate\Http\Request;

class EfdTransactionController extends Controller
{
    /**
     * Display a listing of EFD transactions.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get shops accessible by this user
        $shopIds = Shop::where('owner_id', $user->id)
            ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $query = EfdTransaction::query()
            ->whereIn('shop_id', $shopIds);

        // Filter by shop
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        // Filter by EFD device
        if ($request->filled('efd_device_id')) {
            $query->where('efd_device_id', $request->efd_device_id);
        }

        // Filter by sale
        if ($request->filled('sale_id')) {
            $query->where('sale_id', $request->sale_id);
        }

        // Filter by transmission status
        if ($request->filled('transmission_status')) {
            $query->where('transmission_status', $request->transmission_status);
        }

        // Filter by fiscal receipt number
        if ($request->filled('fiscal_receipt_number')) {
            $query->where('fiscal_receipt_number', 'like', '%'.$request->fiscal_receipt_number.'%');
        }

        // Filter transactions pending retry
        if ($request->filled('pending_retry') && $request->boolean('pending_retry')) {
            $query->whereNotNull('next_retry_at')
                ->where('next_retry_at', '<=', now())
                ->where('transmission_status', '!=', 'success');
        }

        // Filter failed transactions with exhausted retries
        if ($request->filled('retry_exhausted') && $request->boolean('retry_exhausted')) {
            $query->where('transmission_status', 'failed')
                ->where('retry_count', '>=', 3);
        }

        // Filter by transmitted date range
        if ($request->filled('from_date')) {
            $query->whereDate('transmitted_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('transmitted_at', '<=', $request->to_date);
        }

        // Eager load relationships
        $query->with(['shop', 'sale']);

        // Sort by transmitted_at descending by default
        $query->orderBy('transmitted_at', 'desc')->orderBy('created_at', 'desc');

        $transactions = $query->paginate($request->per_page ?? 15);

        return EfdTransactionResource::collection($transactions);
    }

    /**
     * Store a newly created EFD transaction.
     */
    public function store(StoreEfdTransactionRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Verify shop belongs to user
        $shop = Shop::where('id', $data['shop_id'])
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        // Set timestamps if not provided
        if (! isset($data['created_at'])) {
            $data['created_at'] = now();
        }

        if (! isset($data['transmitted_at'])) {
            $data['transmitted_at'] = now();
        }

        // Initialize retry count if not provided
        if (! isset($data['retry_count'])) {
            $data['retry_count'] = 0;
        }

        // Set next retry time for failed/pending transactions
        if (isset($data['transmission_status']) && in_array($data['transmission_status'], ['failed', 'pending', 'offline'])) {
            if (! isset($data['next_retry_at'])) {
                // Exponential backoff: 5 min, 15 min, 60 min
                $retryMinutes = [5, 15, 60];
                $retryIndex = min($data['retry_count'], count($retryMinutes) - 1);
                $data['next_retry_at'] = now()->addMinutes($retryMinutes[$retryIndex]);
            }
        }

        $transaction = EfdTransaction::create($data);

        return new EfdTransactionResource($transaction->load(['shop', 'sale']));
    }

    /**
     * Display the specified EFD transaction.
     */
    public function show(Request $request, EfdTransaction $efdTransaction)
    {
        $user = $request->user();

        // Verify transaction belongs to user's shop
        $shop = Shop::where('id', $efdTransaction->shop_id)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        return new EfdTransactionResource($efdTransaction->load(['shop', 'sale']));
    }
}
