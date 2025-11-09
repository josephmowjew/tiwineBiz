<?php

namespace App\Http\Requests\Subscription;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'plan' => ['nullable', 'string', 'in:free,business,professional,enterprise'],
            'billing_cycle' => ['nullable', 'string', 'in:monthly,annual'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', 'string', 'in:active,cancelled,suspended,grace_period,expired'],
            'current_period_start' => ['nullable', 'date'],
            'current_period_end' => ['nullable', 'date'],
            'cancelled_at' => ['nullable', 'date'],
            'cancel_reason' => ['nullable', 'string'],
            'cancel_at_period_end' => ['nullable', 'boolean'],
            'trial_ends_at' => ['nullable', 'date'],
            'features' => ['nullable', 'array'],
            'limits' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'plan.in' => 'Plan must be free, business, professional, or enterprise.',
            'billing_cycle.in' => 'Billing cycle must be monthly or annual.',
            'amount.min' => 'Amount cannot be negative.',
            'status.in' => 'Status must be active, cancelled, suspended, grace_period, or expired.',
        ];
    }
}
