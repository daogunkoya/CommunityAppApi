<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
            'email' => 'sometimes|email|unique:users,email,' . $this->user()->id,
            'location' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:20',
            'profile_picture' => 'sometimes', // Allow both file uploads and base64 strings - validation handled in controller
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'profile_picture.image' => 'The file must be an image.',
            'profile_picture.mimes' => 'The image must be a JPEG, PNG, or GIF.',
            'profile_picture.max' => 'The image size must be less than 2MB.',
        ];
    }
}
