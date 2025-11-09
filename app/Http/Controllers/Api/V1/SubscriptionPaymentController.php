<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionPayment\StoreSubscriptionPaymentRequest;
use App\Http\Resources\SubscriptionPaymentResource;
use App\Models\Shop;
use App\Models\SubscriptionPayment;
use Illuminate\Http\Request;

class SubscriptionPaymentController extends Controller
{
    /**
     * Display a listing of subscription payments.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get shops accessible by this user
        $shopIds = Shop::where('owner_id', $user->id)
            ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $query = SubscriptionPayment::query()
            ->whereIn('shop_id', $shopIds);

        // Filter by shop
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        // Filter by subscription
        if ($request->filled('subscription_id')) {
            $query->where('subscription_id', $request->subscription_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filter by payment date range
        if ($request->filled('from_date')) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        // Filter unconfirmed payments
        if ($request->filled('unconfirmed') && $request->boolean('unconfirmed')) {
            $query->where('status', 'pending')
                ->whereNull('confirmed_at');
        }

        // Filter payments awaiting confirmation
        if ($request->filled('awaiting_confirmation') && $request->boolean('awaiting_confirmation')) {
            $query->where('status', 'pending')
                ->whereNotNull('payment_date');
        }

        // Eager load relationships
        $query->with(['subscription', 'shop', 'confirmedBy']);

        // Sort by payment_date descending by default
        $query->orderBy('payment_date', 'desc')->orderBy('created_at', 'desc');

        $payments = $query->paginate($request->per_page ?? 15);

        return SubscriptionPaymentResource::collection($payments);
    }

    /**
     * Store a newly created subscription payment.
     */
    public function store(StoreSubscriptionPaymentRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Verify shop belongs to user
        $shop = Shop::where('id', $data['shop_id'])
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        // Auto-generate payment number if not provided
        if (! isset($data['payment_number'])) {
            $data['payment_number'] = 'SUBPAY-'.now()->format('Ymd').'-'.str_pad(
                SubscriptionPayment::where('shop_id', $data['shop_id'])
                    ->whereDate('created_at', today())
                    ->count() + 1,
                4,
                '0',
                STR_PAD_LEFT
            );
        }

        // Set confirmed_at and confirmed_by for confirmed payments
        if (isset($data['status']) && $data['status'] === 'confirmed') {
            if (! isset($data['confirmed_at'])) {
                $data['confirmed_at'] = now();
            }
            if (! isset($data['confirmed_by'])) {
                $data['confirmed_by'] = $user->id;
            }
        }

        // Set payment_date if not provided
        if (! isset($data['payment_date'])) {
            $data['payment_date'] = now();
        }

        $payment = SubscriptionPayment::create($data);

        return new SubscriptionPaymentResource($payment->load(['subscription', 'shop', 'confirmedBy']));
    }

    /**
     * Display the specified subscription payment.
     */
    public function show(Request $request, SubscriptionPayment $subscriptionPayment)
    {
        $user = $request->user();

        // Verify payment belongs to user's shop
        Shop::where('id', $subscriptionPayment->shop_id)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        return new SubscriptionPaymentResource($subscriptionPayment->load(['subscription', 'shop', 'confirmedBy']));
    }
}
