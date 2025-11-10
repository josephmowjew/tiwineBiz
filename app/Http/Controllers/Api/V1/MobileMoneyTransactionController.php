<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\MobileMoneyTransaction\StoreMobileMoneyTransactionRequest;
use App\Http\Resources\MobileMoneyTransactionResource;
use App\Repositories\Contracts\MobileMoneyTransactionRepositoryInterface;
use Illuminate\Http\Request;

class MobileMoneyTransactionController extends Controller
{
    public function __construct(
        protected MobileMoneyTransactionRepositoryInterface $mobileMoneyTransactionRepository
    ) {}

    /**
     * Display a listing of mobile money transactions.
     */
    public function index(Request $request)
    {
        // Prepare filters from request
        $filters = [
            'shop_id' => $request->input('shop_id'),
            'provider' => $request->input('provider'),
            'transaction_type' => $request->input('transaction_type'),
            'status' => $request->input('status'),
            'msisdn' => $request->input('msisdn'),
            'reference_type' => $request->input('reference_type'),
            'reference_id' => $request->input('reference_id'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'search' => $request->input('search'),
            'search_term' => $request->input('search_term'),
        ];

        // Use repository with device-aware pagination
        $transactions = $this->mobileMoneyTransactionRepository->autoPaginate(
            $request->input('per_page'),
            $filters
        );

        return MobileMoneyTransactionResource::collection($transactions);
    }

    /**
     * Store a newly created mobile money transaction.
     */
    public function store(StoreMobileMoneyTransactionRequest $request)
    {
        $data = $request->validated();

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

        // Create transaction using repository
        $transaction = $this->mobileMoneyTransactionRepository->create($data);

        // Load relationships for response
        $transaction->load(['shop']);

        return new MobileMoneyTransactionResource($transaction);
    }

    /**
     * Display the specified mobile money transaction.
     */
    public function show(Request $request, string $id)
    {
        // Use repository to find transaction with shop scope
        $filters = ['id' => $id];
        $transactions = $this->mobileMoneyTransactionRepository->all($filters);
        $transaction = $transactions->first();

        if (! $transaction) {
            return response()->json([
                'message' => 'Mobile money transaction not found or you do not have access to it.',
            ], 404);
        }

        return new MobileMoneyTransactionResource($transaction);
    }
}
