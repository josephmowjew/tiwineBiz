<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionPayment\StoreSubscriptionPaymentRequest;
use App\Http\Resources\SubscriptionPaymentResource;
use App\Models\SubscriptionPayment;
use App\Repositories\Contracts\SubscriptionPaymentRepositoryInterface;
use Illuminate\Http\Request;

class SubscriptionPaymentController extends Controller
{
    public function __construct(
        protected SubscriptionPaymentRepositoryInterface $subscriptionPaymentRepository
    ) {}

    /**
     * Display a listing of subscription payments.
     */
    public function index(Request $request)
    {
        // Prepare filters from request
        $filters = [
            'shop_id' => $request->input('shop_id'),
            'subscription_id' => $request->input('subscription_id'),
            'status' => $request->input('status'),
            'payment_method' => $request->input('payment_method'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'unconfirmed' => $request->boolean('unconfirmed'),
            'awaiting_confirmation' => $request->boolean('awaiting_confirmation'),
            'search' => $request->input('search'),
            'search_term' => $request->input('search_term'),
        ];

        // Use repository with device-aware pagination
        $payments = $this->subscriptionPaymentRepository->autoPaginate(
            $request->input('per_page'),
            $filters
        );

        return SubscriptionPaymentResource::collection($payments);
    }

    /**
     * Store a newly created subscription payment.
     */
    public function store(StoreSubscriptionPaymentRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

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

        // Create payment using repository
        $payment = $this->subscriptionPaymentRepository->create($data);

        // Load relationships for response
        $payment->load(['subscription', 'shop', 'confirmedBy']);

        return new SubscriptionPaymentResource($payment);
    }

    /**
     * Display the specified subscription payment.
     */
    public function show(Request $request, string $id)
    {
        // Use repository to find payment with shop scope
        $filters = ['id' => $id];
        $payments = $this->subscriptionPaymentRepository->all($filters);
        $payment = $payments->first();

        if (! $payment) {
            return response()->json([
                'message' => 'Subscription payment not found or you do not have access to it.',
            ], 404);
        }

        return new SubscriptionPaymentResource($payment);
    }
}
