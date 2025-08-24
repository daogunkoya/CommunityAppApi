<?php

use App\Models\User;
use App\Models\GameEvent;
use App\Models\GameType;
use App\Models\Conversation;
use Illuminate\Support\Facades\Route;

// Debug routes (development only)
Route::get("/debug-sarah", function () {
    $sarah = User::find(2);
    $john = User::where('email', 'john@example.com')->first();

    // Test the exact logic from conversations route
    $conversation = Conversation::whereHas('participants', function($q) use ($john) {
        $q->where('user_id', $john->id);
    })->whereHas('participants', function($q) use ($sarah) {
        $q->where('user_id', $sarah->id);
    })->first();

    if ($conversation) {
        $otherParticipants = $conversation->participants->where('user_id', '!=', $john->id);
        $otherUser = $otherParticipants->first()->user;
        $freshUser = User::find($otherUser->id);

        return response()->json([
            'sarah_direct' => [
                'id' => $sarah->id,
                'name' => $sarah->full_name,
                'is_online' => $sarah->is_online,
                'last_seen_at' => $sarah->last_seen_at,
                'last_seen_formatted' => $sarah->last_seen_formatted,
            ],
            'conversation_logic' => [
                'conversation_id' => $conversation->id,
                'other_user_id' => $otherUser->id,
                'other_user_name' => $freshUser->full_name,
                'other_user_online' => $freshUser->is_online,
                'other_user_last_seen' => $freshUser->last_seen_formatted,
            ],
            'database_raw' => [
                'sarah_is_online' => $sarah->getRawOriginal('is_online'),
                'sarah_last_seen_at' => $sarah->getRawOriginal('last_seen_at'),
            ]
        ]);
    }

    return response()->json(['error' => 'No conversation found between John and Sarah']);
});

Route::get("/debug-games", function () {
    $totalEvents = GameEvent::count();
    $eventsWithGameType = GameEvent::whereNotNull('game_type_id')->count();
    $eventsWithoutGameType = GameEvent::whereNull('game_type_id')->count();

    $gameTypes = GameType::all()->map(function($type) {
        return [
            'id' => $type->id,
            'name' => $type->name,
            'name_length' => strlen($type->name),
            'events_count' => $type->events()->count(),
            'future_events_count' => $type->events()->where('starts_at', '>', now())->count()
        ];
    });

    $sampleEvents = GameEvent::with('gameType')
        ->where('starts_at', '>', now())
        ->limit(10)
        ->get()
        ->map(function($event) {
            return [
                'id' => $event->id,
                'game_type_id' => $event->game_type_id,
                'game_type_name' => $event->gameType ? $event->gameType->name : 'NULL',
                'starts_at' => $event->starts_at,
                'location' => $event->location
            ];
        });

    return response()->json([
        'total_events' => $totalEvents,
        'events_with_game_type' => $eventsWithGameType,
        'events_without_game_type' => $eventsWithoutGameType,
        'game_types' => $gameTypes,
        'sample_future_events' => $sampleEvents
    ]);
});

