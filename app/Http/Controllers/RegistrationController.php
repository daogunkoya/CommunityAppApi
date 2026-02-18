<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\GameType;
use App\Services\GoogleMapsService;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Http\Requests\Auth\RegisterUserRequest;
use App\Actions\Auth\RegisterUserAction;
use App\Http\Resources\UserResource;

class RegistrationController extends Controller
{
    protected $googleMapsService;
    protected $locationService;

    public function __construct()
    {
        // Only inject services when needed
    }

    protected function getGoogleMapsService()
    {
        if (!$this->googleMapsService) {
            try {
                $this->googleMapsService = app(GoogleMapsService::class);
            } catch (\Exception $e) {
                Log::warning('GoogleMapsService not available: ' . $e->getMessage());
                return null;
            }
        }
        return $this->googleMapsService;
    }

    protected function getLocationService()
    {
        if (!$this->locationService) {
            try {
                $this->locationService = app(LocationService::class);
            } catch (\Exception $e) {
                Log::warning('LocationService not available: ' . $e->getMessage());
                return null;
            }
        }
        return $this->locationService;
    }

    /**
     * Get available sports/game types (same as /game-types for consistency with Profile > My interests).
     */
    public function getSports()
    {
        $sports = GameType::orderBy('name')->get(['id', 'name', 'description', 'icon_path', 'color']);

        return response()->json([
            'success' => true,
            'data' => $sports
        ]);
    }

    /**
     * Search for sports facilities near a location
     */
    public function searchFacilities(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'location' => 'required|string',
            'radius' => 'integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $location = $request->input('location');
            $radius = $request->input('radius', 5); // Default 5 miles

            $googleMapsService = $this->getGoogleMapsService();

            if (!$googleMapsService) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location services are currently unavailable. Please try again later.',
                    'data' => [],
                    'location' => [
                        'formatted_address' => $location
                    ]
                ], 503);
            }

            // First, geocode the location to get coordinates
            $geocodeResult = $googleMapsService->geocodeAddress($location);

            if (!$geocodeResult || !isset($geocodeResult['latitude']) || !isset($geocodeResult['longitude'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Could not find location coordinates'
                ], 400);
            }

            $latitude = $geocodeResult['latitude'];
            $longitude = $geocodeResult['longitude'];

            // Search for sports facilities
            $facilities = $googleMapsService->getNearbyPlaces(
                $latitude,
                $longitude,
                'gym|sports_complex|stadium',
                $radius * 1609.34 // Convert miles to meters
            );

            // Filter and format results
            $formattedFacilities = collect($facilities)->map(function ($facility) {
                return [
                    'id' => $facility['place_id'],
                    'name' => $facility['name'],
                    'address' => $facility['vicinity'] ?? $facility['formatted_address'] ?? '',
                    'rating' => $facility['rating'] ?? null,
                    'types' => $facility['types'] ?? [],
                    'photos' => $facility['photos'] ?? [],
                ];
            })->filter(function ($facility) {
                // Filter out non-sports related places
                $sportsKeywords = ['gym', 'fitness', 'sport', 'tennis', 'football', 'basketball', 'swimming', 'athletic', 'recreation'];
                $name = strtolower($facility['name']);
                $types = array_map('strtolower', $facility['types']);

                foreach ($sportsKeywords as $keyword) {
                    if (str_contains($name, $keyword) || in_array($keyword, $types)) {
                        return true;
                    }
                }
                return false;
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formattedFacilities,
                'location' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'formatted_address' => $geocodeResult['formatted_address'] ?? $location
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching facilities: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error searching for facilities'
            ], 500);
        }
    }

    /**
     * Complete registration
     */
    public function register(RegisterUserRequest $request, RegisterUserAction $action)
    {
        try {
            $user = $action->execute($request->validated());

            // Generate token for immediate login
            $tokenResult = $user->createToken('auth-token');
            $accessToken = $tokenResult->accessToken;

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => new UserResource($user->load(['skillLevels.gameType'])),
                    'token' => $accessToken
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Registration failed'
            ], 500);
        }
    }

    /**
     * Social login/registration
     */
    public function socialAuth(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:facebook,google,apple',
            'token' => 'required|string',
            'userData' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $provider = $request->input('provider');
            $token = $request->input('token');
            $userData = $request->input('userData');

            // Verify token with provider (this would need to be implemented based on each provider's API)
            // For now, we'll assume the token is valid

            // Check if user exists
            $user = User::where('auth_provider', $provider)
                ->where('auth_provider_id', $userData['id'])
                ->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $userData['name'] ?? $userData['full_name'] ?? 'User',
                    'email' => $userData['email'],
                    'auth_provider' => $provider,
                    'auth_provider_id' => $userData['id'],
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(32)), // Random password for social users
                ]);
            }

            // Generate token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => $user->wasRecentlyCreated ? 'Registration successful' : 'Login successful',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                    'isNewUser' => $user->wasRecentlyCreated
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Social auth error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Authentication failed'
            ], 500);
        }
    }
}
