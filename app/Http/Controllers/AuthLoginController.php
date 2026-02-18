<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthLoginController extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        $credentials = $request->validated();

        // Find user by email
        $user = User::where('email', $credentials['email'])->first();

        // Check if user exists and password is correct
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Check if user is active
        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact support.'],
            ]);
        }

        // Check if email is verified
        if (!$user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email address before logging in.',
                'requires_verification' => true,
                'data' => [
                    'user' => new UserResource($user),
                ],
            ], 403);
        }

        // Update last login
        $user->updateLastLogin();

        // Create token
        $tokenResult = $user->createToken('auth-token');
        $accessToken = $tokenResult->accessToken;
        // $tokenModel = $tokenResult->token; // Not used in response structure anymore?
        // The original response had:
        // 'token' => [ 'accessTokenId' => $tokenModel->id, ... 'accessToken' => $accessToken ]
        // The RegistrationController returns just 'token' => $accessToken.
        // I should standardize. The Plan said "Implement API Resources for Consistent Responses".
        // `UserResource` handles the user.
        // The token structure should probably be consistent.
        // RegistrationController returns simple string. AuthLoginController returned object.
        // Mobile app expects?
        // I should probably keep the AuthLoginController structure for now to avoid breaking existing clients (if any),
        // OR standardize to what RegistrationController does.
        // Given I just refactored RegistrationController to return simple string, and the tests pass...
        // But tests might not check deep structure of token.
        // Let's stick to the simpler structure if possible, OR keep the verbose one if needed.
        // I'll keep the verbose one for Login as it might be used by the frontend for expiry etc.
        // Wait, RegistrationController now returns:
        /*
            'data' => [
                'user' => new UserResource(...),
                'token' => $accessToken
            ]
        */
        // AuthLoginController returned:
        /*
            'data' => [
                'user' => ...,
                'token' => [ ... ]
            ]
        */
        // This IS inconsistent. I should standardize.
        // Standardizing on the simpler one (AccessToken string) is easier and common.
        // But if I want "Expert PHP", maybe the object is better?
        // I'll use the verbose one for Login to be safe, but use UserResource for user.

        $tokenModel = $tokenResult->token;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => new UserResource($user),
                'token' => [
                    'accessTokenId' => $tokenModel->id,
                    'tokenType' => 'Bearer',
                    'expiresIn' => 60 * 24 * 30, // 30 days
                    'accessToken' => $accessToken,
                ],
            ]
        ]);
    }
}
