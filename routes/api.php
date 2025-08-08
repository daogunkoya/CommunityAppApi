<?php

use App\Http\Controllers\AuthLoginController;
use App\Http\Controllers\AuthRegisterController;
use App\Http\Controllers\EmailVerificationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\GameEventController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\CommunityController;
use App\Http\Middleware\ApiSecurityMiddleware;
use App\Http\Middleware\DisableCsrfForApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

// All API routes should use the 'api' middleware group to avoid CSRF protection
Route::middleware(['api', DisableCsrfForApi::class])->group(function () {

    // Public routes (no authentication required)
    Route::post('/login', AuthLoginController::class);
    Route::post('/register', AuthRegisterController::class);

    // Address validation and autocomplete (public endpoints)
    Route::prefix('address')->group(function () {
        Route::get('/autocomplete', [AddressController::class, 'getAutocompleteSuggestions']);
        Route::get('/place-details', [AddressController::class, 'getPlaceDetails']);
        Route::get('/search-postcode', [AddressController::class, 'searchByPostcode']);
        Route::post('/validate', [AddressController::class, 'validateAddress']);
        Route::get('/components', [AddressController::class, 'getAddressComponents']);
        Route::get('/nearby-places', [AddressController::class, 'getNearbyPlaces']);
    });

    // Apply security middleware to protected API routes
    Route::middleware([ApiSecurityMiddleware::class])->group(function () {

        // Game types (public endpoint for creating games)
        Route::get('/game-types', function () {
            return App\Models\GameType::all();
        });

        // Sport statistics (public endpoint for sport categories)
        Route::get('/sport-stats', function () {
            $sportStats = App\Models\GameType::withCount(['events' => function ($query) {
                    $query->where('starts_at', '>', now()->startOfMinute());
                }])
                ->get()
                ->filter(function ($sport) {
                    return $sport->events_count > 0;
                })
                ->map(function ($sport) {
                    return [
                        'name' => $sport->name,
                        'count' => $sport->events_count,
                        'color' => match (strtolower($sport->name)) {
                            'tennis' => 'bg-sport-green',
                            'football' => 'bg-sport-orange',
                            'basketball' => 'bg-sport-blue',
                            'cycling' => 'bg-sport-red',
                            'swimming' => 'bg-primary',
                            default => 'bg-accent',
                        },
                    ];
                })
                ->sortByDesc('count')
                ->values();



            return response()->json([
                'success' => true,
                'data' => $sportStats
            ]);
        });

        // Email verification routes
        Route::post('/email/verify', [EmailVerificationController::class, 'verify']);
        Route::post('/email/resend', [EmailVerificationController::class, 'resend']);

        // Protected routes
        Route::middleware('auth:api')->group(function () {
            // User management
            Route::get('/user', function (Request $request) {
                return response()->json([
                    'user' => [
                        'id' => $request->user()->id,
                        'first_name' => $request->user()->first_name,
                        'last_name' => $request->user()->last_name,
                        'email' => $request->user()->email,
                        'email_verified_at' => $request->user()->email_verified_at,
                    ]
                ]);
            });

            // Profile management
            Route::get('/profile', [ProfileController::class, 'show']);
            Route::post('/profile', [ProfileController::class, 'update']);



            Route::post('/logout', function (Request $request) {
                $request->user()->tokens()->delete();
                return response()->json(['message' => 'Logged out successfully']);
            });

            // Home and dashboard
            Route::get('/home', HomeController::class);

            // Game events
            Route::get('/events', [GameEventController::class, 'index']);
            Route::post('/events', [GameEventController::class, 'store']);
            Route::get('/events/stats', [GameEventController::class, 'stats']);
            Route::get('/events/{event}', [GameEventController::class, 'show']);
            Route::put('/events/{event}', [GameEventController::class, 'update']);
            Route::delete('/events/{event}', [GameEventController::class, 'destroy']);
            Route::post('/events/{event}/join', [GameEventController::class, 'join']);

            // Location-based features
            Route::prefix('location')->group(function () {
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

            // Community management
            Route::prefix('communities')->group(function () {
                Route::get('/my-communities', [CommunityController::class, 'getUserCommunities']);
                Route::get('/all', [CommunityController::class, 'getAllCommunities']);
                Route::get('/primary', [CommunityController::class, 'getPrimaryCommunity']);
                Route::get('/{communityId}/stats', [CommunityController::class, 'getCommunityStats']);
            });
            Route::delete('/events/{event}/leave', [GameEventController::class, 'leave']);

            // Discussions
            Route::get('/discussions', [DiscussionController::class, 'index']);
            Route::post('/discussions', [DiscussionController::class, 'store']);
            Route::get('/discussions/{discussion}', [DiscussionController::class, 'show']);
            Route::put('/discussions/{discussion}', [DiscussionController::class, 'update']);
            Route::delete('/discussions/{discussion}', [DiscussionController::class, 'destroy']);

            // Comments
            Route::post('/discussions/{discussion}/comments', [DiscussionController::class, 'storeComment']);
            Route::get('/discussions/{discussion}/comments', [DiscussionController::class, 'showComments']);

            // Likes
            Route::post('/discussions/{discussion}/likes', [DiscussionController::class, 'like']);
            Route::delete('/discussions/{discussion}/likes', [DiscussionController::class, 'unlike']);

            // Trending topics
            Route::get('/discussions/trending/topics', [DiscussionController::class, 'trendingTopics']);
        });
    });
});
