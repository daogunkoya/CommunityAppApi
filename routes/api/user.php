<?php

use App\Http\Controllers\User\UserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// User management routes (protected)
Route::middleware('auth:api')->group(function () {
    // User operations
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/user/online', [UserController::class, 'markOnline']);
    Route::post('/user/offline', [UserController::class, 'markOffline']);
    Route::post('/user/ping', [UserController::class, 'ping']);

    // Profile management
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);
});

