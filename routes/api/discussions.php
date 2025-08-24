<?php

use App\Http\Controllers\DiscussionController;
use Illuminate\Support\Facades\Route;

// Discussions routes (protected)
Route::middleware('auth:api')->group(function () {
    Route::get('/discussions', [DiscussionController::class, 'index']);
    Route::post('/discussions', [DiscussionController::class, 'store']);
    Route::get('/discussions/{discussion}', [DiscussionController::class, 'show']);
    Route::put('/discussions/{discussion}', [DiscussionController::class, 'update']);
    Route::delete('/discussions/{discussion}', [DiscussionController::class, 'destroy']);

    // Discussion typing indicator routes
    Route::post('/discussions/{discussion}/typing/start', [DiscussionController::class, 'startTyping']);
    Route::post('/discussions/{discussion}/typing/stop', [DiscussionController::class, 'stopTyping']);
    Route::get('/discussions/{discussion}/typing', [DiscussionController::class, 'getTypingUsers']);

    // Comments
    Route::post('/discussions/{discussion}/comments', [DiscussionController::class, 'storeComment']);
    Route::get('/discussions/{discussion}/comments', [DiscussionController::class, 'showComments']);

    // Likes
    Route::post('/discussions/{discussion}/likes', [DiscussionController::class, 'like']);
    Route::delete('/discussions/{discussion}/likes', [DiscussionController::class, 'unlike']);

    // Trending topics
    Route::get('/discussions/trending/topics', [DiscussionController::class, 'trendingTopics']);
});

