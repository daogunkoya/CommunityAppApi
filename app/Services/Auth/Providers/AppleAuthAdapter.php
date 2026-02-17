<?php

namespace App\Services\Auth\Providers;

use App\Contracts\AuthProviderInterface;
use App\Services\Auth\AuthResult;
use App\Models\User;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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

            // Verify the Apple token and extract payload
            $payload = $this->verifyAndExtractPayload($accessToken);
            if (!$payload) {
                return AuthResult::failure('Invalid Apple authentication token');
            }
            // Ensure client-sent provider_id matches token's sub (prevents impersonation)
            if (($payload->sub ?? '') !== $providerId) {
                return AuthResult::failure('Apple token subject does not match provider ID');
            }

            // Extract user info from profile
            // Note: Apple only provides email/name on FIRST sign-in
            // Subsequent sign-ins may not include this data
            $email = $profile['email'] ?? '';
            $firstName = $profile['first_name'] ?? $profile['name'] ?? '';
            $lastName = $profile['last_name'] ?? '';
            $profilePicture = $profile['picture'] ?? null;

            // Check if user exists by provider_id (primary lookup)
            $user = User::where('auth_provider', 'apple')
                       ->where('auth_provider_id', $providerId)
                       ->first();

            if (!$user) {
                // If email provided, check if user exists by email
                if (!empty($email)) {
                    $user = User::where('email', $email)->first();

                    if ($user) {
                        // User exists but with different auth provider, link the accounts
                        $user->update([
                            'auth_provider' => 'apple',
                            'auth_provider_id' => $providerId,
                            'profile_picture' => $profilePicture,
                        ]);
                    }
                }

                // If still no user, create new one
                if (!$user) {
                    // Generate a placeholder email if not provided
                    $userEmail = !empty($email) ? $email : "apple_{$providerId}@privaterelay.appleid.com";
                    
                    // Use placeholder names if not provided
                    $userFirstName = !empty($firstName) ? $firstName : 'Apple';
                    $userLastName = !empty($lastName) ? $lastName : 'User';

                    // Create new user
                    $user = User::create([
                        'first_name' => $userFirstName,
                        'last_name' => $userLastName,
                        'email' => $userEmail,
                        'password' => Hash::make(Str::random(32)), // Random password for social users
                        'auth_provider' => 'apple',
                        'auth_provider_id' => $providerId,
                        'profile_picture' => $profilePicture,
                        'email_verified_at' => now(), // Social users are pre-verified
                        'is_active' => true,
                    ]);
                }
            } else {
                // User exists - update email/name if provided and missing
                $updateData = [];
                
                if (!empty($email) && (empty($user->email) || str_contains($user->email, '@privaterelay.appleid.com'))) {
                    $updateData['email'] = $email;
                }
                
                if (!empty($firstName) && (empty($user->first_name) || $user->first_name === 'Apple')) {
                    $updateData['first_name'] = $firstName;
                }
                
                if (!empty($lastName) && (empty($user->last_name) || $user->last_name === 'User')) {
                    $updateData['last_name'] = $lastName;
                }
                
                if (!empty($updateData)) {
                    $user->update($updateData);
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

    /**
     * Verify Apple identity token and extract payload.
     * Validates signature, expiration, issuer, and audience.
     *
     * @return object|null The decoded JWT payload or null if invalid
     */
    private function verifyAndExtractPayload(string $token): ?object
    {
        try {
            // Fetch Apple's public keys
            $response = Http::timeout(10)->get('https://appleid.apple.com/auth/keys');
            if (!$response->successful()) {
                Log::warning('Failed to fetch Apple JWKS', ['status' => $response->status()]);
                return false;
            }

            $jwks = $response->json();
            if (empty($jwks['keys'])) {
                Log::warning('Apple JWKS has no keys');
                return false;
            }

            $keys = JWK::parseKeySet($jwks, 'ES256');
            $payload = JWT::decode($token, $keys);

            // Validate issuer
            $expectedIss = 'https://appleid.apple.com';
            if (empty($payload->iss) || $payload->iss !== $expectedIss) {
                Log::warning('Apple token invalid issuer', ['iss' => $payload->iss ?? 'null']);
                return null;
            }

            // Validate audience (bundle ID or Services ID)
            $allowedAudiences = array_filter([
                config('services.apple.client_id'),
                config('services.apple.bundle_id', 'com.matchgrinder.mobile'),
            ]);
            $aud = $payload->aud ?? null;
            if (empty($aud) || !in_array($aud, $allowedAudiences, true)) {
                Log::warning('Apple token invalid audience', [
                    'aud' => $aud,
                    'allowed' => $allowedAudiences,
                ]);
                return null;
            }

            // sub (user identifier) must be present
            if (empty($payload->sub)) {
                Log::warning('Apple token missing sub');
                return null;
            }

            return $payload;
        } catch (\Firebase\JWT\ExpiredException $e) {
            Log::info('Apple token expired');
            return null;
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            Log::warning('Apple token signature invalid: ' . $e->getMessage());
            return null;
        } catch (\Exception $e) {
            Log::error('Apple token verification error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify Apple token (interface compatibility).
     */
    public function verifyToken(string $token): bool
    {
        return $this->verifyAndExtractPayload($token) !== null;
    }

    public function getUserInfo(string $token): array
    {
        // TODO: Implement actual Apple user info retrieval
        // For now, return empty array for testing
        return [];
    }
}
