<?php

namespace App\Services\Auth\Providers;

use App\Contracts\AuthProviderInterface;
use App\Services\Auth\AuthResult;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AppleAuthAdapter implements AuthProviderInterface
{
    public function authenticate(array $credentials): AuthResult
    {
        try {
            // Validate credentials
            if (!isset($credentials['access_token']) || !isset($credentials['provider_id'])) {
                return AuthResult::failure('Access token and provider ID are required');
            }

            $accessToken = $credentials['access_token'];
            $providerId = $credentials['provider_id'];
            $profile = $credentials['profile'] ?? [];

            // Verify the Apple token
            if (!$this->verifyToken($accessToken)) {
                return AuthResult::failure('Invalid Apple authentication token');
            }

            // Extract user info from profile
            $email = $profile['email'] ?? '';
            $firstName = $profile['first_name'] ?? $profile['name'] ?? '';
            $lastName = $profile['last_name'] ?? '';
            $profilePicture = $profile['picture'] ?? null;

            // Check if user exists by provider_id or email
            $user = User::where('auth_provider', 'apple')
                       ->where('auth_provider_id', $providerId)
                       ->first();

            if (!$user) {
                // Check if user exists by email
                $user = User::where('email', $email)->first();

                if ($user) {
                    // User exists but with different auth provider, link the accounts
                    $user->update([
                        'auth_provider' => 'apple',
                        'auth_provider_id' => $providerId,
                        'profile_picture' => $profilePicture,
                    ]);
                } else {
                    // Create new user
                    $user = User::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'password' => Hash::make(Str::random(32)), // Random password for social users
                        'auth_provider' => 'apple',
                        'auth_provider_id' => $providerId,
                        'profile_picture' => $profilePicture,
                        'email_verified_at' => now(), // Social users are pre-verified
                        'is_active' => true,
                    ]);
                }
            }

            // Update last login
            $user->updateLastLogin();

            // Generate token
            $tokenResult = $user->createToken('auth-token');
            $token = $tokenResult->accessToken;

            return AuthResult::success($user, $token);
        } catch (\Exception $e) {
            return AuthResult::failure('Apple authentication failed: ' . $e->getMessage());
        }
    }

    public function verifyToken(string $token): bool
    {
        // TODO: Implement actual Apple token verification
        // For now, return true for testing
        // In production, you would verify the token with Apple's API
        return true;
    }

    public function getUserInfo(string $token): array
    {
        // TODO: Implement actual Apple user info retrieval
        // For now, return empty array for testing
        return [];
    }
}
