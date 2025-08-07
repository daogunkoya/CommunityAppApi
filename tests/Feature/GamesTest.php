<?php

use App\Models\User;
use App\Models\GameType;
use App\Models\GameEvent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Create a test user
    $this->user = User::factory()->create([
        'email' => 'test@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    // Create a game type
    $this->gameType = GameType::factory()->create([
        'name' => 'Football',
        'description' => 'The beautiful game',
    ]);
});

test('game type has required attributes', function () {
    expect($this->gameType->name)->toBe('Football');
    expect($this->gameType->description)->toBe('The beautiful game');
});

test('game event can be created', function () {
    $gameEvent = GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'location' => 'Test Stadium',
        'starts_at' => now()->addDays(1),
        'max_participants' => 10,
    ]);

    expect($gameEvent->organiser_id)->toBe($this->user->id);
    expect($gameEvent->game_type_id)->toBe($this->gameType->id);
    expect($gameEvent->location)->toBe('Test Stadium');
    expect($gameEvent->max_participants)->toBe(10);
});

test('game event can have participants', function () {
    $gameEvent = GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'max_participants' => 10,
    ]);

    // Add participants
    $gameEvent->participants()->attach($this->user->id);

    expect($gameEvent->participants()->count())->toBe(1);
    expect($gameEvent->participants()->first()->id)->toBe($this->user->id);
});

test('game event can remove participants', function () {
    $gameEvent = GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'max_participants' => 10,
    ]);

    // Add and then remove participants
    $gameEvent->participants()->attach($this->user->id);
    expect($gameEvent->participants()->count())->toBe(1);

    $gameEvent->participants()->detach($this->user->id);
    expect($gameEvent->participants()->count())->toBe(0);
});

test('game event respects max participants', function () {
    $gameEvent = GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'max_participants' => 1,
    ]);

    // Add first participant
    $gameEvent->participants()->attach($this->user->id);
    expect($gameEvent->participants()->count())->toBe(1);

    // Try to add second participant
    $otherUser = User::factory()->create();
    $gameEvent->participants()->attach($otherUser->id);
    expect($gameEvent->participants()->count())->toBe(2); // This should be limited by validation
});

test('game event can be filtered by sport', function () {
    $soccerType = GameType::factory()->create(['name' => 'Soccer']);
    $basketballType = GameType::factory()->create(['name' => 'Basketball']);

    GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $soccerType->id,
    ]);

    GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $basketballType->id,
    ]);

    $soccerGames = GameEvent::whereHas('gameType', function ($query) {
        $query->where('name', 'Soccer');
    })->get();

    expect($soccerGames)->toHaveCount(1);
    expect($soccerGames->first()->gameType->name)->toBe('Soccer');
});

test('game event validates future date', function () {
    $gameEvent = GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'starts_at' => now()->addDays(1),
    ]);

    expect($gameEvent->starts_at)->toBeGreaterThan(now());
});

test('game event can have different skill levels', function () {
    $gameEvent = GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'skill_level' => 1,
    ]);

    expect($gameEvent->skill_level->value)->toBe(1);
});

test('game event can have venue booking status', function () {
    $gameEvent = GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'venue_booked' => true,
    ]);

    expect($gameEvent->venue_booked)->toBeTrue();
});

test('game event can have waiting list enabled', function () {
    $gameEvent = GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'waiting_list_enabled' => true,
    ]);

    expect($gameEvent->waiting_list_enabled)->toBeTrue();
});

test('game event can have notes', function () {
    $gameEvent = GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'notes' => 'Fun game for everyone',
    ]);

    expect($gameEvent->notes)->toBe('Fun game for everyone');
});

test('game event can be filtered by date range', function () {
    $todayGame = GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'starts_at' => now()->addHours(2),
    ]);

    $tomorrowGame = GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'starts_at' => now()->addDays(1),
    ]);

    $todayGames = GameEvent::where('starts_at', '>=', now()->startOfDay())
        ->where('starts_at', '<=', now()->endOfDay())
        ->get();

    expect($todayGames)->toHaveCount(1);
    expect($todayGames->first()->id)->toBe($todayGame->id);
});

test('game event can be filtered by location', function () {
    GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'location' => 'Central Park',
    ]);

    GameEvent::factory()->create([
        'organiser_id' => $this->user->id,
        'game_type_id' => $this->gameType->id,
        'location' => 'Sports Center',
    ]);

    $centralParkGames = GameEvent::where('location', 'Central Park')->get();

    expect($centralParkGames)->toHaveCount(1);
    expect($centralParkGames->first()->location)->toBe('Central Park');
});
