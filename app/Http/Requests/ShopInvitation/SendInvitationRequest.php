<?php

namespace App\Http\Requests\ShopInvitation;

use Illuminate\Foundation\Http\FormRequest;

class SendInvitationRequest extends FormRequest
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
            'shop_id' => ['required', 'string', 'exists:shops,id'],
            'email' => ['required', 'email', 'max:255'],
            'role_id' => ['required', 'string', 'exists:roles,id'],
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
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'role_id.required' => 'Role ID is required.',
            'role_id.exists' => 'The selected role does not exist.',
        ];
    }
}
