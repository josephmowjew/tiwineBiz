<?php

namespace App\Http\Requests\Sale;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
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
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'amount_paid' => ['required', 'numeric', 'min:0'],
            'balance' => ['nullable', 'numeric'],
            'change_given' => ['nullable', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'in:paid,partial,unpaid,credit'],
            'payment_methods' => ['nullable', 'array'],
            'currency' => ['nullable', 'string', 'in:MWK,USD,ZAR,GBP,EUR'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'sale_type' => ['required', 'string', 'in:pos,whatsapp,phone_order,online'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],
            'sale_date' => ['nullable', 'date'],

            // Sale items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.batch_id' => ['nullable', 'uuid', 'exists:product_batches,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.is_taxable' => ['nullable', 'boolean'],
            'items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes' => ['nullable', 'string'],
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
            'subtotal.required' => 'Subtotal is required.',
            'total_amount.required' => 'Total amount is required.',
            'amount_paid.required' => 'Amount paid is required.',
            'payment_status.required' => 'Payment status is required.',
            'payment_status.in' => 'Payment status must be: paid, partial, unpaid, or credit.',
            'sale_type.required' => 'Sale type is required.',
            'sale_type.in' => 'Sale type must be: pos, whatsapp, phone_order, or online.',
            'items.required' => 'At least one sale item is required.',
            'items.min' => 'At least one sale item is required.',
            'items.*.product_id.required' => 'Product ID is required for each item.',
            'items.*.product_id.exists' => 'One or more selected products do not exist.',
            'items.*.quantity.required' => 'Quantity is required for each item.',
            'items.*.unit_price.required' => 'Unit price is required for each item.',
        ];
    }
}
