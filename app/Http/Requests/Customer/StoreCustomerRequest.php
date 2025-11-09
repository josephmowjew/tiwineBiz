<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerRequest extends FormRequest
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
            'shop_id' => ['required', 'uuid', 'exists:shops,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'regex:/^(\+265|0)[1-9]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'regex:/^(\+265|0)[1-9]\d{8}$/'],
            'physical_address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'trust_level' => ['nullable', 'string', 'in:trusted,monitor,restricted,new'],
            'preferred_language' => ['nullable', 'string', 'in:en,ny'],
            'preferred_contact_method' => ['nullable', 'string', 'in:phone,email,whatsapp,sms'],
            'notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
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
            'shop_id.required' => 'Shop ID is required.',
            'shop_id.exists' => 'The selected shop does not exist.',
            'name.required' => 'Customer name is required.',
            'phone.regex' => 'Phone number must be in Malawian format: +265XXXXXXXXX or 0XXXXXXXXX.',
            'whatsapp_number.regex' => 'WhatsApp number must be in Malawian format: +265XXXXXXXXX or 0XXXXXXXXX.',
            'trust_level.in' => 'Trust level must be: trusted, monitor, restricted, or new.',
            'preferred_language.in' => 'Language must be either en (English) or ny (Chichewa).',
            'preferred_contact_method.in' => 'Contact method must be: phone, email, whatsapp, or sms.',
        ];
    }
}
