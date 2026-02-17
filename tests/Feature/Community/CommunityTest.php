<?php

use App\Models\User;
use App\Models\Community;
use App\Models\GameEvent;
use App\Models\GameType;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can list their communities', function () {
    $user = User::factory()->create();
    $communities = Community::factory()->count(3)->create();

    // Attach communities
    $user->communities()->attach($communities[0]->id, ['is_primary' => true, 'is_active' => true]);
    $user->communities()->attach($communities[1]->id, ['is_primary' => false, 'is_active' => true]);
    // 3rd community not attached

    Passport::actingAs($user);

    $response = $this->getJson('/api/communities/my-communities');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'is_primary',
                ]
            ]
        ])
        ->assertJsonCount(2, 'data'); // Should only return the 2 attached
});

test('authenticated user can list all communities', function () {
    $user = User::factory()->create();
    Community::factory()->count(5)->create(['is_active' => true]);
    Community::factory()->create(['is_active' => false]); // Should be hidden

    Passport::actingAs($user);

    $response = $this->getJson('/api/communities/all');

    $response->assertStatus(200)
        ->assertJsonCount(5, 'data');
});

test('authenticated user can get primary community', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create();

    $user->communities()->attach($community->id, ['is_primary' => true, 'is_active' => true]);

    Passport::actingAs($user);

    $response = $this->getJson('/api/communities/primary');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $community->id)
        ->assertJsonPath('data.name', $community->name);
});

test('primary community returns 404 if none set', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create();

    // Attach but not primary
    $user->communities()->attach($community->id, ['is_primary' => false]);

    Passport::actingAs($user);

    $response = $this->getJson('/api/communities/primary');

    $response->assertStatus(404);
});

test('can get community stats', function () {
    $user = User::factory()->create();
    $community = Community::factory()->create();
    $gameType = GameType::factory()->create();

    // Add some users to community (via pivot or relationship?)
    // Community stats count: users(), gameEvents()

    // Attach user to community
    $user->communities()->attach($community->id);

    // Create events
    GameEvent::factory()->count(2)->create([
        'community_id' => $community->id,
        'community_name' => $community->name,
        'city' => $community->city,
        'state' => $community->state,
        'country' => $community->country,
        'game_type_id' => $gameType->id,
        'organiser_id' => $user->id,
        'starts_at' => now()->addDay() // Upcoming
    ]);

    GameEvent::factory()->create([
        'community_id' => $community->id,
        'community_name' => $community->name,
        'city' => $community->city,
        'state' => $community->state,
        'country' => $community->country,
        'game_type_id' => $gameType->id,
        'organiser_id' => $user->id,
        'starts_at' => now()->subDay() // Past
    ]);

    Passport::actingAs($user);

    $response = $this->getJson("/api/communities/{$community->id}/stats");

    $response->assertStatus(200)
        ->assertJsonPath('data.total_users', 1)
        ->assertJsonPath('data.total_events', 3) // Total events
        ->assertJsonPath('data.upcoming_events', 2); // Upcoming only
});
