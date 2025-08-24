<?php

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
            $authTypeValue = $request->input('auth_type', 1);
            $authType = AuthType::fromValue($authTypeValue);
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
                $token = $result->getToken();

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
                            'accessTokenId' => $token,
                            'tokenType' => 'Bearer',
                            'expiresIn' => 60 * 24 * 30, // 30 days
                            'accessToken' => $token,
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
            case AuthType::APPLE:
                $rules = [
                    'access_token' => 'required|string',
                    'provider_id' => 'required|string',
                    'profile' => 'array',
                    'profile.email' => 'required|email',
                    'profile.first_name' => 'required|string',
                    'profile.last_name' => 'required|string',
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
