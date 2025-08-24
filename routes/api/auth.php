<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\UnifiedAuthController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\AuthLoginController;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Login endpoint
Route::post('/login', AuthLoginController::class);

// Unified authentication endpoint
Route::post('/auth', [UnifiedAuthController::class, 'authenticate']);

// Google OAuth callback for mobile apps
Route::post('/auth/google/callback', [GoogleAuthController::class, 'callback']);

// Test endpoint to directly test GoogleAuthAdapter
Route::post('/auth/test-google', function () {
    $adapter = new \App\Services\Auth\Providers\GoogleAuthAdapter();
    $result = $adapter->verifyToken('demo_google_access_token_123');

    return response()->json([
        'token' => 'demo_google_access_token_123',
        'verify_result' => $result,
        'timestamp' => now()->toISOString()
    ]);
});

// Simple test endpoint for mobile app
Route::post('/auth/mobile-test', function () {
    try {
        // Create or get a test user
        $user = User::where('email', 'mobile@test.com')->first();

        if (!$user) {
            $user = User::create([
                'first_name' => 'Mobile',
                'last_name' => 'Test',
                'email' => 'mobile@test.com',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => true,
                'auth_provider' => 'google',
                'auth_provider_id' => 'mobile_test_user',
            ]);
        }

        // Generate token
        $tokenResult = $user->createToken('auth-token');
        $token = $tokenResult->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'Mobile authentication successful',
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
        return response()->json([
            'success' => false,
            'message' => 'Mobile authentication failed: ' . $e->getMessage()
        ], 500);
    }
});

// Debug endpoint
Route::post('/auth/debug', function () {
    return response()->json([
        'message' => 'Debug endpoint working',
        'timestamp' => now()->toISOString()
    ]);
});
