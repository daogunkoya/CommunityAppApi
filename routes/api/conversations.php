<?php

use App\Http\Controllers\ConversationController;
use Illuminate\Support\Facades\Route;

// Messaging routes (protected)
Route::middleware('auth:api')->prefix('conversations')->group(function () {
    Route::get('/', [ConversationController::class, 'index']);
    Route::post('/', [ConversationController::class, 'store']);
    Route::get('/{conversation}', [ConversationController::class, 'show']);
    Route::get('/{conversation}/messages', [ConversationController::class, 'getMessages']);
    Route::post('/{conversation}/messages', [ConversationController::class, 'sendMessage']);
    Route::get('/{conversation}/participants', [ConversationController::class, 'getParticipants']);
    Route::post('/{conversation}/read', [ConversationController::class, 'markAsRead']);

    // Typing indicator routes
    Route::post('/{conversation}/typing/start', [ConversationController::class, 'startTyping']);
    Route::post('/{conversation}/typing/stop', [ConversationController::class, 'stopTyping']);
    Route::get('/{conversation}/typing', [ConversationController::class, 'getTypingUsers']);
});

