<?php

use App\Http\Controllers\GameEventController;
use Illuminate\Support\Facades\Route;

// Game events routes (protected)
Route::middleware('auth:api')->group(function () {
    Route::get('/events', [GameEventController::class, 'index']);
    Route::post('/events', [GameEventController::class, 'store']);
    Route::get('/events/stats', [GameEventController::class, 'stats']);
    Route::get('/events/{event}', [GameEventController::class, 'show']);
    Route::put('/events/{event}', [GameEventController::class, 'update']);
    Route::delete('/events/{event}', [GameEventController::class, 'destroy']);
    Route::post('/events/{event}/join', [GameEventController::class, 'join']);
    Route::delete('/events/{event}/leave', [GameEventController::class, 'leave']);
});

