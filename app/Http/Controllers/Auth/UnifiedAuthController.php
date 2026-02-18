<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Enums\AuthType;
use App\Services\Auth\AuthProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class UnifiedAuthController extends Controller
{
    protected AuthProviderFactory $authFactory;

    public function __construct(AuthProviderFactory $authFactory)
    {
        $this->authFactory = $authFactory;
    }

    /**
     * Handle unified authentication for all providers
     */
    public function authenticate(Request $request)
    {
        try {
            // Log authentication attempt for debugging (especially TestFlight)
            Log::info('Authentication attempt', [
                'auth_type' => $request->input('auth_type'),
                'email' => $request->input('credentials.email') ?? 'N/A',
                'user_agent' => $request->header('User-Agent'),
                'origin' => $request->header('Origin'),
                'ip' => $request->ip(),
            ]);

            // Validate request
            $validator = Validator::make($request->all(), [
                'auth_type' => 'integer|min:1|max:4',
                'credentials' => 'required|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get auth type (default to email if not provided)
            $authTypeValue = (int) $request->input('auth_type', 1);
            $authType = AuthType::fromInt($authTypeValue);
            $credentials = $request->input('credentials');

            // Validate credentials based on auth type
            $credentialValidation = $this->validateCredentials($authType, $credentials);
            if (!$credentialValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'errors' => $credentialValidation['errors']
                ], 422);
            }

            // Create provider and authenticate
            $provider = $this->authFactory->create($authType);
            $result = $provider->authenticate($credentials);

            if ($result->isSuccessful()) {
                $user = $result->getUser();
                $tokenString = $result->getToken();

                // The EmailAuthAdapter already creates a token via createToken()
                // We need to get the token model to access the ID
                // Get the most recent token for this user (the one just created)
                $tokenModel = $user->tokens()->where('name', 'auth-token')->latest()->first();

                // If token model not found (shouldn't happen), use token string as fallback
                if (!$tokenModel) {
                    // This is a fallback - the token was created but we can't find the model
                    // Use a placeholder ID and the actual token string
                    $tokenModelId = 0; // Placeholder - token will still work
                    $accessToken = $tokenString;
                } else {
                    $accessToken = $tokenString; // Use the token from AuthResult
                    $tokenModelId = $tokenModel->id;
                }

                // Log authentication success for debugging (especially TestFlight)
                Log::info('Authentication successful', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'auth_type' => $authTypeValue,
                    'user_agent' => $request->header('User-Agent'),
                    'origin' => $request->header('Origin'),
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Authentication successful',
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
                            'accessTokenId' => $tokenModelId,
                            'tokenType' => 'Bearer',
                            'expiresIn' => 60 * 24 * 30, // 30 days
                            'accessToken' => $accessToken,
                        ]
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result->getError()
                ], 401);
            }

        } catch (\Exception $e) {
            Log::error('Unified authentication error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed'
            ], 500);
        }
    }

    /**
     * Validate credentials based on auth type
     */
    private function validateCredentials(AuthType $authType, array $credentials): array
    {
        $rules = [];

        switch ($authType) {
            case AuthType::EMAIL:
                $rules = [
                    'email' => 'required|email',
                    'password' => 'required|string|min:6',
                ];
                break;

            case AuthType::GOOGLE:
            case AuthType::FACEBOOK:
                $rules = [
                    'access_token' => 'required|string',
                    'provider_id' => 'required|string',
                    'profile' => 'array',
                    'profile.email' => 'required|email',
                    'profile.first_name' => 'required|string',
                    'profile.last_name' => 'required|string',
                ];
                break;

            case AuthType::APPLE:
                // Apple Sign In: email and name are optional (only provided on first sign-in)
                // Users can also choose to hide their email
                $rules = [
                    'access_token' => 'required|string',
                    'provider_id' => 'required|string',
                    'profile' => 'array',
                    'profile.email' => 'nullable|email', // Optional - user can hide email
                    'profile.first_name' => 'nullable|string', // Optional - only on first sign-in
                    'profile.last_name' => 'nullable|string', // Optional - only on first sign-in
                ];
                break;
        }

        $validator = Validator::make($credentials, $rules);

        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()
        ];
    }
}
