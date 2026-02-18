<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\UserResource;

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
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get current authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();
        return response()->json([
            'success' => true,
            'data' => [
                'user' => new UserResource($user),
            ]
        ]);
    }
}

