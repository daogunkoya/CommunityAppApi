<?php

namespace App\Actions\Auth;

use App\Models\User;
use App\Services\GoogleMapsService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class RegisterUserAction
{
    protected ?GoogleMapsService $googleMapsService;

    public function __construct(?GoogleMapsService $googleMapsService = null)
    {
        $this->googleMapsService = $googleMapsService;
    }

    public function execute(array $data): User
    {
        $googleMapsService = $this->getGoogleMapsService();
        $latitude = null;
        $longitude = null;
        $formattedAddress = $data['location'] ?? null;

        // Geocode the location if Google Maps service is available
        if ($googleMapsService && !empty($data['location'])) {
            try {
                $geocodeResult = $googleMapsService->geocodeAddress($data['location']);

                if ($geocodeResult) {
                    $latitude = $geocodeResult['latitude'] ?? null;
                    $longitude = $geocodeResult['longitude'] ?? null;
                    $formattedAddress = $geocodeResult['formatted_address'] ?? $data['location'];
                }
            } catch (\Exception $e) {
                Log::warning('Geocoding failed during registration: ' . $e->getMessage());
            }
        }

        // Split full name into first and last name
        $nameParts = explode(' ', trim($data['fullName']), 2);
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';

        // Handle password for social vs email authentication
        $password = $data['password'] ?? null;
        if (!$password && ($data['authProvider'] ?? 'email') !== 'email') {
            // Generate a random password for social authentication users
            $password = Str::random(32);
        }

        // Create new user
        $userData = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $data['email'],
            'password' => Hash::make($password),
            'location' => $formattedAddress,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'radius' => $data['radius'] ?? 5,
            'main_goal' => $data['mainGoal'],
            'auth_provider' => $data['authProvider'],
            'auth_provider_id' => $data['authProviderId'] ?? null,
            'email_verified_at' => now(), // Auto-verify for now
        ];

        // Only add optional fields if provided
        if (!empty($data['dateOfBirth'])) {
            $userData['date_of_birth'] = $data['dateOfBirth'];
        }
        if (!empty($data['gender'])) {
            $userData['gender'] = $data['gender'];
        }

        $user = User::create($userData);

        // Store user preferences
        $this->storeSkillLevels($user, $data);

        return $user;
    }

    protected function storeSkillLevels(User $user, array $data): void
    {
        $skillLevelsData = [];
        if (isset($data['selectedSports']) && isset($data['skillLevels'])) {
            foreach ($data['selectedSports'] as $sportId) {
                if (isset($data['skillLevels'][$sportId])) {
                    $skillLevelsData[] = [
                        'user_id' => $user->id,
                        'game_type_id' => $sportId,
                        'skill_level' => $data['skillLevels'][$sportId],
                    ];
                }
            }
        }

        if (!empty($skillLevelsData)) {
            $user->skillLevels()->createMany($skillLevelsData);

            // Also sync game_user_interest
            $skillLevelMap = [
                'beginner' => 1,
                'intermediate' => 2,
                'advanced' => 3,
                'expert' => 3,
            ];
            $syncData = [];
            foreach ($data['selectedSports'] as $sportId) {
                $skillKey = $data['skillLevels'][$sportId] ?? 'beginner';
                $syncData[(int) $sportId] = [
                    'skill_level' => $skillLevelMap[strtolower($skillKey)] ?? 1,
                ];
            }
            $user->gameInterests()->sync($syncData);
        }
    }

    protected function getGoogleMapsService(): ?GoogleMapsService
    {
        if ($this->googleMapsService) {
            return $this->googleMapsService;
        }

        try {
            return app(GoogleMapsService::class);
        } catch (\Exception $e) {
            Log::warning('GoogleMapsService not available: ' . $e->getMessage());
            return null;
        }
    }
}
