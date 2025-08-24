<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle user login (legacy endpoint for backward compatibility)
     */
    public function login(Request $request)
    {
        // Redirect to new unified auth endpoint
        $request->merge([
            'auth_type' => 1, // EMAIL
            'credentials' => [
                'email' => $request->input('email'),
                'password' => $request->input('password'),
            ]
        ]);

        // Call the unified auth controller
        $unifiedController = app(\App\Http\Controllers\Auth\UnifiedAuthController::class);
        return $unifiedController->authenticate($request);
    }

    /**
     * Handle user logout
     */
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request)
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'first_name' => $request->user()->first_name,
                'last_name' => $request->user()->last_name,
                'email' => $request->user()->email,
                'email_verified_at' => $request->user()->email_verified_at,
            ]
        ]);
    }
}

