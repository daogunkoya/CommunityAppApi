<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Dashboard routes (protected)
Route::middleware('auth:api')->group(function () {
    Route::get('/home', HomeController::class);
    
    // MAIN UNIFIED DASHBOARD ENDPOINT - Use this for initial page load
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // Individual Section Endpoints (for granular updates)
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/activity', [DashboardController::class, 'activity']);
    Route::get('/dashboard/recommended-games', [DashboardController::class, 'recommendedGames']);
    Route::get('/dashboard/tournaments', [DashboardController::class, 'relevantTournaments']);
    Route::get('/dashboard/upcoming-games', [DashboardController::class, 'upcomingGames']);
    Route::get('/dashboard/interests', [DashboardController::class, 'userInterests']);
});

