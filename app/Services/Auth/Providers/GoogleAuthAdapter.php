<?php

declare(strict_types=1);

namespace App\Services\Auth\Providers;

use App\Contracts\AuthProviderInterface;
use App\Services\Auth\AuthResult;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleAuthAdapter implements AuthProviderInterface
{
    private string $googleClientId;

    public function __construct()
    {
        $this->googleClientId = config('services.google.client_id') ?: 'mock-client-id-for-testing';
    }

    public function authenticate(array $credentials): AuthResult
    {
        try {
            // Validate credentials
            if (!isset($credentials['access_token']) || !isset($credentials['provider_id'])) {
                return AuthResult::failure('Access token and provider ID are required');
            }

            $idToken = $credentials['access_token']; // This is actually an ID token
            $providerId = $credentials['provider_id'];
            $profile = $credentials['profile'] ?? [];

            // Verify the Google ID token and extract user info
            $userInfo = $this->verifyAndExtractUserInfo($idToken);
            if (!$userInfo) {
                return AuthResult::failure('Invalid Google authentication token');
            }

            // Use extracted user info from ID token
            $email = $userInfo['email'] ?? $profile['email'] ?? '';
            $firstName = $userInfo['given_name'] ?? $profile['first_name'] ?? '';
            $lastName = $userInfo['family_name'] ?? $profile['last_name'] ?? '';
            $profilePicture = $userInfo['picture'] ?? $profile['picture'] ?? null;
            $googleUserId = $userInfo['sub'] ?? $providerId;

            // Check if user exists by Google user ID or email
            $user = User::where('auth_provider', 'google')
                       ->where('auth_provider_id', $googleUserId)
                       ->first();

            if (!$user) {
                // Check if user exists by email
                $user = User::where('email', $email)->first();

                if ($user) {
                    // User exists but with different auth provider, link the accounts
                    $user->update([
                        'auth_provider' => 'google',
                        'auth_provider_id' => $googleUserId,
                        'profile_picture' => $profilePicture,
                    ]);
                } else {
                    // Create new user
                    $user = User::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'password' => Hash::make(Str::random(32)), // Random password for social users
                        'auth_provider' => 'google',
                        'auth_provider_id' => $googleUserId,
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
            Log::error('Google authentication error: ' . $e->getMessage());
            return AuthResult::failure('Google authentication failed: ' . $e->getMessage());
        }
    }

    public function verifyToken(string $token): bool
    {
        // Only accept demo/mock tokens in local development
        if ($this->isLocalOrAllowsDemoTokens()) {
            if (str_starts_with($token, 'demo_google_access_token_')) {
                Log::info('Accepting demo Google token for local testing');
                return true;
            }
            if (str_starts_with($token, 'mock_google_token_')) {
                Log::info('Accepting mock Google token for local testing');
                return true;
            }
        }

        try {
            // For production: Verify with Google's servers
            $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                'access_token' => $token
            ]);

            if ($response->successful()) {
                $tokenInfo = $response->json();

                // Verify audience (client ID)
                if (isset($tokenInfo['aud']) && $tokenInfo['aud'] === $this->googleClientId) {
                    Log::info('Google token verified successfully');
                    return true;
                }

                Log::warning('Google token verification failed: wrong audience', [
                    'expected' => $this->googleClientId,
                    'received' => $tokenInfo['aud'] ?? 'unknown'
                ]);
                return false;
            }

            Log::warning('Failed to verify Google token with Google servers');
            return false;

        } catch (\Exception $e) {
            Log::error('Google token verification error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify Google ID token and extract user information
     */
    private function isLocalOrAllowsDemoTokens(): bool
    {
        return config('app.env') === 'local'
            || config('app.accept_demo_auth_tokens', false);
    }

    public function verifyAndExtractUserInfo(string $idToken): ?array
    {
        // Only accept demo/mock tokens in local development (security: never in production)
        if ($this->isLocalOrAllowsDemoTokens()) {
            if (str_starts_with($idToken, 'demo_google_access_token_')) {
                Log::info('Accepting demo Google token for local testing');
                return [
                    'sub' => 'demo_google_user_123',
                    'email' => 'demo@example.com',
                    'given_name' => 'Demo',
                    'family_name' => 'User',
                    'picture' => null,
                ];
            }
            if (str_starts_with($idToken, 'mock_google_token_')) {
                Log::info('Accepting mock Google ID token for local testing');
                return [
                    'sub' => 'mock_google_user_id',
                    'email' => 'test@example.com',
                    'given_name' => 'Test',
                    'family_name' => 'User',
                    'picture' => null,
                ];
            }
        }

        try {
            // Decode the JWT to get the payload
            $tokenParts = explode('.', $idToken);
            if (count($tokenParts) !== 3) {
                Log::warning('Invalid JWT token format');
                return null;
            }

            $payload = json_decode(base64_decode($tokenParts[1]), true);
            if (!$payload) {
                Log::warning('Invalid JWT payload');
                return null;
            }

            // For Google One Tap ID tokens, we need to verify with Google's tokeninfo endpoint for ID tokens
            // This is different from access tokens
            $response = Http::get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $idToken
            ]);

            if ($response->successful()) {
                $tokenInfo = $response->json();
                
                // Verify audience (client ID)
                if (isset($tokenInfo['aud']) && $tokenInfo['aud'] === $this->googleClientId) {
                    Log::info('Google ID token verified successfully');
                    // Extract user information from the payload
                    return [
                        'sub' => $payload['sub'] ?? null,
                        'email' => $payload['email'] ?? null,
                        'given_name' => $payload['given_name'] ?? null,
                        'family_name' => $payload['family_name'] ?? null,
                        'picture' => $payload['picture'] ?? null,
                    ];
                }

                Log::warning('Google ID token verification failed: wrong audience', [
                    'expected' => $this->googleClientId,
                    'received' => $tokenInfo['aud'] ?? 'unknown'
                ]);
                return null;
            }

            Log::warning('Failed to verify Google ID token with Google servers');
            return null;

        } catch (\Exception $e) {
            Log::error('Google ID token verification error: ' . $e->getMessage());
            return null;
        }
    }

    public function getUserInfo(string $token): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $token
            ])->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Failed to get Google user info', [
                'status' => $response->status(),
                'response' => $response->body()
            ]);
            return [];
        } catch (\Exception $e) {
            Log::error('Google user info error: ' . $e->getMessage());
            return [];
        }
    }
}
