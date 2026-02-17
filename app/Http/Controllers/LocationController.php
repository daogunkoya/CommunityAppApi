<?php

namespace App\Http\Controllers;

use App\Http\Resources\CommunityResource;
use App\Http\Resources\GameEventResource;
use App\Http\Resources\UserResource;
use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{
    private LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Update user's location.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string|max:500',
        ]);

        $user = Auth::user();
        $address = $request->input('address');

        $success = $this->locationService->updateUserLocation($user, $address);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update location. Please check the address and try again.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Location updated successfully.',
            'data' => [
                'user' => new UserResource($user->fresh()),
            ],
        ]);
    }

    /**
     * Validate an address.
     */
    public function validateAddress(Request $request): JsonResponse
    {
        $request->validate([
            'address' => 'required|string|max:500',
        ]);

        $result = $this->locationService->validateAddress($request->input('address'));

        return response()->json([
            'success' => $result['valid'],
            'message' => $result['message'],
            'data' => $result['valid'] ? $result['data'] : null,
        ]);
    }

    /**
     * Get location suggestions for autocomplete.
     */
    public function getLocationSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'type' => 'nullable|string|in:address,postcode,place',
        ]);

        $query = $request->input('query');
        $type = $request->input('type', 'address');

        $suggestions = $this->locationService->getLocationSuggestions($query, $type);

        return response()->json([
            'success' => true,
            'data' => $suggestions,
        ]);
    }

    /**
     * Get place details from place ID.
     */
    public function getPlaceDetails(Request $request): JsonResponse
    {
        $request->validate([
            'place_id' => 'required|string',
        ]);

        $placeDetails = $this->locationService->getPlaceDetails($request->input('place_id'));

        if (!$placeDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to get place details. Please try again.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => $placeDetails,
        ]);
    }

    /**
     * Search by postcode.
     */
    public function searchByPostcode(Request $request): JsonResponse
    {
        $request->validate([
            'postcode' => 'required|string|min:3|max:10',
        ]);

        $results = $this->locationService->searchByPostcode($request->input('postcode'));

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Get nearby users.
     */
    public function getNearbyUsers(Request $request): JsonResponse
    {
        $request->validate([
            'radius' => 'nullable|numeric|min:1|max:50',
        ]);

        $user = Auth::user();
        $radius = $request->input('radius', 10);

        $users = $this->locationService->getNearbyUsers($user, $radius);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
        ]);
    }

    /**
     * Get community users.
     */
    public function getCommunityUsers(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $user = Auth::user();
        $limit = $request->input('limit', 20);

        $users = $this->locationService->getCommunityUsers($user, $limit);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($users),
        ]);
    }

    /**
     * Get nearby game events.
     */
    public function getNearbyGameEvents(Request $request): JsonResponse
    {
        $request->validate([
            'radius' => 'nullable|numeric|min:1|max:50',
        ]);

        $user = Auth::user();
        $radius = $request->input('radius', 10);

        $events = $this->locationService->getNearbyGameEvents($user, $radius);

        return response()->json([
            'success' => true,
            'data' => GameEventResource::collection($events),
        ]);
    }

    /**
     * Get community game events.
     */
    public function getCommunityGameEvents(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:50',
        ]);

        $user = Auth::user();
        $limit = $request->input('limit', 20);

        $events = $this->locationService->getCommunityGameEvents($user, $limit);

        return response()->json([
            'success' => true,
            'data' => GameEventResource::collection($events),
        ]);
    }

    /**
     * Get community statistics.
     */
    public function getCommunityStatistics(Request $request): JsonResponse
    {
        $request->validate([
            'community_name' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
        ]);

        $statistics = $this->locationService->getCommunityStatistics(
            $request->input('community_name'),
            $request->input('city'),
            $request->input('state')
        );

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Search communities.
     */
    public function searchCommunities(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $communities = $this->locationService->searchCommunities(
            $request->input('query'),
            $request->input('limit', 10)
        );

        return response()->json([
            'success' => true,
            'data' => CommunityResource::collection($communities),
        ]);
    }

    /**
     * Get popular communities.
     */
    public function getPopularCommunities(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $communities = $this->locationService->getPopularCommunities(
            $request->input('limit', 10)
        );

        return response()->json([
            'success' => true,
            'data' => CommunityResource::collection($communities),
        ]);
    }

    /**
     * Get community recommendations for user.
     */
    public function getCommunityRecommendations(Request $request): JsonResponse
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:10',
        ]);

        $user = Auth::user();
        $limit = $request->input('limit', 5);

        $communities = $this->locationService->getCommunityRecommendations($user, $limit);

        return response()->json([
            'success' => true,
            'data' => CommunityResource::collection($communities),
        ]);
    }

    /**
     * Get location-based recommendations.
     */
    public function getLocationBasedRecommendations(): JsonResponse
    {
        $user = Auth::user();
        $recommendations = $this->locationService->getLocationBasedRecommendations($user);

        return response()->json([
            'success' => true,
            'data' => [
                'nearby_users' => UserResource::collection($recommendations['nearby_users']),
                'community_users' => UserResource::collection($recommendations['community_users']),
                'nearby_events' => GameEventResource::collection($recommendations['nearby_events']),
                'community_events' => GameEventResource::collection($recommendations['community_events']),
                'popular_communities' => CommunityResource::collection($recommendations['popular_communities']),
                'community_recommendations' => CommunityResource::collection($recommendations['community_recommendations']),
            ],
        ]);
    }
}
