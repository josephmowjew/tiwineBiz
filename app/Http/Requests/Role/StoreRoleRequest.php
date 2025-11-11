<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_]+$/'],
            'display_name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'max:100'],
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
            'name.required' => 'Role name is required.',
            'name.regex' => 'Role name must contain only lowercase letters, numbers, and underscores.',
            'display_name.required' => 'Display name is required.',
            'permissions.required' => 'At least one permission is required.',
            'permissions.min' => 'At least one permission is required.',
            'permissions.*.required' => 'Permission cannot be empty.',
        ];
    }
}
