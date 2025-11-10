<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBranchRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Branch::class);
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
            'code' => ['required', 'string', 'max:50'],
            'branch_type' => ['required', 'in:main,satellite,warehouse,kiosk'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'manager_id' => ['nullable', 'uuid', 'exists:users,id'],
            'is_active' => ['boolean'],
            'opened_at' => ['nullable', 'date'],
            'settings' => ['nullable', 'array'],
            'features' => ['nullable', 'array'],
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'shop_id.required' => 'Shop is required',
            'shop_id.exists' => 'Selected shop does not exist',
            'name.required' => 'Branch name is required',
            'code.required' => 'Branch code is required',
            'branch_type.required' => 'Branch type is required',
            'branch_type.in' => 'Branch type must be one of: main, satellite, warehouse, kiosk',
            'email.email' => 'Please provide a valid email address',
            'manager_id.exists' => 'Selected manager does not exist',
        ];
    }
}
