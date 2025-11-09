<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'credit_id' => ['nullable', 'uuid', 'exists:credits,id'],
            'sale_id' => ['nullable', 'uuid', 'exists:sales,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'currency' => ['nullable', 'string', 'in:MWK,USD,ZAR,GBP,EUR'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'payment_method' => ['required', 'string', 'in:cash,airtel_money,tnm_mpamba,nbs_bank,standard_bank,fmb_bank,natswitch,bank_transfer,cheque,other'],
            'transaction_reference' => ['nullable', 'string', 'max:100'],
            'mobile_money_details' => ['nullable', 'array'],
            'mobile_money_details.phone' => ['required_if:payment_method,airtel_money,tnm_mpamba', 'string'],
            'mobile_money_details.sender_name' => ['nullable', 'string'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'cheque_number' => ['required_if:payment_method,cheque', 'string', 'max:50'],
            'cheque_date' => ['required_if:payment_method,cheque', 'date'],
            'payment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'shop_id.required' => 'Shop ID is required.',
            'shop_id.exists' => 'The selected shop does not exist.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'credit_id.exists' => 'The selected credit does not exist.',
            'sale_id.exists' => 'The selected sale does not exist.',
            'amount.required' => 'Payment amount is required.',
            'amount.min' => 'Payment amount must be at least 0.01.',
            'currency.in' => 'Currency must be one of: MWK, USD, ZAR, GBP, EUR.',
            'payment_method.required' => 'Payment method is required.',
            'payment_method.in' => 'Invalid payment method selected.',
            'mobile_money_details.phone.required_if' => 'Phone number is required for mobile money payments.',
            'cheque_number.required_if' => 'Cheque number is required for cheque payments.',
            'cheque_date.required_if' => 'Cheque date is required for cheque payments.',
        ];
    }
}
