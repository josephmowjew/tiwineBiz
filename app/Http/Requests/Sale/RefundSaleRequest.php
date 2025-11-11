<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;

class RefundSaleRequest extends FormRequest
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
            'refund_amount' => ['required', 'numeric', 'min:0.01', 'max:999999999.99'],
            'refund_reason' => ['required', 'string', 'max:1000'],
            'refund_method' => ['required', 'string', 'in:cash,mobile_money,bank_transfer,card,credit_note'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['nullable', 'array'],
            'items.*.sale_item_id' => ['required', 'uuid', 'exists:sale_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:999999.999'],
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
            'refund_amount.required' => 'Refund amount is required.',
            'refund_amount.numeric' => 'Refund amount must be a number.',
            'refund_amount.min' => 'Refund amount must be greater than 0.',
            'refund_amount.max' => 'Refund amount cannot exceed 999999999.99.',
            'refund_reason.required' => 'Reason for refund is required.',
            'refund_reason.max' => 'Refund reason cannot exceed 1000 characters.',
            'refund_method.required' => 'Refund method is required.',
            'refund_method.in' => 'Refund method must be one of: cash, mobile_money, bank_transfer, card, credit_note.',
            'notes.max' => 'Notes cannot exceed 5000 characters.',
            'items.*.sale_item_id.required' => 'Sale item ID is required for each refund item.',
            'items.*.sale_item_id.uuid' => 'Sale item ID must be a valid UUID.',
            'items.*.sale_item_id.exists' => 'Sale item does not exist.',
            'items.*.quantity.required' => 'Quantity is required for each refund item.',
            'items.*.quantity.numeric' => 'Quantity must be a number.',
            'items.*.quantity.min' => 'Quantity must be greater than 0.',
        ];
    }
}
