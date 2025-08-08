<?php

namespace App\Http\Controllers;

use App\Services\LocationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class AddressController extends Controller
{
    private LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    /**
     * Get address autocomplete suggestions.
     */
    public function getAutocompleteSuggestions(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2|max:100',
            'type' => 'nullable|string|in:address,postcode,place',
            'country' => 'nullable|string|max:50',
        ]);

        $query = $request->input('query');
        $type = $request->input('type', 'address');
        $country = $request->input('country', 'UK');

        // Add country to query if not already present
        if (!str_contains(strtoupper($query), strtoupper($country))) {
            $query = $query . ', ' . $country;
        }

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
            'country' => 'nullable|string|max:50',
        ]);

        $postcode = $request->input('postcode');
        $country = $request->input('country', 'UK');

        $results = $this->locationService->searchByPostcode($postcode);

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }

    /**
     * Validate and format an address.
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
     * Get address components for form filling.
     */
    public function getAddressComponents(Request $request): JsonResponse
    {
        $request->validate([
            'place_id' => 'required|string',
        ]);

        $placeDetails = $this->locationService->getPlaceDetails($request->input('place_id'));

        if (!$placeDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to get address components.',
            ], 400);
        }

        // Format address components for form fields
        $components = [
            'formatted_address' => $placeDetails['formatted_address'],
            'address' => $placeDetails['address'],
            'city' => $placeDetails['city'],
            'state' => $placeDetails['state'],
            'postal_code' => $placeDetails['postal_code'],
            'country' => $placeDetails['country'],
            'latitude' => $placeDetails['latitude'],
            'longitude' => $placeDetails['longitude'],
            'community_name' => $placeDetails['community_name'],
            'borough' => $placeDetails['borough'],
        ];

        return response()->json([
            'success' => true,
            'data' => $components,
        ]);
    }

    /**
     * Get nearby places for a location.
     */
    public function getNearbyPlaces(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'radius' => 'nullable|numeric|min:100|max:50000',
            'type' => 'nullable|string',
        ]);

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');
        $radius = $request->input('radius', 5000); // 5km default
        $type = $request->input('type'); // e.g., 'restaurant', 'gym', 'park'

        $places = $this->locationService->getNearbyPlaces($latitude, $longitude, $type, $radius);

        return response()->json([
            'success' => true,
            'data' => $places,
        ]);
    }
}
