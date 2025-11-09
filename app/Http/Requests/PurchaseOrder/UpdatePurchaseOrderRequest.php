<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:draft,sent,confirmed,in_transit,at_border,clearing,received,partial,cancelled'],
            'expected_delivery_date' => ['sometimes', 'date'],
            'actual_delivery_date' => ['sometimes', 'date'],
            'shipping_method' => ['sometimes', 'string', 'max:100'],
            'tracking_number' => ['sometimes', 'string', 'max:100'],
            'border_point' => ['sometimes', 'string', 'in:Mwanza,Dedza,Songwe,Mchinji,Muloza,Chiponde,Nakonde,Karonga,Chilumba,Other'],
            'clearing_agent_name' => ['sometimes', 'string', 'max:255'],
            'clearing_agent_phone' => ['sometimes', 'string', 'max:20'],
            'customs_entry_number' => ['sometimes', 'string', 'max:100'],
            'freight_cost' => ['sometimes', 'numeric', 'min:0'],
            'insurance_cost' => ['sometimes', 'numeric', 'min:0'],
            'customs_duty' => ['sometimes', 'numeric', 'min:0'],
            'clearing_fee' => ['sometimes', 'numeric', 'min:0'],
            'transport_cost' => ['sometimes', 'numeric', 'min:0'],
            'other_charges' => ['sometimes', 'numeric', 'min:0'],
            'tax_amount' => ['sometimes', 'numeric', 'min:0'],
            'documents' => ['sometimes', 'array'],
            'notes' => ['sometimes', 'string'],
            'internal_notes' => ['sometimes', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Invalid status selected.',
            'expected_delivery_date.date' => 'Expected delivery date must be a valid date.',
            'actual_delivery_date.date' => 'Actual delivery date must be a valid date.',
        ];
    }
}
