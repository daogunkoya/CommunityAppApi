<?php

namespace App\Services;

use App\Models\User;
use App\Models\Community;
use App\Models\GameEvent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocationService
{
    private GoogleMapsService $googleMapsService;

    public function __construct(GoogleMapsService $googleMapsService)
    {
        $this->googleMapsService = $googleMapsService;
    }

    /**
     * Update user location with geocoding.
     */
    public function updateUserLocation(User $user, string $address): bool
    {
        try {
            $locationData = $this->googleMapsService->geocodeAddress($address);

            if (!$locationData) {
                Log::warning('Failed to geocode address for user', [
                    'user_id' => $user->id,
                    'address' => $address,
                ]);
                return false;
            }

            $user->update([
                'address' => $locationData['address'],
                'city' => $locationData['city'],
                'state' => $locationData['state'],
                'postal_code' => $locationData['postal_code'],
                'country' => $locationData['country'],
                'latitude' => $locationData['latitude'],
                'longitude' => $locationData['longitude'],
                'community_name' => $locationData['community_name'],
                'borough' => $locationData['borough'],
                'location_verified' => true,
            ]);

            // Auto-assign user to community
            $this->assignUserToCommunity($user);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update user location', [
                'user_id' => $user->id,
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Update game event location with geocoding.
     */
    public function updateGameEventLocation(GameEvent $gameEvent, string $address): bool
    {
        try {
            $locationData = $this->googleMapsService->geocodeAddress($address);

            if (!$locationData) {
                Log::warning('Failed to geocode address for game event', [
                    'game_event_id' => $gameEvent->id,
                    'address' => $address,
                ]);
                return false;
            }

            $gameEvent->update([
                'address' => $locationData['address'],
                'city' => $locationData['city'],
                'state' => $locationData['state'],
                'postal_code' => $locationData['postal_code'],
                'country' => $locationData['country'],
                'latitude' => $locationData['latitude'],
                'longitude' => $locationData['longitude'],
                'community_name' => $locationData['community_name'],
                'borough' => $locationData['borough'],
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update game event location', [
                'game_event_id' => $gameEvent->id,
                'address' => $address,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Assign user to community based on location.
     */
    public function assignUserToCommunity(User $user): bool
    {
        if (!$user->community_name || !$user->city || !$user->state) {
            return false;
        }

        try {
            $community = Community::firstOrCreate([
                'name' => $user->community_name,
                'city' => $user->city,
                'state' => $user->state,
                'country' => $user->country,
            ], [
                'type' => 'borough',
                'latitude' => $user->latitude,
                'longitude' => $user->longitude,
                'description' => "Community in {$user->city}, {$user->state}",
            ]);

            // Check if user is already in this community
            $existingMembership = $user->communities()
                ->where('community_id', $community->id)
                ->first();

            if (!$existingMembership) {
                // Set as primary community if user has no primary community
                $hasPrimary = $user->communities()
                    ->wherePivot('is_primary', true)
                    ->exists();

                $user->communities()->attach($community->id, [
                    'is_primary' => !$hasPrimary,
                    'is_active' => true,
                    'joined_at' => now(),
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to assign user to community', [
                'user_id' => $user->id,
                'community_name' => $user->community_name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Get users in the same community.
     */
    public function getCommunityUsers(User $user, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->byCommunity($user->community_name, $user->city, $user->state)
            ->limit($limit)
            ->get();
    }

    /**
     * Get nearby users within a radius.
     */
    public function getNearbyUsers(User $user, float $radiusKm = 10): \Illuminate\Database\Eloquent\Collection
    {
        if (!$user->latitude || !$user->longitude) {
            return collect();
        }

        return User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->withinRadius($user->latitude, $user->longitude, $radiusKm)
            ->get();
    }

    /**
     * Get game events in user's community.
     */
    public function getCommunityGameEvents(User $user, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return GameEvent::where('starts_at', '>=', now())
            ->byCommunity($user->community_name, $user->city, $user->state)
            ->with(['gameType', 'organiser'])
            ->orderBy('starts_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get nearby game events within a radius.
     */
    public function getNearbyGameEvents(User $user, float $radiusKm = 10): \Illuminate\Database\Eloquent\Collection
    {
        if (!$user->latitude || !$user->longitude) {
            return collect();
        }

        return GameEvent::where('starts_at', '>=', now())
            ->withinRadius($user->latitude, $user->longitude, $radiusKm)
            ->with(['gameType', 'organiser'])
            ->orderBy('starts_at')
            ->get();
    }

    /**
     * Get community statistics.
     */
    public function getCommunityStatistics(string $communityName, string $city, string $state): array
    {
        $community = Community::where('name', $communityName)
            ->where('city', $city)
            ->where('state', $state)
            ->first();

        if (!$community) {
            return [
                'total_users' => 0,
                'primary_users' => 0,
                'game_events' => 0,
                'recent_events' => 0,
            ];
        }

        return $community->getStatistics();
    }

    /**
     * Search for communities by name.
     */
    public function searchCommunities(string $query, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Community::active()
            ->where('name', 'like', "%{$query}%")
            ->orWhere('city', 'like', "%{$query}%")
            ->orWhere('state', 'like', "%{$query}%")
            ->limit($limit)
            ->get();
    }

    /**
     * Get popular communities.
     */
    public function getPopularCommunities(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Community::active()
            ->withCount('activeUsers')
            ->orderBy('active_users_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get user's community recommendations.
     */
    public function getCommunityRecommendations(User $user, int $limit = 5): \Illuminate\Database\Eloquent\Collection
    {
        if (!$user->latitude || !$user->longitude) {
            return collect();
        }

        return Community::active()
            ->where('id', '!=', $user->primaryCommunity()?->id)
            ->withinRadius($user->latitude, $user->longitude, 50) // 50km radius
            ->withCount('activeUsers')
            ->orderBy('active_users_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get location-based recommendations for a user.
     */
    public function getLocationBasedRecommendations(User $user): array
    {
        $recommendations = [
            'nearby_users' => $this->getNearbyUsers($user, 5)->take(5),
            'community_users' => $this->getCommunityUsers($user, 5),
            'nearby_events' => $this->getNearbyGameEvents($user, 5)->take(5),
            'community_events' => $this->getCommunityGameEvents($user, 5),
            'popular_communities' => $this->getPopularCommunities(3),
            'community_recommendations' => $this->getCommunityRecommendations($user, 3),
        ];

        return $recommendations;
    }

    /**
     * Validate and format address.
     */
    public function validateAddress(string $address): array
    {
        $locationData = $this->googleMapsService->geocodeAddress($address);

        if (!$locationData) {
            return [
                'valid' => false,
                'message' => 'Unable to validate this address. Please check and try again.',
            ];
        }

        return [
            'valid' => true,
            'data' => $locationData,
            'message' => 'Address validated successfully.',
        ];
    }

    /**
     * Get location suggestions based on partial address.
     */
    public function getLocationSuggestions(string $query, string $type = 'address'): array
    {
        return $this->googleMapsService->searchPlaces($query, $type);
    }

    /**
     * Get place details from place ID.
     */
    public function getPlaceDetails(string $placeId): ?array
    {
        return $this->googleMapsService->getPlaceDetails($placeId);
    }

        /**
     * Search by postcode.
     */
    public function searchByPostcode(string $postcode): array
    {
        // Clean postcode (remove spaces, uppercase)
        $cleanPostcode = strtoupper(str_replace(' ', '', $postcode));

        // Use the new searchByPostcode method from GoogleMapsService
        $results = $this->googleMapsService->searchByPostcode($postcode);

        return $results;
    }

    /**
     * Get nearby places for a location.
     */
    public function getNearbyPlaces(float $latitude, float $longitude, ?string $type = null, int $radius = 5000): array
    {
        return $this->googleMapsService->getNearbyPlaces($latitude, $longitude, $type, $radius);
    }
}
