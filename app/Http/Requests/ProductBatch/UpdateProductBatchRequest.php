<?php

namespace App\Http\Requests\ProductBatch;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_number' => ['sometimes', 'string', 'max:255'],
            'lot_number' => ['sometimes', 'string', 'max:255'],
            'unit_cost' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'product_cost' => ['sometimes', 'numeric', 'min:0'],
            'freight_cost' => ['sometimes', 'numeric', 'min:0'],
            'customs_duty' => ['sometimes', 'numeric', 'min:0'],
            'clearing_fee' => ['sometimes', 'numeric', 'min:0'],
            'other_costs' => ['sometimes', 'numeric', 'min:0'],
            'manufacture_date' => ['sometimes', 'date', 'before_or_equal:today'],
            'expiry_date' => ['sometimes', 'date'],
            'notes' => ['sometimes', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'manufacture_date.before_or_equal' => 'Manufacture date cannot be in the future.',
        ];
    }
}
