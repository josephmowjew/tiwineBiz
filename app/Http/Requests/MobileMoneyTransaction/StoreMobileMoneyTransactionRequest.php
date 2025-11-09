<?php

namespace App\Http\Requests\MobileMoneyTransaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMobileMoneyTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'provider' => ['required', 'string', 'in:airtel_money,tnm_mpamba'],
            'transaction_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('mobile_money_transactions')->where(function ($query) {
                    return $query->where('provider', $this->input('provider'));
                }),
            ],
            'transaction_type' => ['required', 'string', 'in:c2b,b2c,b2b'],
            'msisdn' => ['required', 'string', 'regex:/^\+265[0-9]{9}$/'],
            'sender_name' => ['nullable', 'string', 'max:255'],
            'receiver_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['required', 'string', 'size:3'],
            'transaction_fee' => ['nullable', 'numeric', 'min:0'],
            'reference_type' => ['nullable', 'string', 'in:sale,payment,credit,subscription_payment'],
            'reference_id' => ['nullable', 'uuid'],
            'status' => ['required', 'string', 'in:pending,successful,failed,reversed'],
            'request_payload' => ['nullable', 'array'],
            'response_payload' => ['nullable', 'array'],
            'webhook_received_at' => ['nullable', 'date'],
            'webhook_payload' => ['nullable', 'array'],
            'transaction_date' => ['nullable', 'date'],
            'confirmed_at' => ['nullable', 'date'],
            'created_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_id.required' => 'Shop is required.',
            'provider.required' => 'Mobile money provider is required.',
            'provider.in' => 'Provider must be either airtel_money or tnm_mpamba.',
            'transaction_id.required' => 'Transaction ID is required.',
            'transaction_id.unique' => 'A transaction with this ID already exists for this provider.',
            'transaction_type.required' => 'Transaction type is required.',
            'transaction_type.in' => 'Transaction type must be c2b (customer to business), b2c (business to customer), or b2b (business to business).',
            'msisdn.required' => 'Phone number (MSISDN) is required.',
            'msisdn.regex' => 'Phone number must be in Malawian format (+265 followed by 9 digits).',
            'amount.required' => 'Amount is required.',
            'amount.min' => 'Amount must be greater than 0.',
            'currency.required' => 'Currency is required.',
            'status.required' => 'Transaction status is required.',
            'status.in' => 'Status must be pending, successful, failed, or reversed.',
        ];
    }
}
