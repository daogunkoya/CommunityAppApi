<?php

namespace App\Services\Auth\Providers;

use App\Contracts\AuthProviderInterface;
use App\Services\Auth\AuthResult;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class EmailAuthAdapter implements AuthProviderInterface
{
    public function authenticate(array $credentials): AuthResult
    {
        try {
            // Validate credentials
            if (!isset($credentials['email']) || !isset($credentials['password'])) {
                return AuthResult::failure('Email and password are required');
            }

            // Find user by email
            $user = User::where('email', $credentials['email'])->first();

            // Check if user exists and password is correct
            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                return AuthResult::failure('The provided credentials are incorrect');
            }

            // Check if user is active
            if (!$user->is_active) {
                return AuthResult::failure('Your account has been deactivated. Please contact support');
            }

            // Check if email is verified
            if (!$user->email_verified_at) {
                return AuthResult::failure('Please verify your email address before logging in');
            }

            // Update last login
            $user->updateLastLogin();

            // Create token
            $tokenResult = $user->createToken('auth-token');
            $token = $tokenResult->accessToken;

            return AuthResult::success($user, $token);
        } catch (\Exception $e) {
            return AuthResult::failure('Authentication failed: ' . $e->getMessage());
        }
    }

    public function verifyToken(string $token): bool
    {
        // For email auth, we don't need to verify external tokens
        return true;
    }

    public function getUserInfo(string $token): array
    {
        // For email auth, we don't get user info from external provider
        return [];
    }
}
