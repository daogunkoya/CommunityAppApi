<?php

use App\Http\Controllers\Sport\SportController;
use Illuminate\Support\Facades\Route;

// Sport routes (public)
Route::get('/game-types', [SportController::class, 'gameTypes']);
Route::get('/games', [SportController::class, 'games']);
Route::get('/sport-stats', [SportController::class, 'stats']);


// User-specific sport statistics (authenticated)
Route::middleware('auth:api')->get('/sport-stats/user-interests', [SportController::class, 'userSportStats']);
