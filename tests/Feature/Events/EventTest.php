<?php

use App\Models\User;
use App\Models\GameEvent;
use App\Models\GameType;
use App\Models\Conversation;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can list events', function () {
    $user = User::factory()->create();
    $gameType = GameType::factory()->create(['name' => 'Football']);

    // Create future event
    GameEvent::factory()->create([
        'game_type_id' => $gameType->id,
        'starts_at' => now()->addDay()
    ]);

    // Create past event (should not show by default)
    GameEvent::factory()->create([
        'game_type_id' => $gameType->id,
        'starts_at' => now()->subDay()
    ]);

    Passport::actingAs($user);

    $response = $this->getJson('/api/events');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'sport',
                    'location',
                    'starts_at'
                ]
            ]
        ])
        ->assertJsonCount(1, 'data'); // Only future event
});

test('authenticated user can create event and auto-creates resources', function () {
    $user = User::factory()->create();
    $gameType = GameType::factory()->create();

    Passport::actingAs($user);

    $payload = [
        'game_type_id' => $gameType->id,
        'location' => 'Wembley Stadium',
        'city' => 'London',
        'starts_at' => now()->addWeek()->toDateTimeString(),
        'skill_level' => 2,
        'max_participants' => 10,
        // Provided community info to trigger creation
        'community_name' => 'Wembley Ballers',
        'borough' => 'Brent'
    ];

    $response = $this->postJson('/api/events', $payload);

    $response->assertStatus(201)
        ->assertJson(['success' => true]);

    $eventId = $response->json('data.id');

    // Check Event created
    $this->assertDatabaseHas('game_events', [
        'id' => $eventId,
        'location' => 'Wembley Stadium',
        'organiser_id' => $user->id
    ]);

    // Check Community created
    $this->assertDatabaseHas('communities', [
        'name' => 'Wembley Ballers',
        'city' => 'London'
    ]);

    // Check Community Chat created
    $this->assertDatabaseHas('conversations', [
        'name' => 'Wembley Ballers - Community Chat',
        'type' => 'community'
    ]);

    // Check User joined event
    $this->assertDatabaseHas('game_event_participants', [
        'game_event_id' => $eventId,
        'user_id' => $user->id,
        'is_waiting' => false
    ]);
});

test('authenticated user can join event', function () {
    $organiser = User::factory()->create();
    $user = User::factory()->create();
    $event = GameEvent::factory()->create([
        'organiser_id' => $organiser->id,
        'max_participants' => 10,
        'starts_at' => now()->addDay()
    ]);

    Passport::actingAs($user);

    $response = $this->postJson("/api/events/{$event->id}/join");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    // Check participation
    $this->assertDatabaseHas('game_event_participants', [
        'game_event_id' => $event->id,
        'user_id' => $user->id,
        'is_waiting' => false
    ]);

    // Check Direct Conversation created between user and organiser
    $this->assertDatabaseHas('conversations', [
        'type' => 'direct',
        'context_id' => $event->id
    ]);
});

test('user is added to waiting list if event is full', function () {
    $organiser = User::factory()->create();
    $event = GameEvent::factory()->create([
        'organiser_id' => $organiser->id,
        'max_participants' => 1,
        'waiting_list_enabled' => true,
        'starts_at' => now()->addDay()
    ]);

    // Fill the event
    $participant = User::factory()->create();
    $event->participants()->attach($participant->id);

    // New user tries to join
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->postJson("/api/events/{$event->id}/join");

    $response->assertStatus(200)
        ->assertJson(['success' => true, 'message' => 'Added to waiting list']);

    $this->assertDatabaseHas('game_event_participants', [
        'game_event_id' => $event->id,
        'user_id' => $user->id,
        'is_waiting' => true
    ]);
});

test('authenticated user can leave event', function () {
    $user = User::factory()->create();
    $event = GameEvent::factory()->create();
    $event->participants()->attach($user->id);

    Passport::actingAs($user);

    $response = $this->deleteJson("/api/events/{$event->id}/leave");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing('game_event_participants', [
        'game_event_id' => $event->id,
        'user_id' => $user->id
    ]);
});

test('organiser can update event', function () {
    $user = User::factory()->create();
    $event = GameEvent::factory()->create(['organiser_id' => $user->id]);

    Passport::actingAs($user);

    $response = $this->putJson("/api/events/{$event->id}", [
        'location' => 'New Location'
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('game_events', [
        'id' => $event->id,
        'location' => 'New Location'
    ]);
});

test('non-organiser cannot update event', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $event = GameEvent::factory()->create(['organiser_id' => $otherUser->id]);

    Passport::actingAs($user);

    $response = $this->putJson("/api/events/{$event->id}", [
        'location' => 'New Location'
    ]);

    $response->assertStatus(403);
});

test('user can comment on event', function () {
    $user = User::factory()->create();
    $event = GameEvent::factory()->create();

    Passport::actingAs($user);

    $response = $this->postJson("/api/events/{$event->id}/comments", [
        'body' => 'Game on!'
    ]);

    $response->assertStatus(201);
    // Let's check controller later.

    $this->assertDatabaseHas('game_event_comments', [
        'game_event_id' => $event->id,
        'user_id' => $user->id,
        'body' => 'Game on!'
    ]);
});
