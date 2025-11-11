<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjustStockRequest extends FormRequest
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
            'type' => ['required', Rule::in(['increase', 'decrease'])],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:999999.999'],
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'unit_cost' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
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
            'type.required' => 'Adjustment type is required.',
            'type.in' => 'Adjustment type must be either "increase" or "decrease".',
            'quantity.required' => 'Quantity is required.',
            'quantity.numeric' => 'Quantity must be a number.',
            'quantity.min' => 'Quantity must be greater than 0.',
            'quantity.max' => 'Quantity cannot exceed 999999.999.',
            'reason.required' => 'Reason for adjustment is required.',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
            'notes.max' => 'Notes cannot exceed 5000 characters.',
            'unit_cost.numeric' => 'Unit cost must be a number.',
            'unit_cost.min' => 'Unit cost cannot be negative.',
            'unit_cost.max' => 'Unit cost cannot exceed 999999999.99.',
        ];
    }
}
