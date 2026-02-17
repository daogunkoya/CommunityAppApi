<?php

use App\Http\Controllers\CommunityController;
use Illuminate\Support\Facades\Route;

// Community management routes (protected)
Route::middleware('auth:api')->prefix('communities')->group(function () {
    Route::get('/my-communities', [CommunityController::class, 'getUserCommunities']);
    Route::get('/all', [CommunityController::class, 'getAllCommunities']);
    Route::get('/primary', [CommunityController::class, 'getPrimaryCommunity']);
    Route::get('/{communityId}/stats', [CommunityController::class, 'getCommunityStats']);
});

