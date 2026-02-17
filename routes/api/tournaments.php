<?php

use App\Http\Controllers\TournamentController;
use Illuminate\Support\Facades\Route;

// Tournaments routes (protected)
Route::middleware('auth:api')->group(function () {
    Route::get('/tournaments', [TournamentController::class, 'index']);
    Route::post('/tournaments', [TournamentController::class, 'store']);
    Route::get('/tournaments/available-game-types', [TournamentController::class, 'availableGameTypes']);
    Route::get('/tournaments/upcoming-matches', [TournamentController::class, 'upcomingMatches']);
    Route::get('/tournaments/{tournament}', [TournamentController::class, 'show']);
    Route::put('/tournaments/{tournament}', [TournamentController::class, 'update']);
    Route::post('/tournaments/{tournament}/register', [TournamentController::class, 'register']);
    Route::delete('/tournaments/{tournament}/unregister', [TournamentController::class, 'unregister']);
    Route::post('/tournaments/{tournament}/approve', [TournamentController::class, 'approve']);
    Route::post('/tournaments/{tournament}/reject', [TournamentController::class, 'reject']);
});

