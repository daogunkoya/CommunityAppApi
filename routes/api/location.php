<?php

use App\Http\Controllers\LocationController;
use App\Http\Controllers\AddressController;
use Illuminate\Support\Facades\Route;

// Address validation and autocomplete (public endpoints)
Route::prefix('address')->group(function () {
    Route::get('/autocomplete', [AddressController::class, 'getAutocompleteSuggestions']);
    Route::get('/place-details', [AddressController::class, 'getPlaceDetails']);
    Route::get('/search-postcode', [AddressController::class, 'searchByPostcode']);
    Route::post('/validate', [AddressController::class, 'validateAddress']);
    Route::get('/components', [AddressController::class, 'getAddressComponents']);
    Route::get('/nearby-places', [AddressController::class, 'getNearbyPlaces']);
});

// Location-based features (protected)
Route::middleware('auth:api')->prefix('location')->group(function () {
    Route::post('/update', [LocationController::class, 'updateLocation']);
    Route::post('/validate', [LocationController::class, 'validateAddress']);
    Route::get('/suggestions', [LocationController::class, 'getLocationSuggestions']);
    Route::get('/place-details', [LocationController::class, 'getPlaceDetails']);
    Route::get('/search-postcode', [LocationController::class, 'searchByPostcode']);
    Route::get('/nearby-users', [LocationController::class, 'getNearbyUsers']);
    Route::get('/community-users', [LocationController::class, 'getCommunityUsers']);
    Route::get('/nearby-events', [LocationController::class, 'getNearbyGameEvents']);
    Route::get('/community-events', [LocationController::class, 'getCommunityGameEvents']);
    Route::get('/community-statistics', [LocationController::class, 'getCommunityStatistics']);
    Route::get('/search-communities', [LocationController::class, 'searchCommunities']);
    Route::get('/popular-communities', [LocationController::class, 'getPopularCommunities']);
    Route::get('/community-recommendations', [LocationController::class, 'getCommunityRecommendations']);
    Route::get('/recommendations', [LocationController::class, 'getLocationBasedRecommendations']);
});

