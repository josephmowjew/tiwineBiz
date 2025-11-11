<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ImportProductsRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'], // 10MB max
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
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
            'file.required' => 'Import file is required.',
            'file.file' => 'The uploaded file must be a valid file.',
            'file.mimes' => 'Import file must be an Excel file (xlsx, xls) or CSV file.',
            'file.max' => 'Import file size must not exceed 10MB.',
            'shop_id.required' => 'Shop ID is required.',
            'shop_id.uuid' => 'Shop ID must be a valid UUID.',
            'shop_id.exists' => 'The specified shop does not exist.',
        ];
    }
}
