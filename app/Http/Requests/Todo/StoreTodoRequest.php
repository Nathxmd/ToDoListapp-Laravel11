<?php

namespace App\Http\Requests\Todo;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTodoRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['sometimes', 'string', Rule::in(['low', 'medium', 'high'])],
            'due_date' => ['nullable', 'date', 'after_or_equal:today'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
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
            'title.required' => 'Todo title is required',
            'title.max' => 'Title cannot exceed 255 characters',
            'priority.in' => 'Priority must be low, medium, or high',
            'due_date.after_or_equal' => 'Due date cannot be in the past',
            'category_id.exists' => 'Selected category does not exist',
        ];
    }
}
