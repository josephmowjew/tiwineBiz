<?php

namespace App\Http\Requests\StockMovement;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockMovementRequest extends FormRequest
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
        $rules = [
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'batch_id' => ['nullable', 'uuid', 'exists:product_batches,id'],
            'movement_type' => [
                'required',
                'string',
                'in:sale,purchase,return_from_customer,return_to_supplier,adjustment_increase,adjustment_decrease,damage,theft,expired,transfer_out,transfer_in,stocktake,opening_balance',
            ],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference_type' => ['nullable', 'string', 'max:50'],
            'reference_id' => ['nullable', 'uuid'],
            'notes' => ['nullable', 'string'],
            'from_location' => ['nullable', 'string', 'max:100'],
            'to_location' => ['nullable', 'string', 'max:100'],
        ];

        // Reason is required for certain movement types
        $reasonRequiredTypes = [
            'adjustment_increase', 'adjustment_decrease', 'damage',
            'theft', 'expired', 'stocktake',
        ];

        if (in_array($this->movement_type, $reasonRequiredTypes)) {
            $rules['reason'] = ['required', 'string'];
        } else {
            $rules['reason'] = ['nullable', 'string'];
        }

        return $rules;
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
            'product_id.required' => 'Product ID is required.',
            'product_id.exists' => 'The selected product does not exist.',
            'batch_id.exists' => 'The selected product batch does not exist.',
            'movement_type.required' => 'Movement type is required.',
            'movement_type.in' => 'Invalid movement type selected.',
            'quantity.required' => 'Quantity is required.',
            'quantity.min' => 'Quantity must be at least 0.001.',
            'unit_cost.min' => 'Unit cost cannot be negative.',
            'reason.required' => 'Reason is required for this movement type.',
            'reference_type.max' => 'Reference type cannot exceed 50 characters.',
            'from_location.max' => 'From location cannot exceed 100 characters.',
            'to_location.max' => 'To location cannot exceed 100 characters.',
        ];
    }
}
