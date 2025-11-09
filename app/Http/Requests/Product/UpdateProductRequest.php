<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
        $productId = $this->route('product');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'name_chichewa' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'sku' => ['sometimes', 'string', 'max:100', 'unique:products,sku,'.$productId.',id,shop_id,'.$this->shop_id],
            'barcode' => ['nullable', 'string', 'max:100'],
            'manufacturer_code' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'cost_price' => ['sometimes', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'numeric', 'min:0'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'base_currency' => ['nullable', 'string', 'in:MWK,USD,ZAR,GBP,EUR'],
            'base_currency_price' => ['nullable', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'min_stock_level' => ['nullable', 'numeric', 'min:0'],
            'max_stock_level' => ['nullable', 'numeric', 'min:0'],
            'reorder_point' => ['nullable', 'numeric', 'min:0'],
            'reorder_quantity' => ['nullable', 'numeric', 'min:0'],
            'storage_location' => ['nullable', 'string', 'max:100'],
            'shelf' => ['nullable', 'string', 'max:50'],
            'bin' => ['nullable', 'string', 'max:50'],
            'is_vat_applicable' => ['nullable', 'boolean'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_category' => ['nullable', 'string', 'max:50'],
            'primary_supplier_id' => ['nullable', 'uuid', 'exists:suppliers,id'],
            'attributes' => ['nullable', 'array'],
            'images' => ['nullable', 'array'],
            'track_batches' => ['nullable', 'boolean'],
            'track_serial_numbers' => ['nullable', 'boolean'],
            'has_expiry' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
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
            'sku.unique' => 'This SKU is already used in your shop.',
            'category_id.exists' => 'The selected category does not exist.',
            'base_currency.in' => 'Currency must be one of: MWK, USD, ZAR, GBP, EUR.',
        ];
    }
}
