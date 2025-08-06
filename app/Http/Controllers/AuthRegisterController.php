<?php

namespace App\Http\Controllers;

use App\Http\Resources\AuthResource;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;

class AuthRegisterController extends Controller
{
    public function __invoke(Request $request)
    {
        $validatedRequest = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'password_confirmation' => ['required'],
            'location' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female,other,prefer_not_to_say'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        try {
            DB::beginTransaction();

            // Hash the password before creating user
            $validatedRequest['password'] = Hash::make($validatedRequest['password']);

            // Remove password_confirmation as it's not needed in the database
            unset($validatedRequest['password_confirmation']);

            // Create user
            $user = User::create($validatedRequest);

            // Generate email verification token
            $verificationToken = $user->generateEmailVerificationToken();

            // Send email verification notification
            $user->notify(new EmailVerificationNotification($verificationToken));

            DB::commit();

            // Return response without token (user needs to verify email first)
            return response()->json([
                'message' => 'Registration successful! Please check your email to verify your account.',
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ],
                'requires_verification' => true,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw ValidationException::withMessages([
                'email' => ['Registration failed. Please try again.'],
            ]);
        }
    }
}
