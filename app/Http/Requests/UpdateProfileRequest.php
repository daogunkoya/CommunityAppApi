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
            'profile_picture' => 'sometimes|file|image|mimes:jpeg,jpg,png,gif|max:2048|string', // 2MB max or base64 string (server limit)
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
