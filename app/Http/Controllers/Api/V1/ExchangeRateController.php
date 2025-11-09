<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExchangeRate\StoreExchangeRateRequest;
use App\Http\Resources\ExchangeRateResource;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    /**
     * Display a listing of exchange rates.
     */
    public function index(Request $request)
    {
        $query = ExchangeRate::query();

        // Filter by target currency
        if ($request->filled('target_currency')) {
            $query->where('target_currency', $request->target_currency);
        }

        // Filter by base currency
        if ($request->filled('base_currency')) {
            $query->where('base_currency', $request->base_currency);
        }

        // Filter by effective date range
        if ($request->filled('from_date')) {
            $query->whereDate('effective_date', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->whereDate('effective_date', '<=', $request->to_date);
        }

        // Filter by source
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Get current/active rates (effective today and valid)
        if ($request->filled('active_only') && filter_var($request->active_only, FILTER_VALIDATE_BOOLEAN)) {
            $query->whereDate('effective_date', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', now());
                });
        }

        // Eager load relationships
        $query->with(['creator']);

        // Sort by effective_date descending by default
        $query->orderBy('effective_date', 'desc')->orderBy('created_at', 'desc');

        $rates = $query->paginate($request->per_page ?? 15);

        return ExchangeRateResource::collection($rates);
    }

    /**
     * Store a newly created exchange rate.
     */
    public function store(StoreExchangeRateRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $data['created_by'] = $user->id;

        // Set rate_used if not provided (default to official_rate)
        if (! isset($data['rate_used'])) {
            $data['rate_used'] = $data['official_rate'];
        }

        $exchangeRate = ExchangeRate::create($data);

        return new ExchangeRateResource($exchangeRate->load(['creator']));
    }

    /**
     * Display the specified exchange rate.
     */
    public function show(ExchangeRate $exchangeRate)
    {
        return new ExchangeRateResource($exchangeRate->load(['creator']));
    }

    /**
     * Get the latest exchange rate for a specific currency pair.
     */
    public function latest(Request $request)
    {
        $request->validate([
            'base_currency' => ['required', 'string', 'size:3'],
            'target_currency' => ['required', 'string', 'size:3'],
        ]);

        $rate = ExchangeRate::where('base_currency', $request->base_currency)
            ->where('target_currency', $request->target_currency)
            ->whereDate('effective_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', now());
            })
            ->orderBy('effective_date', 'desc')
            ->first();

        if (! $rate) {
            abort(404, 'No exchange rate found for the specified currency pair.');
        }

        return new ExchangeRateResource($rate->load(['creator']));
    }

    /**
     * Remove the specified exchange rate.
     */
    public function destroy(ExchangeRate $exchangeRate)
    {
        $exchangeRate->delete();

        return response()->noContent();
    }
}
