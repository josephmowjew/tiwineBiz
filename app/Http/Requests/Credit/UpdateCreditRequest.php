<?php

namespace App\Http\Requests\Credit;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCreditRequest extends FormRequest
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
            'amount_paid' => ['sometimes', 'numeric', 'min:0'],
            'due_date' => ['sometimes', 'date'],
            'payment_term' => ['sometimes', 'string', 'in:lero,mawa,sabata_imeneyi,malipiro_15,malipiro_30,masabata_2,mwezi_umodzi,miyezi_2,miyezi_3,custom'],
            'status' => ['sometimes', 'string', 'in:pending,partial,paid,overdue,written_off,disputed'],
            'notes' => ['sometimes', 'string'],
            'internal_notes' => ['sometimes', 'string'],
            'next_reminder_date' => ['sometimes', 'date'],
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
            'amount_paid.min' => 'Amount paid cannot be negative.',
            'payment_term.in' => 'Invalid payment term selected.',
            'status.in' => 'Invalid status selected.',
        ];
    }
}
