<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\EfdTransaction\StoreEfdTransactionRequest;
use App\Http\Resources\EfdTransactionResource;
use App\Repositories\Contracts\EfdTransactionRepositoryInterface;
use Illuminate\Http\Request;

class EfdTransactionController extends Controller
{
    public function __construct(
        protected EfdTransactionRepositoryInterface $efdTransactionRepository
    ) {}

    /**
     * Display a listing of EFD transactions.
     */
    public function index(Request $request)
    {
        // Prepare filters from request
        $filters = [
            'shop_id' => $request->input('shop_id'),
            'efd_device_id' => $request->input('efd_device_id'),
            'sale_id' => $request->input('sale_id'),
            'transmission_status' => $request->input('transmission_status'),
            'fiscal_receipt_number' => $request->input('fiscal_receipt_number'),
            'pending_retry' => $request->boolean('pending_retry'),
            'retry_exhausted' => $request->boolean('retry_exhausted'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
            'search' => $request->input('search'),
            'search_term' => $request->input('search_term'),
        ];

        // Use repository with device-aware pagination
        $transactions = $this->efdTransactionRepository->autoPaginate(
            $request->input('per_page'),
            $filters
        );

        return EfdTransactionResource::collection($transactions);
    }

    /**
     * Store a newly created EFD transaction.
     */
    public function store(StoreEfdTransactionRequest $request)
    {
        $data = $request->validated();

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

        // Create transaction using repository
        $transaction = $this->efdTransactionRepository->create($data);

        // Load relationships for response
        $transaction->load(['shop', 'sale']);

        return new EfdTransactionResource($transaction);
    }

    /**
     * Display the specified EFD transaction.
     */
    public function show(Request $request, string $id)
    {
        // Use repository to find transaction with shop scope
        $filters = ['id' => $id];
        $transactions = $this->efdTransactionRepository->all($filters);
        $transaction = $transactions->first();

        if (! $transaction) {
            return response()->json([
                'message' => 'EFD transaction not found or you do not have access to it.',
            ], 404);
        }

        return new EfdTransactionResource($transaction);
    }
}
