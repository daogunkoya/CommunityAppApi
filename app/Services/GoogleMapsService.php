<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleMapsService
{
    private string $apiKey;
    private string $baseUrl = 'https://maps.googleapis.com/maps/api';

    public function __construct()
    {
        $this->apiKey = config('services.google.maps_api_key');
    }

    /**
     * Geocode an address to get coordinates and location details.
     */
    public function geocodeAddress(string $address): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/geocode/json", [
                'address' => $address,
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    return $this->parseGeocodeResult($data['results'][0]);
                }
            }

            Log::warning('Google Maps geocoding failed', [
                'address' => $address,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Google Maps geocoding error', [
                'address' => $address,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Reverse geocode coordinates to get address details.
     */
    public function reverseGeocode(float $latitude, float $longitude): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/geocode/json", [
                'latlng' => "{$latitude},{$longitude}",
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && !empty($data['results'])) {
                    return $this->parseGeocodeResult($data['results'][0]);
                }
            }

            Log::warning('Google Maps reverse geocoding failed', [
                'coordinates' => "{$latitude},{$longitude}",
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Google Maps reverse geocoding error', [
                'coordinates' => "{$latitude},{$longitude}",
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Parse geocoding result to extract location details.
     */
    private function parseGeocodeResult(array $result): array
    {
        $addressComponents = $result['address_components'];
        $geometry = $result['geometry'];

        $location = [
            'formatted_address' => $result['formatted_address'],
            'latitude' => $geometry['location']['lat'],
            'longitude' => $geometry['location']['lng'],
            'address' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'country' => null,
            'community_name' => null,
            'borough' => null,
        ];

        foreach ($addressComponents as $component) {
            $types = $component['types'];
            $value = $component['long_name'];

            if (in_array('street_number', $types) || in_array('route', $types)) {
                $location['address'] = $location['address'] ? $location['address'] . ' ' . $value : $value;
            } elseif (in_array('locality', $types) || in_array('postal_town', $types)) {
                $location['city'] = $value;
            } elseif (in_array('administrative_area_level_1', $types)) {
                $location['state'] = $value;
            } elseif (in_array('postal_code', $types)) {
                $location['postal_code'] = $value;
            } elseif (in_array('country', $types)) {
                $location['country'] = $value;
            } elseif (in_array('sublocality_level_1', $types)) {
                $location['borough'] = $value;
            } elseif (in_array('sublocality', $types)) {
                $location['community_name'] = $value;
            } elseif (in_array('administrative_area_level_2', $types)) {
                // Use administrative_area_level_2 as borough if no sublocality
                if (!$location['borough']) {
                    $location['borough'] = $value;
                }
            }
        }

        return $location;
    }

    /**
     * Get place details from a place ID.
     */
    public function getPlaceDetails(string $placeId): ?array
    {
        try {
            $response = Http::get("{$this->baseUrl}/place/details/json", [
                'place_id' => $placeId,
                'fields' => 'formatted_address,geometry,address_components',
                'key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK' && isset($data['result'])) {
                    return $this->parseGeocodeResult($data['result']);
                }
            }

            Log::warning('Google Maps place details failed', [
                'place_id' => $placeId,
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Google Maps place details error', [
                'place_id' => $placeId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

        /**
     * Search for places by text query.
     */
    public function searchPlaces(string $query, string $type = 'address', string $location = null): array
    {
        try {
            // For postcode searches, use geocoding API instead of places API
            if ($type === 'postcode') {
                return $this->searchByPostcode($query);
            }

            $params = [
                'query' => $query,
                'key' => $this->apiKey,
            ];

            // Add type-specific parameters
            if ($type === 'place') {
                $params['types'] = 'establishment';
            }

            if ($location) {
                $params['location'] = $location;
                $params['radius'] = 50000; // 50km radius
            }

            $response = Http::get("{$this->baseUrl}/place/textsearch/json", $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK') {
                    return array_map(function ($place) {
                        return [
                            'place_id' => $place['place_id'],
                            'name' => $place['name'],
                            'formatted_address' => $place['formatted_address'],
                            'latitude' => $place['geometry']['location']['lat'],
                            'longitude' => $place['geometry']['location']['lng'],
                        ];
                    }, $data['results']);
                }
            }

            Log::warning('Google Maps place search failed', [
                'query' => $query,
                'response' => $response->json(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Google Maps place search error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

            /**
     * Search by postcode using geocoding API.
     */
    public function searchByPostcode(string $postcode): array
    {
        try {
            $results = [];

            // First, get the postcode area coordinates
            $geocodeResponse = Http::get("{$this->baseUrl}/geocode/json", [
                'address' => $postcode . ', UK',
                'key' => $this->apiKey,
            ]);

            if ($geocodeResponse->successful()) {
                $geocodeData = $geocodeResponse->json();

                if ($geocodeData['status'] === 'OK' && !empty($geocodeData['results'])) {
                    $postcodeResult = $geocodeData['results'][0];
                    $location = $postcodeResult['geometry']['location'];
                    $latitude = $location['lat'];
                    $longitude = $location['lng'];

                    // Add the main postcode result
                    $locationData = $this->parseGeocodeResult($postcodeResult);
                    $results[] = [
                        'place_id' => $postcodeResult['place_id'],
                        'name' => $locationData['city'] ?? 'Address',
                        'formatted_address' => $locationData['formatted_address'],
                        'latitude' => $locationData['latitude'],
                        'longitude' => $locationData['longitude'],
                        'postcode' => $postcode,
                    ];

                    // Extract street name from the postcode result
                    $streetName = $this->extractStreetName($postcodeResult);

                    if ($streetName) {
                        // Generate house numbers for this street
                        $houseNumbers = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30];

                        foreach ($houseNumbers as $number) {
                            $addressQuery = $number . ' ' . $streetName . ', ' . $postcode . ', UK';

                            $houseResponse = Http::get("{$this->baseUrl}/geocode/json", [
                                'address' => $addressQuery,
                                'key' => $this->apiKey,
                            ]);

                            if ($houseResponse->successful()) {
                                $houseData = $houseResponse->json();
                                if ($houseData['status'] === 'OK' && !empty($houseData['results'])) {
                                    foreach ($houseData['results'] as $result) {
                                        $formattedAddress = $result['formatted_address'];

                                        // Only include addresses that contain the postcode
                                        if (str_contains($formattedAddress, $postcode)) {
                                            $houseLocationData = $this->parseGeocodeResult($result);

                                            // Check for duplicates
                                            $isDuplicate = false;
                                            foreach ($results as $existingResult) {
                                                if ($existingResult['formatted_address'] === $houseLocationData['formatted_address']) {
                                                    $isDuplicate = true;
                                                    break;
                                                }
                                            }

                                            if (!$isDuplicate) {
                                                $results[] = [
                                                    'place_id' => $result['place_id'],
                                                    'name' => $number . ' ' . $streetName,
                                                    'formatted_address' => $houseLocationData['formatted_address'],
                                                    'latitude' => $houseLocationData['latitude'],
                                                    'longitude' => $houseLocationData['longitude'],
                                                    'postcode' => $postcode,
                                                ];

                                                // Limit to 10 results to avoid too many API calls
                                                if (count($results) >= 10) {
                                                    break 2;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // If we still don't have enough results, try some common street names
                    if (count($results) <= 3) {
                        $commonStreets = ['High Street', 'Main Street', 'Church Street', 'Station Road'];

                        foreach ($commonStreets as $street) {
                            $streetQuery = $street . ' ' . $postcode;
                            $streetResponse = Http::get("{$this->baseUrl}/place/textsearch/json", [
                                'query' => $streetQuery,
                                'location' => "{$latitude},{$longitude}",
                                'radius' => 2000,
                                'key' => $this->apiKey,
                            ]);

                            if ($streetResponse->successful()) {
                                $streetData = $streetResponse->json();
                                if ($streetData['status'] === 'OK') {
                                    foreach ($streetData['results'] as $place) {
                                        $placeAddress = $place['formatted_address'];
                                        if (str_contains($placeAddress, $postcode)) {
                                            // Check for duplicates
                                            $isDuplicate = false;
                                            foreach ($results as $existingResult) {
                                                if ($existingResult['formatted_address'] === $placeAddress) {
                                                    $isDuplicate = true;
                                                    break;
                                                }
                                            }

                                            if (!$isDuplicate) {
                                                $results[] = [
                                                    'place_id' => $place['place_id'],
                                                    'name' => $place['name'] ?? 'Address',
                                                    'formatted_address' => $placeAddress,
                                                    'latitude' => $place['geometry']['location']['lat'],
                                                    'longitude' => $place['geometry']['location']['lng'],
                                                    'postcode' => $postcode,
                                                ];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error('Google Maps postcode search error', [
                'postcode' => $postcode,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Extract street name from geocoding result.
     */
    private function extractStreetName(array $result): ?string
    {
        foreach ($result['address_components'] as $component) {
            if (in_array('route', $component['types'])) {
                return $component['long_name'];
            }
        }
        return null;
    }

    /**
     * Calculate distance between two points.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // Earth's radius in kilometers

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Get nearby places within a radius.
     */
    public function getNearbyPlaces(float $latitude, float $longitude, string $type = null, int $radius = 5000): array
    {
        try {
            $params = [
                'location' => "{$latitude},{$longitude}",
                'radius' => $radius,
                'key' => $this->apiKey,
            ];

            if ($type) {
                $params['type'] = $type;
            }

            $response = Http::get("{$this->baseUrl}/place/nearbysearch/json", $params);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['status'] === 'OK') {
                    return array_map(function ($place) {
                        return [
                            'place_id' => $place['place_id'],
                            'name' => $place['name'],
                            'formatted_address' => $place['vicinity'] ?? $place['formatted_address'] ?? '',
                            'latitude' => $place['geometry']['location']['lat'],
                            'longitude' => $place['geometry']['location']['lng'],
                            'types' => $place['types'] ?? [],
                        ];
                    }, $data['results']);
                }
            }

            Log::warning('Google Maps nearby places failed', [
                'coordinates' => "{$latitude},{$longitude}",
                'response' => $response->json(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Google Maps nearby places error', [
                'coordinates' => "{$latitude},{$longitude}",
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
