<?php

namespace App\Http\Requests\SubscriptionPayment;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscription_id' => ['required', 'uuid', 'exists:subscriptions,id'],
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'payment_number' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('subscription_payments')->where(function ($query) {
                    return $query->where('shop_id', $this->input('shop_id'));
                }),
            ],
            'invoice_number' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_method' => ['nullable', 'string', 'in:airtel_money,tnm_mpamba,bank_transfer,cash'],
            'transaction_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:pending,confirmed,failed,refunded'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after:period_start'],
            'payment_date' => ['nullable', 'date'],
            'confirmed_at' => ['nullable', 'date'],
            'confirmed_by' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'subscription_id.required' => 'Subscription is required.',
            'shop_id.required' => 'Shop is required.',
            'payment_number.unique' => 'A payment with this number already exists for this shop.',
            'amount.required' => 'Payment amount is required.',
            'amount.min' => 'Amount must be greater than 0.',
            'currency.required' => 'Currency is required.',
            'payment_method.in' => 'Payment method must be airtel_money, tnm_mpamba, bank_transfer, or cash.',
            'status.required' => 'Payment status is required.',
            'status.in' => 'Status must be pending, confirmed, failed, or refunded.',
            'period_start.required' => 'Billing period start is required.',
            'period_end.required' => 'Billing period end is required.',
            'period_end.after' => 'Period end must be after period start.',
        ];
    }
}
