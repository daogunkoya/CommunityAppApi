<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GoogleAuthController extends Controller
{
    public function callback(Request $request)
    {
        try {
            $code = $request->input('code');
            $redirectUri = $request->input('redirect_uri');

            if (!$code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authorization code is required'
                ], 400);
            }

            // Exchange authorization code for access token
            $tokenResponse = Http::post('https://oauth2.googleapis.com/token', [
                'client_id' => config('services.google.client_id'),
                'client_secret' => config('services.google.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirectUri,
            ]);

            if (!$tokenResponse->successful()) {
                Log::error('Google token exchange failed', [
                    'response' => $tokenResponse->body(),
                    'status' => $tokenResponse->status()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to exchange authorization code for token'
                ], 400);
            }

            $tokenData = $tokenResponse->json();
            $accessToken = $tokenData['access_token'];

            // Get user info from Google
            $userInfoResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken
            ])->get('https://www.googleapis.com/oauth2/v2/userinfo');

            if (!$userInfoResponse->successful()) {
                Log::error('Failed to get Google user info', [
                    'response' => $userInfoResponse->body(),
                    'status' => $userInfoResponse->status()
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to get user information from Google'
                ], 400);
            }

            $userInfo = $userInfoResponse->json();

            // Check if user exists by Google ID or email
            $user = User::where('auth_provider', 'google')
                       ->where('auth_provider_id', $userInfo['id'])
                       ->first();

            if (!$user) {
                // Check if user exists by email
                $user = User::where('email', $userInfo['email'])->first();

                if ($user) {
                    // User exists but with different auth provider, link the accounts
                    $user->update([
                        'auth_provider' => 'google',
                        'auth_provider_id' => $userInfo['id'],
                        'profile_picture' => $userInfo['picture'] ?? null,
                    ]);
                } else {
                    // Create new user
                    $user = User::create([
                        'first_name' => $userInfo['given_name'] ?? '',
                        'last_name' => $userInfo['family_name'] ?? '',
                        'email' => $userInfo['email'],
                        'password' => Hash::make(Str::random(32)), // Random password for social users
                        'auth_provider' => 'google',
                        'auth_provider_id' => $userInfo['id'],
                        'profile_picture' => $userInfo['picture'] ?? null,
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

            return response()->json([
                'success' => true,
                'message' => 'Google authentication successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'email' => $user->email,
                        'auth_provider' => $user->auth_provider,
                        'auth_provider_id' => $user->auth_provider_id,
                        'profile_picture' => $user->profile_picture,
                        'email_verified_at' => $user->email_verified_at,
                        'is_active' => $user->is_active,
                        'date_of_birth' => $user->date_of_birth,
                        'gender' => $user->gender,
                        'location' => $user->location,
                        'radius' => $user->radius,
                        'main_goal' => $user->main_goal,
                    ],
                    'token' => [
                        'accessTokenId' => $token,
                        'tokenType' => 'Bearer',
                        'expiresIn' => 60 * 24 * 30, // 30 days
                        'accessToken' => $token,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Google OAuth callback error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Google authentication failed'
            ], 500);
        }
    }
}
