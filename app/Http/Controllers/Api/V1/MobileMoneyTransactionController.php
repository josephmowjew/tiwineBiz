<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MobileMoneyTransaction\StoreMobileMoneyTransactionRequest;
use App\Http\Resources\MobileMoneyTransactionResource;
use App\Models\MobileMoneyTransaction;
use App\Models\Shop;
use Illuminate\Http\Request;

class MobileMoneyTransactionController extends Controller
{
    /**
     * Display a listing of mobile money transactions.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get shops accessible by this user
        $shopIds = Shop::where('owner_id', $user->id)
            ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $query = MobileMoneyTransaction::query()
            ->whereIn('shop_id', $shopIds);

        // Filter by shop
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        // Filter by provider
        if ($request->filled('provider')) {
            $query->where('provider', $request->provider);
        }

        // Filter by transaction type
        if ($request->filled('transaction_type')) {
            $query->where('transaction_type', $request->transaction_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by MSISDN
        if ($request->filled('msisdn')) {
            $query->where('msisdn', 'like', '%'.$request->msisdn.'%');
        }

        // Filter by reference type and ID
        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
        }

        if ($request->filled('reference_id')) {
            $query->where('reference_id', $request->reference_id);
        }

        // Filter by date range
        if ($request->filled('from_date')) {
            $query->whereDate('transaction_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('transaction_date', '<=', $request->to_date);
        }

        // Eager load relationships
        $query->with(['shop']);

        // Sort by transaction_date descending by default
        $query->orderBy('transaction_date', 'desc')->orderBy('created_at', 'desc');

        $transactions = $query->paginate($request->per_page ?? 15);

        return MobileMoneyTransactionResource::collection($transactions);
    }

    /**
     * Store a newly created mobile money transaction.
     */
    public function store(StoreMobileMoneyTransactionRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Verify shop belongs to user
        $shop = Shop::where('id', $data['shop_id'])
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        // Set created_at if not provided
        if (! isset($data['created_at'])) {
            $data['created_at'] = now();
        }

        // Set transaction_date if not provided
        if (! isset($data['transaction_date'])) {
            $data['transaction_date'] = now();
        }

        // Set confirmed_at for successful transactions
        if (isset($data['status']) && $data['status'] === 'successful' && ! isset($data['confirmed_at'])) {
            $data['confirmed_at'] = now();
        }

        $transaction = MobileMoneyTransaction::create($data);

        return new MobileMoneyTransactionResource($transaction->load(['shop']));
    }

    /**
     * Display the specified mobile money transaction.
     */
    public function show(Request $request, MobileMoneyTransaction $mobileMoneyTransaction)
    {
        $user = $request->user();

        // Verify transaction belongs to user's shop
        $shop = Shop::where('id', $mobileMoneyTransaction->shop_id)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        return new MobileMoneyTransactionResource($mobileMoneyTransaction->load(['shop']));
    }
}
