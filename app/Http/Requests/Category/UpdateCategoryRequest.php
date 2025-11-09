<?php

namespace App\Http\Requests\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
        $categoryId = $this->route('category');
        $category = \App\Models\Category::find($categoryId);

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'name_chichewa' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:categories,slug,'.$categoryId.',id,shop_id,'.($category ? $category->shop_id : '')],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'icon' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:7'],
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
            'slug.unique' => 'This category slug is already used.',
            'parent_id.exists' => 'The selected parent category does not exist.',
        ];
    }
}
