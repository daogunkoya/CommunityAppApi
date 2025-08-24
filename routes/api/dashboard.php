<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

// Dashboard routes (protected)
Route::middleware('auth:api')->group(function () {
    Route::get('/home', HomeController::class);
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
});

