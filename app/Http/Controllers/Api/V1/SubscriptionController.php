<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Shop;
use App\Models\Subscription;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get shops accessible by this user
        $shopIds = Shop::where('owner_id', $user->id)
            ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $query = Subscription::query()
            ->whereIn('shop_id', $shopIds);

        // Filter by shop
        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        // Filter by plan
        if ($request->filled('plan')) {
            $query->where('plan', $request->plan);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by billing cycle
        if ($request->filled('billing_cycle')) {
            $query->where('billing_cycle', $request->billing_cycle);
        }

        // Filter expiring soon (within next 7 days)
        if ($request->filled('expiring_soon') && $request->boolean('expiring_soon')) {
            $query->where('status', 'active')
                ->whereBetween('current_period_end', [now(), now()->addDays(7)]);
        }

        // Filter expired
        if ($request->filled('expired') && $request->boolean('expired')) {
            $query->where('status', 'expired')
                ->where('current_period_end', '<', now());
        }

        // Filter cancelled but still active
        if ($request->filled('pending_cancellation') && $request->boolean('pending_cancellation')) {
            $query->where('cancel_at_period_end', true)
                ->where('current_period_end', '>', now());
        }

        // Eager load relationships
        $query->with(['shop', 'payments']);

        // Sort by current_period_end descending by default
        $query->orderBy('current_period_end', 'desc')->orderBy('created_at', 'desc');

        $subscriptions = $query->paginate($request->per_page ?? 15);

        return SubscriptionResource::collection($subscriptions);
    }

    /**
     * Store a newly created subscription.
     */
    public function store(StoreSubscriptionRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        // Verify shop belongs to user
        $shop = Shop::where('id', $data['shop_id'])
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        // Set started_at if not provided
        if (! isset($data['started_at'])) {
            $data['started_at'] = now();
        }

        // Set current period start/end if not provided
        if (! isset($data['current_period_start'])) {
            $data['current_period_start'] = now();
        }

        if (! isset($data['current_period_end'])) {
            $months = $data['billing_cycle'] === 'annual' ? 12 : 1;
            $data['current_period_end'] = now()->addMonths($months);
        }

        // Initialize cancel_at_period_end if not provided
        if (! isset($data['cancel_at_period_end'])) {
            $data['cancel_at_period_end'] = false;
        }

        $subscription = Subscription::create($data);

        return new SubscriptionResource($subscription->load(['shop', 'payments']));
    }

    /**
     * Display the specified subscription.
     */
    public function show(Request $request, Subscription $subscription)
    {
        $user = $request->user();

        // Verify subscription belongs to user's shop
        Shop::where('id', $subscription->shop_id)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        return new SubscriptionResource($subscription->load(['shop', 'payments']));
    }

    /**
     * Update the specified subscription.
     */
    public function update(UpdateSubscriptionRequest $request, Subscription $subscription)
    {
        $user = $request->user();
        $data = $request->validated();

        // Verify subscription belongs to user's shop
        Shop::where('id', $subscription->shop_id)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        // Handle cancellation logic
        if (isset($data['status']) && $data['status'] === 'cancelled') {
            $data['cancelled_at'] = $data['cancelled_at'] ?? now();

            // If cancel_at_period_end is true, don't change status to cancelled yet
            if (isset($data['cancel_at_period_end']) && $data['cancel_at_period_end']) {
                $data['status'] = $subscription->status; // Keep current status
            }
        }

        // Handle period renewal
        if (isset($data['current_period_end']) && $data['current_period_end'] > $subscription->current_period_end) {
            $data['current_period_start'] = $subscription->current_period_end;
        }

        $subscription->update($data);

        return new SubscriptionResource($subscription->load(['shop', 'payments']));
    }

    /**
     * Remove the specified subscription.
     */
    public function destroy(Request $request, Subscription $subscription)
    {
        $user = $request->user();

        // Verify subscription belongs to user's shop
        Shop::where('id', $subscription->shop_id)
            ->where(fn ($q) => $q->where('owner_id', $user->id)
                ->orWhereHas('users', fn ($q) => $q->where('user_id', $user->id)))
            ->firstOrFail();

        $subscription->delete();

        return response()->json(['message' => 'Subscription deleted successfully'], 200);
    }
}
