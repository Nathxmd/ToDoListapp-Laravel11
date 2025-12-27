<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authenticated users only (handled by middleware)
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:500'],
            'email_notifications' => ['sometimes', 'boolean'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'theme_color' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'font_size' => ['sometimes', 'string', Rule::in(['small', 'medium', 'large'])],
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
            'name.string' => 'Name must be a string',
            'name.max' => 'Name cannot exceed 255 characters',
            'email.email' => 'Please provide a valid email address',
            'email.unique' => 'This email is already taken',
            'timezone.timezone' => 'Please provide a valid timezone',
            'theme_color.regex' => 'Theme color must be a valid HEX color code',
            'font_size.in' => 'Font size must be small, medium, or large',
        ];
    }
}
