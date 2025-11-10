<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subscription\StoreSubscriptionRequest;
use App\Http\Requests\Subscription\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        protected SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    /**
     * Display a listing of subscriptions.
     */
    public function index(Request $request)
    {
        // Prepare filters from request
        $filters = [
            'shop_id' => $request->input('shop_id'),
            'plan' => $request->input('plan'),
            'status' => $request->input('status'),
            'billing_cycle' => $request->input('billing_cycle'),
            'expiring_soon' => $request->boolean('expiring_soon'),
            'expired' => $request->boolean('expired'),
            'pending_cancellation' => $request->boolean('pending_cancellation'),
            'search' => $request->input('search'),
            'search_term' => $request->input('search_term'),
        ];

        // Use repository with device-aware pagination
        $subscriptions = $this->subscriptionRepository->autoPaginate(
            $request->input('per_page'),
            $filters
        );

        return SubscriptionResource::collection($subscriptions);
    }

    /**
     * Store a newly created subscription.
     */
    public function store(StoreSubscriptionRequest $request)
    {
        $data = $request->validated();

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

        // Create subscription using repository
        $subscription = $this->subscriptionRepository->create($data);

        // Load relationships for response
        $subscription->load(['shop', 'payments']);

        return new SubscriptionResource($subscription);
    }

    /**
     * Display the specified subscription.
     */
    public function show(Request $request, string $id)
    {
        // Use repository to find subscription with shop scope
        $filters = ['id' => $id];
        $subscriptions = $this->subscriptionRepository->all($filters);
        $subscription = $subscriptions->first();

        if (! $subscription) {
            return response()->json([
                'message' => 'Subscription not found or you do not have access to it.',
            ], 404);
        }

        return new SubscriptionResource($subscription);
    }

    /**
     * Update the specified subscription.
     */
    public function update(UpdateSubscriptionRequest $request, string $id)
    {
        $data = $request->validated();

        // Verify subscription exists and user has access
        $filters = ['id' => $id];
        $subscriptions = $this->subscriptionRepository->all($filters);
        $existingSubscription = $subscriptions->first();

        if (! $existingSubscription) {
            return response()->json([
                'message' => 'Subscription not found or you do not have access to it.',
            ], 404);
        }

        // Handle cancellation logic
        if (isset($data['status']) && $data['status'] === 'cancelled') {
            $data['cancelled_at'] = $data['cancelled_at'] ?? now();

            // If cancel_at_period_end is true, don't change status to cancelled yet
            if (isset($data['cancel_at_period_end']) && $data['cancel_at_period_end']) {
                $data['status'] = $existingSubscription->status; // Keep current status
            }
        }

        // Handle period renewal
        if (isset($data['current_period_end']) && $data['current_period_end'] > $existingSubscription->current_period_end) {
            $data['current_period_start'] = $existingSubscription->current_period_end;
        }

        // Update subscription using repository
        $subscription = $this->subscriptionRepository->update($id, $data);

        // Load relationships for response
        $subscription->load(['shop', 'payments']);

        return new SubscriptionResource($subscription);
    }

    /**
     * Remove the specified subscription.
     */
    public function destroy(Request $request, string $id)
    {
        // Verify subscription exists and user has access
        $filters = ['id' => $id];
        $subscriptions = $this->subscriptionRepository->all($filters);

        if ($subscriptions->isEmpty()) {
            return response()->json([
                'message' => 'Subscription not found or you do not have access to it.',
            ], 404);
        }

        // Delete subscription using repository
        $this->subscriptionRepository->delete($id);

        return response()->json(['message' => 'Subscription deleted successfully'], 200);
    }
}
