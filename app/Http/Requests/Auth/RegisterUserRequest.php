<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
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
            'fullName' => ['required', 'string', 'max:255'],
            'dateOfBirth' => ['required', 'date', 'before:13 years ago', 'after:1900-01-01'],
            'gender' => ['nullable', 'in:male,female,prefer-not-to-say,prefer_not_to_say'],
            'location' => ['nullable', 'string'],
            'radius' => ['integer', 'min:1', 'max:50'],
            'selectedSports' => ['required', 'array', 'min:1'],
            'selectedSports.*' => ['exists:game_types,id'],
            'skillLevels' => ['required', 'array'],
            'skillLevels.*' => ['in:beginner,intermediate,advanced,expert'],
            'mainGoal' => ['required', 'string'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required_if:authProvider,email', 'nullable', 'string', 'min:8'],
            'authProvider' => ['required', 'in:email,facebook,google,apple'],
            'authProviderId' => ['nullable', 'string'],
        ];
    }
}
