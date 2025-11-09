<?php

namespace App\Http\Requests\ProductBatch;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'purchase_order_id' => ['nullable', 'uuid', 'exists:purchase_orders,id'],
            'supplier_id' => ['nullable', 'uuid', 'exists:suppliers,id'],
            'batch_number' => ['nullable', 'string', 'max:255'],
            'lot_number' => ['nullable', 'string', 'max:255'],
            'initial_quantity' => ['required', 'numeric', 'min:0.001'],
            'remaining_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'product_cost' => ['nullable', 'numeric', 'min:0'],
            'freight_cost' => ['nullable', 'numeric', 'min:0'],
            'customs_duty' => ['nullable', 'numeric', 'min:0'],
            'clearing_fee' => ['nullable', 'numeric', 'min:0'],
            'other_costs' => ['nullable', 'numeric', 'min:0'],
            'total_landed_cost' => ['nullable', 'numeric', 'min:0'],
            'purchase_date' => ['required', 'date'],
            'manufacture_date' => ['nullable', 'date', 'before_or_equal:today'],
            'expiry_date' => ['nullable', 'date', 'after:manufacture_date'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'Product is required.',
            'initial_quantity.required' => 'Initial quantity is required.',
            'initial_quantity.min' => 'Initial quantity must be greater than 0.',
            'purchase_date.required' => 'Purchase date is required.',
            'manufacture_date.before_or_equal' => 'Manufacture date cannot be in the future.',
            'expiry_date.after' => 'Expiry date must be after manufacture date.',
        ];
    }
}
