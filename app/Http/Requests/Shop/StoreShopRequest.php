<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class StoreShopRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'business_type' => ['required', 'string', 'in:retail,wholesale,restaurant,service,other'],
            'legal_name' => ['nullable', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:100'],
            'tpin' => ['nullable', 'string', 'regex:/^\d{10}$/'],
            'vrn' => ['nullable', 'string', 'max:50'],
            'is_vat_registered' => ['nullable', 'boolean'],
            'phone' => ['required', 'string', 'regex:/^(\+265|0)[1-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'default_currency' => ['nullable', 'string', 'in:MWK,USD,ZAR,GBP,EUR'],
            'fiscal_year_start_month' => ['nullable', 'integer', 'between:1,12'],
            'primary_color' => ['nullable', 'string', 'max:7'],
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
            'name.required' => 'Shop name is required.',
            'business_type.required' => 'Business type is required.',
            'business_type.in' => 'Business type must be: retail, wholesale, restaurant, service, or other.',
            'tpin.regex' => 'TPIN must be exactly 10 digits.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must be in Malawian format: +265XXXXXXXXX or 0XXXXXXXXX.',
            'default_currency.in' => 'Currency must be one of: MWK, USD, ZAR, GBP, EUR.',
            'fiscal_year_start_month.between' => 'Fiscal year start month must be between 1 and 12.',
        ];
    }
}
