<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'supplier_id' => ['required', 'uuid', 'exists:suppliers,id'],
            'po_number' => ['nullable', 'string', 'max:50'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'freight_cost' => ['nullable', 'numeric', 'min:0'],
            'insurance_cost' => ['nullable', 'numeric', 'min:0'],
            'customs_duty' => ['nullable', 'numeric', 'min:0'],
            'clearing_fee' => ['nullable', 'numeric', 'min:0'],
            'transport_cost' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'amount_in_base_currency' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:draft,sent,confirmed,in_transit,at_border,clearing,received,partial,cancelled'],
            'order_date' => ['required', 'date'],
            'expected_delivery_date' => ['nullable', 'date', 'after_or_equal:order_date'],
            'shipping_method' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:100'],
            'border_point' => ['nullable', 'string', 'in:Mwanza,Dedza,Songwe,Mchinji,Muloza,Chiponde,Nakonde,Karonga,Chilumba,Other'],
            'clearing_agent_name' => ['nullable', 'string', 'max:255'],
            'clearing_agent_phone' => ['nullable', 'string', 'max:20'],
            'customs_entry_number' => ['nullable', 'string', 'max:100'],
            'documents' => ['nullable', 'array'],
            'notes' => ['nullable', 'string'],
            'internal_notes' => ['nullable', 'string'],

            // Items validation
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.product_name' => ['required', 'string', 'max:255'],
            'items.*.product_code' => ['nullable', 'string', 'max:255'],
            'items.*.quantity_ordered' => ['required', 'numeric', 'min:0.001'],
            'items.*.quantity_received' => ['nullable', 'numeric', 'min:0'],
            'items.*.unit' => ['required', 'string', 'max:255'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_id.required' => 'Shop is required.',
            'supplier_id.required' => 'Supplier is required.',
            'order_date.required' => 'Order date is required.',
            'expected_delivery_date.after_or_equal' => 'Expected delivery date must be on or after order date.',
            'items.required' => 'At least one item is required.',
            'items.min' => 'At least one item is required.',
            'items.*.product_id.required' => 'Product is required for each item.',
            'items.*.quantity_ordered.required' => 'Quantity ordered is required for each item.',
            'items.*.unit_price.required' => 'Unit price is required for each item.',
        ];
    }
}
