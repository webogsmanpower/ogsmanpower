<?php

namespace App\Http\Requests\API;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Facades\Log;

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
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'mobile' => ['nullable', 'string', 'max:20', 'unique:users,mobile'],
            'phone_number' => ['nullable', 'string', 'max:20', 'unique:users,mobile'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'headline' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'current_location' => ['nullable', 'string', 'max:255'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:50'],
            'is_profile_complete' => ['nullable', 'boolean'],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['string', 'max:100'],
            'bio' => ['nullable', 'string'],
            'resume_path' => ['nullable', 'string', 'max:255'],
            'accept_terms' => ['nullable', 'boolean'],
            'subscribe_updates' => ['nullable', 'boolean'],
            'module' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered. Please use a different email or sign in.',
            'mobile.unique' => 'This phone number is already registered. Please use a different phone number.',
            'phone_number.unique' => 'This phone number is already registered. Please use a different phone number.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        Log::info('Seeker registration validation failed', [
            'errors' => $validator->errors()->toArray(),
            'request_data' => $this->except(['password', 'password_confirmation'])
        ]);

        parent::failedValidation($validator);
    }
}
