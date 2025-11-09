<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^(\+265|0)[1-9]\d{8}$/', 'unique:users,phone'],
            'password' => ['required', 'string', Password::min(8)->mixedCase()->numbers(), 'confirmed'],
            'profile_photo_url' => ['nullable', 'string', 'url', 'max:500'],
            'preferred_language' => ['nullable', 'string', 'in:en,ny'],
            'timezone' => ['nullable', 'string', 'timezone'],
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
            'name.required' => 'Full name is required.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone.required' => 'Phone number is required.',
            'phone.regex' => 'Phone number must be in Malawian format: +265XXXXXXXXX or 0XXXXXXXXX.',
            'phone.unique' => 'This phone number is already registered.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Password confirmation does not match.',
            'profile_photo_url.url' => 'Profile photo URL must be a valid URL.',
            'preferred_language.in' => 'Language must be either en (English) or ny (Chichewa).',
            'timezone.timezone' => 'Please provide a valid timezone.',
        ];
    }
}
