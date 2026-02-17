<?php

use App\Models\User;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can list users', function () {
    $user = User::factory()->create();
    User::factory()->count(5)->create();

    Passport::actingAs($user);

    $response = $this->getJson('/api/users');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'first_name',
                    'last_name',
                ]
            ]
        ]);
});

test('authenticated user can mark themselves online', function () {
    $user = User::factory()->create(['is_online' => false]);

    Passport::actingAs($user);

    $response = $this->postJson('/api/user/online');

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_online' => true,
    ]);
});

test('authenticated user can mark themselves offline', function () {
    $user = User::factory()->create(['is_online' => true]);

    Passport::actingAs($user);

    $response = $this->postJson('/api/user/offline');

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'is_online' => false,
    ]);
});

test('authenticated user can ping to update last seen', function () {
    $user = User::factory()->create();

    Passport::actingAs($user);

    // Freeze time not easily possible without Carbon::setTestNow(), 
    // but we can check if response implies success
    $response = $this->postJson('/api/user/ping');

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    // Check if last_seen_at was updated (is not null)
    $user->refresh();
    expect($user->last_seen_at)->not->toBeNull();
});

test('unauthenticated user cannot access user endpoints', function () {
    $response = $this->getJson('/api/users');
    $response->assertStatus(401);

    $response = $this->postJson('/api/user/online');
    $response->assertStatus(401);
});
