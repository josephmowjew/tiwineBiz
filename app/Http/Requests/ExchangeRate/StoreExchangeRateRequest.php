<?php

namespace App\Http\Requests\ExchangeRate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExchangeRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'base_currency' => ['required', 'string', 'size:3'],
            'target_currency' => ['required', 'string', 'size:3'],
            'official_rate' => ['required', 'numeric', 'min:0.0001'],
            'street_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'rate_used' => ['nullable', 'numeric', 'min:0.0001'],
            'effective_date' => [
                'required',
                'date',
                Rule::unique('exchange_rates', 'effective_date')
                    ->where('target_currency', $this->input('target_currency')),
            ],
            'valid_until' => ['nullable', 'date', 'after_or_equal:effective_date'],
            'source' => ['nullable', 'string', 'in:RBM,manual,API,street_market,bank'],
        ];
    }

    public function messages(): array
    {
        return [
            'base_currency.required' => 'Base currency is required.',
            'base_currency.size' => 'Base currency must be a 3-letter code (e.g., MWK).',
            'target_currency.required' => 'Target currency is required.',
            'target_currency.size' => 'Target currency must be a 3-letter code (e.g., USD).',
            'official_rate.required' => 'Official exchange rate is required.',
            'official_rate.min' => 'Official rate must be greater than 0.',
            'effective_date.required' => 'Effective date is required.',
            'effective_date.unique' => 'An exchange rate for this currency and date already exists.',
            'valid_until.after_or_equal' => 'Valid until date must be on or after the effective date.',
        ];
    }
}
