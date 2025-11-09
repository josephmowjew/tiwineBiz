<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class StoreCreditRequest extends FormRequest
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
            'customer_id' => ['required', 'uuid', 'exists:customers,id'],
            'sale_id' => ['nullable', 'uuid', 'exists:sales,id'],
            'original_amount' => ['required', 'numeric', 'min:0.01'],
            'amount_paid' => ['nullable', 'numeric', 'min:0'],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'payment_term' => ['nullable', 'string', 'in:lero,mawa,sabata_imeneyi,malipiro_15,malipiro_30,masabata_2,mwezi_umodzi,miyezi_2,miyezi_3,custom'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
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
            'customer_id.required' => 'Customer ID is required.',
            'customer_id.exists' => 'The selected customer does not exist.',
            'sale_id.exists' => 'The selected sale does not exist.',
            'original_amount.required' => 'Original credit amount is required.',
            'original_amount.min' => 'Credit amount must be at least 0.01.',
            'issue_date.required' => 'Issue date is required.',
            'due_date.required' => 'Due date is required.',
            'due_date.after_or_equal' => 'Due date must be on or after the issue date.',
            'payment_term.in' => 'Invalid payment term selected.',
        ];
    }
}
