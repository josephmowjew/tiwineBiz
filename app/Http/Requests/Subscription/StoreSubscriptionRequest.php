<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'plan' => ['required', 'string', 'in:free,business,professional,enterprise'],
            'billing_cycle' => ['required', 'string', 'in:monthly,annual'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'in:active,cancelled,suspended,grace_period,expired'],
            'started_at' => ['nullable', 'date'],
            'current_period_start' => ['nullable', 'date'],
            'current_period_end' => ['nullable', 'date', 'after:current_period_start'],
            'cancelled_at' => ['nullable', 'date'],
            'cancel_reason' => ['nullable', 'string'],
            'cancel_at_period_end' => ['nullable', 'boolean'],
            'trial_ends_at' => ['nullable', 'date', 'after:started_at'],
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_id.required' => 'Shop is required.',
            'plan.required' => 'Subscription plan is required.',
            'plan.in' => 'Plan must be free, business, professional, or enterprise.',
            'billing_cycle.required' => 'Billing cycle is required.',
            'billing_cycle.in' => 'Billing cycle must be monthly or annual.',
            'amount.required' => 'Subscription amount is required.',
            'amount.min' => 'Amount cannot be negative.',
            'currency.required' => 'Currency is required.',
            'status.in' => 'Status must be active, cancelled, suspended, grace_period, or expired.',
            'current_period_end.after' => 'Period end must be after period start.',
            'trial_ends_at.after' => 'Trial end must be after subscription start.',
        ];
    }
}
