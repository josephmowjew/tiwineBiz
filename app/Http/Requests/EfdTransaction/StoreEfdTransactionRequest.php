<?php

namespace App\Http\Requests\EfdTransaction;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEfdTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'efd_device_id' => ['required', 'string', 'max:255'],
            'efd_device_serial' => ['required', 'string', 'max:255'],
            'sale_id' => ['required', 'uuid', 'exists:sales,id'],
            'fiscal_receipt_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('efd_transactions')->where(function ($query) {
                    return $query->where('efd_device_id', $this->input('efd_device_id'));
                }),
            ],
            'fiscal_day_counter' => ['required', 'integer', 'min:1'],
            'fiscal_signature' => ['required', 'string'],
            'qr_code_data' => ['required', 'string'],
            'verification_url' => ['nullable', 'string', 'url', 'max:1000'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'vat_amount' => ['required', 'numeric', 'min:0'],
            'mra_response_code' => ['nullable', 'string', 'max:255'],
            'mra_response_message' => ['nullable', 'string'],
            'mra_acknowledgement' => ['nullable', 'array'],
            'transmitted_at' => ['nullable', 'date'],
            'transmission_status' => ['required', 'string', 'in:success,failed,pending,offline'],
            'retry_count' => ['nullable', 'integer', 'min:0', 'max:100'],
            'last_retry_at' => ['nullable', 'date'],
            'next_retry_at' => ['nullable', 'date', 'after_or_equal:transmitted_at'],
            'created_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'shop_id.required' => 'Shop is required.',
            'efd_device_id.required' => 'EFD device ID is required.',
            'efd_device_serial.required' => 'EFD device serial number is required.',
            'sale_id.required' => 'Sale reference is required.',
            'sale_id.exists' => 'The specified sale does not exist.',
            'fiscal_receipt_number.required' => 'Fiscal receipt number is required.',
            'fiscal_receipt_number.unique' => 'A fiscal receipt with this number already exists for this EFD device.',
            'fiscal_day_counter.required' => 'Fiscal day counter is required.',
            'fiscal_day_counter.min' => 'Fiscal day counter must be at least 1.',
            'fiscal_signature.required' => 'Fiscal signature is required for MRA compliance.',
            'qr_code_data.required' => 'QR code data is required for receipt verification.',
            'verification_url.url' => 'Verification URL must be a valid URL.',
            'total_amount.required' => 'Total amount is required.',
            'total_amount.min' => 'Total amount cannot be negative.',
            'vat_amount.required' => 'VAT amount is required.',
            'vat_amount.min' => 'VAT amount cannot be negative.',
            'transmission_status.required' => 'Transmission status is required.',
            'transmission_status.in' => 'Transmission status must be success, failed, pending, or offline.',
            'retry_count.min' => 'Retry count cannot be negative.',
            'retry_count.max' => 'Retry count cannot exceed 100.',
            'next_retry_at.after_or_equal' => 'Next retry time must be after or equal to transmission time.',
        ];
    }
}
