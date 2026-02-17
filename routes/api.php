
<?php

use App\Http\Middleware\ApiSecurityMiddleware;
use App\Http\Middleware\DisableCsrfForApi;
use Illuminate\Support\Facades\Route;

// All API routes should use the 'api' middleware group to avoid CSRF protection
Route::middleware(['api', DisableCsrfForApi::class])->group(function () {

    // Include route files for better organization
    require __DIR__ . '/api/auth.php';
    require __DIR__ . '/api/sport.php';
    require __DIR__ . '/api/location.php';

    // Apply security middleware to protected API routes
    Route::middleware([ApiSecurityMiddleware::class])->group(function () {

        // Include protected route files
        require __DIR__ . '/api/user.php';
        require __DIR__ . '/api/dashboard.php';
        require __DIR__ . '/api/events.php';
        require __DIR__ . '/api/community.php';
        require __DIR__ . '/api/discussions.php';
        require __DIR__ . '/api/tournaments.php';
        require __DIR__ . '/api/conversations.php';
    });
});

// Test route
Route::get("/test", function () {
    return response()->json(["message" => "Fresh Laravel API is working!"]);
});

// Debug routes (development only)
if (app()->environment('local', 'development')) {
    require __DIR__ . '/api/debug.php';
}
