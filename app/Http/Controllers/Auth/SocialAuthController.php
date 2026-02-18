<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Handle social authentication (legacy endpoint for backward compatibility)
     */
    public function authenticate(Request $request)
    {
        // Map provider to auth type
        $providerMap = [
            'google' => 2,
            'facebook' => 3,
            'apple' => 4,
        ];

        $provider = $request->input('provider');
        $authType = $providerMap[$provider] ?? 2; // Default to Google

        // Convert legacy format to new format
        $request->merge([
            'auth_type' => $authType,
            'credentials' => [
                'access_token' => $request->input('access_token'),
                'provider_id' => $request->input('provider_id'),
                'profile' => [
                    'email' => $request->input('email'),
                    'first_name' => $request->input('first_name'),
                    'last_name' => $request->input('last_name'),
                    'picture' => $request->input('profile_picture'),
                ]
            ]
        ]);

        // Call the unified auth controller
        $unifiedController = app(\App\Http\Controllers\Auth\UnifiedAuthController::class);
        return $unifiedController->authenticate($request);
    }

    /**
     * Verify social authentication token
     */
    private function verifySocialToken($provider, $accessToken, $providerId)
    {
        // This is a placeholder implementation
        // In production, you would verify the token with each provider's API

        switch ($provider) {
            case 'facebook':
                return $this->verifyFacebookToken($accessToken, $providerId);
            case 'google':
                return $this->verifyGoogleToken($accessToken, $providerId);
            case 'apple':
                return $this->verifyAppleToken($accessToken, $providerId);
            default:
                return false;
        }
    }

    /**
     * Verify Facebook token
     */
    private function verifyFacebookToken($accessToken, $providerId)
    {
        // TODO: Implement Facebook token verification
        // For now, return true for testing
        return true;
    }

    /**
     * Verify Google token
     */
    private function verifyGoogleToken($accessToken, $providerId)
    {
        // TODO: Implement Google token verification
        // For now, return true for testing
        return true;
    }

    /**
     * Verify Apple token
     */
    private function verifyAppleToken($accessToken, $providerId)
    {
        // TODO: Implement Apple token verification
        // For now, return true for testing
        return true;
    }
}
