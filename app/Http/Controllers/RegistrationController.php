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
     * Get available sports/game types
     */
    public function getSports()
    {
        $sports = GameType::orderBy('name')->get(['id', 'name']);

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
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullName' => 'required|string|max:255',
            'dateOfBirth' => 'required|date|before:today|after:1900-01-01',
            'gender' => 'required|in:male,female,prefer-not-to-say',
            'location' => 'required|string',
            'radius' => 'integer|min:1|max:50',
            'selectedSports' => 'required|array|min:1',
            'selectedSports.*' => 'exists:game_types,id',
            'skillLevels' => 'required|array',
            'skillLevels.*' => 'in:beginner,intermediate,advanced,expert',
            'mainGoal' => 'required|string',
            'email' => 'required|email',
            'password' => 'required_if:authProvider,email|string|min:8',
            'authProvider' => 'required|in:email,facebook,google,apple',
            'authProviderId' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $googleMapsService = $this->getGoogleMapsService();
            $latitude = null;
            $longitude = null;
            $formattedAddress = $request->input('location');

            // Geocode the location if Google Maps service is available
            if ($googleMapsService) {
                $geocodeResult = $googleMapsService->geocodeAddress($request->input('location'));

                if ($geocodeResult) {
                    $latitude = $geocodeResult['latitude'] ?? null;
                    $longitude = $geocodeResult['longitude'] ?? null;
                    $formattedAddress = $geocodeResult['formatted_address'] ?? $request->input('location');
                }
            }

            // Split full name into first and last name
            $nameParts = explode(' ', trim($request->input('fullName')), 2);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[1] ?? '';

            // Check if user already exists
            $user = User::where('email', $request->input('email'))->first();

            if ($user) {
                // User exists, update their profile with the new data
                Log::info('Updating existing user profile', ['user_id' => $user->id, 'email' => $user->email]);

                $user->update([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'date_of_birth' => $request->input('dateOfBirth'),
                    'gender' => $request->input('gender'),
                    'location' => $formattedAddress,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'radius' => $request->input('radius', 5),
                    'main_goal' => $request->input('mainGoal'),
                    'auth_provider' => $request->input('authProvider'),
                    'auth_provider_id' => $request->input('authProviderId'),
                    'email_verified_at' => now(), // Auto-verify for now
                ]);

                // Clear existing skill levels and create new ones
                $user->skillLevels()->delete();
            } else {
                // Handle password for social vs email authentication
                $password = $request->input('password');
                if (!$password && $request->input('authProvider') !== 'email') {
                    // Generate a random password for social authentication users
                    $password = Str::random(32);
                }

                // Create new user
                $user = User::create([
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $request->input('email'),
                    'password' => Hash::make($password),
                    'date_of_birth' => $request->input('dateOfBirth'),
                    'gender' => $request->input('gender'),
                    'location' => $formattedAddress,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'radius' => $request->input('radius', 5),
                    'main_goal' => $request->input('mainGoal'),
                    'auth_provider' => $request->input('authProvider'),
                    'auth_provider_id' => $request->input('authProviderId'),
                    'email_verified_at' => now(), // Auto-verify for now
                ]);
            }

            // Store user preferences - fix the mapping
            $skillLevelsData = [];
            foreach ($request->input('selectedSports') as $sportId) {
                if (isset($request->input('skillLevels')[$sportId])) {
                    $skillLevelsData[] = [
                        'user_id' => $user->id,
                        'game_type_id' => $sportId,
                        'skill_level' => $request->input('skillLevels')[$sportId],
                    ];
                }
            }

            if (!empty($skillLevelsData)) {
                $user->skillLevels()->createMany($skillLevelsData);
            }



            // Generate token
            try {
                $tokenResult = $user->createToken('auth-token');
                $accessToken = $tokenResult->accessToken;
                $tokenModel = $tokenResult->token;
                Log::info('Token created successfully', ['user_id' => $user->id, 'token' => $accessToken]);
            } catch (\Exception $e) {
                Log::error('Token creation failed', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                $accessToken = null;
                $tokenModel = null;
            }

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => [
                    'user' => $user->load(['skillLevels.gameType']),
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
