<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class TransferStockRequest extends FormRequest
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
            'from_branch_id' => ['required', 'uuid', 'exists:branches,id'],
            'to_branch_id' => ['required', 'uuid', 'exists:branches,id', 'different:from_branch_id'],
            'quantity' => ['required', 'numeric', 'min:0.001', 'max:999999.999'],
            'reason' => ['required', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:5000'],
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
            'from_branch_id.required' => 'Source branch is required.',
            'from_branch_id.uuid' => 'Source branch ID must be a valid UUID.',
            'from_branch_id.exists' => 'Source branch does not exist.',
            'to_branch_id.required' => 'Destination branch is required.',
            'to_branch_id.uuid' => 'Destination branch ID must be a valid UUID.',
            'to_branch_id.exists' => 'Destination branch does not exist.',
            'to_branch_id.different' => 'Destination branch must be different from source branch.',
            'quantity.required' => 'Transfer quantity is required.',
            'quantity.numeric' => 'Quantity must be a number.',
            'quantity.min' => 'Quantity must be greater than 0.',
            'quantity.max' => 'Quantity cannot exceed 999999.999.',
            'reason.required' => 'Reason for transfer is required.',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
            'notes.max' => 'Notes cannot exceed 5000 characters.',
        ];
    }
}
