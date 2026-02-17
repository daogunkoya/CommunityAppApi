<?php

use App\Models\User;
use App\Models\GameType;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can view their profile', function () {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->getJson('/api/profile');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                'id',
                'first_name',
                'last_name',
                'email',
            ]
        ])
        ->assertJsonPath('data.email', $user->email);
});

test('authenticated user can update their profile', function () {
    $user = User::factory()->create();

    Passport::actingAs($user);

    $newData = [
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'bio' => 'New User Bio',
        'phone' => '1234567890',
        'location' => 'New York, USA'
    ];

    $response = $this->postJson('/api/profile', $newData);

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonPath('data.first_name', 'Updated')
        ->assertJsonPath('data.bio', 'New User Bio');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'first_name' => 'Updated',
        'bio' => 'New User Bio',
    ]);
});

test('authenticated user can get their interests', function () {
    $user = User::factory()->create();
    $gameType = GameType::factory()->create();

    // Attach interest
    $user->gameInterests()->attach($gameType->id, ['skill_level' => 2]);

    Passport::actingAs($user);

    $response = $this->getJson('/api/profile/interests');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'game_type_id',
                    'name',
                    'skill_level',
                ]
            ]
        ])
        ->assertJsonFragment([
            'game_type_id' => $gameType->id,
            'skill_level' => 2 // Pivot data check
        ]);
});

test('authenticated user can update their interests', function () {
    $user = User::factory()->create();
    $gameType1 = GameType::factory()->create();
    $gameType2 = GameType::factory()->create();

    Passport::actingAs($user);

    $payload = [
        'interests' => [
            [
                'game_type_id' => $gameType1->id,
                'skill_level' => 1
            ],
            [
                'game_type_id' => $gameType2->id,
                'skill_level' => 3
            ]
        ]
    ];

    $response = $this->postJson('/api/profile/interests', $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    // Check DB
    $this->assertDatabaseHas('game_user_interest', [
        'user_id' => $user->id,
        'game_type_id' => $gameType1->id,
        'skill_level' => 1
    ]);

    $this->assertDatabaseHas('game_user_interest', [
        'user_id' => $user->id,
        'game_type_id' => $gameType2->id,
        'skill_level' => 3
    ]);
});

test('profile update validates input', function () {
    $user = User::factory()->create();
    Passport::actingAs($user);

    $response = $this->postJson('/api/profile', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});
