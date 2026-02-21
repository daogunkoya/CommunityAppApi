<?php

use App\Http\Controllers\User\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NotificationSettingsController;
use App\Http\Controllers\NotificationCenterController;
use Illuminate\Support\Facades\Route;

// User management routes (protected)
Route::middleware('auth:api')->group(function () {
    // User operations
    Route::get('/users', [UserController::class, 'index']);
    Route::post('/user/online', [UserController::class, 'markOnline']);
    Route::post('/user/offline', [UserController::class, 'markOffline']);
    Route::post('/user/ping', [UserController::class, 'ping']);
    Route::get('/users/{id}', [UserController::class, 'show']);

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/password', [ProfileController::class, 'updatePassword']);
    Route::delete('/profile', [ProfileController::class, 'destroy']);

    // Sport Interests Management
    Route::get('/profile/interests', [ProfileController::class, 'getInterests']);
    Route::post('/profile/interests', [ProfileController::class, 'updateInterests']);

    // Device & Notification Settings
    Route::post('/user/device-token', [NotificationSettingsController::class, 'registerDevice']);
    Route::get('/user/notification-preferences', [NotificationSettingsController::class, 'getPreferences']);
    Route::put('/user/notification-preferences', [NotificationSettingsController::class, 'updatePreferences']);

    // In-App Notification Center
    Route::get('/user/notifications', [NotificationCenterController::class, 'index']);
    Route::put('/user/notifications/read-all', [NotificationCenterController::class, 'markAllAsRead']);
    Route::put('/user/notifications/{id}/read', [NotificationCenterController::class, 'markAsRead']);

    // Reporting & Blocking
    Route::post('/reports', [\App\Http\Controllers\ReportController::class, 'store']);
    Route::get('/reports', [\App\Http\Controllers\ReportController::class, 'index']); // ADMIN: Fetch all reports
    Route::post('/reports/{id}/resolve', [\App\Http\Controllers\ReportController::class, 'resolve']); // ADMIN: Resolve report
    Route::post('/users/block', [\App\Http\Controllers\BlockController::class, 'store']);
    Route::delete('/users/block/{id}', [\App\Http\Controllers\BlockController::class, 'destroy']);
    Route::get('/users/blocked', [\App\Http\Controllers\BlockController::class, 'index']);
});
