<?php

use App\Models\User;
use App\Models\Discussion;
use App\Models\GameType;
use App\Models\Comment;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can list discussions', function () {
    $user = User::factory()->create();
    Discussion::factory()->count(3)->create();

    Passport::actingAs($user);

    // Default filter might hide discussions if user has no interests or different interests
    // So we pass filter_by_interests=false
    $response = $this->getJson('/api/discussions?filter_by_interests=false');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'body',
                    'author'
                ]
            ]
        ])
        ->assertJsonCount(3, 'data');
});

test('authenticated user can create discussion', function () {
    $user = User::factory()->create();
    $gameType = GameType::factory()->create();

    Passport::actingAs($user);

    $payload = [
        'title' => 'New Discussion Topic',
        'body' => 'This is the body of the discussion.',
        'game_type_id' => $gameType->id
    ];

    $response = $this->postJson('/api/discussions', $payload);

    $response->assertStatus(201)
        ->assertJson(['success' => true])
        ->assertJsonPath('data.title', 'New Discussion Topic');

    $this->assertDatabaseHas('discussions', [
        'title' => 'New Discussion Topic',
        'user_id' => $user->id
    ]);
});

test('authenticated user can view discussion', function () {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->create();

    Passport::actingAs($user);

    $response = $this->getJson("/api/discussions/{$discussion->id}");

    $response->assertStatus(200)
        ->assertJson(['success' => true])
        ->assertJsonPath('data.id', $discussion->id)
        ->assertJsonPath('data.title', $discussion->title);
});

test('authenticated user can update own discussion', function () {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $user->id]);

    Passport::actingAs($user);

    $payload = ['title' => 'Updated Title'];

    $response = $this->putJson("/api/discussions/{$discussion->id}", $payload);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('discussions', [
        'id' => $discussion->id,
        'title' => 'Updated Title'
    ]);
});

test('authenticated user cannot update others discussion', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $otherUser->id]);

    Passport::actingAs($user);

    $response = $this->putJson("/api/discussions/{$discussion->id}", ['title' => 'Hacked']);

    $response->assertStatus(403);
});

test('authenticated user can delete own discussion', function () {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->create(['user_id' => $user->id]);

    Passport::actingAs($user);

    $response = $this->deleteJson("/api/discussions/{$discussion->id}");

    $response->assertStatus(200);

    $this->assertDatabaseMissing('discussions', ['id' => $discussion->id]);
});

test('authenticated user can comment on discussion', function () {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->create();

    Passport::actingAs($user);

    $response = $this->postJson("/api/discussions/{$discussion->id}/comments", [
        'body' => 'This is a comment'
    ]);

    $response->assertStatus(201)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('comments', [
        'discussion_id' => $discussion->id,
        'user_id' => $user->id,
        'body' => 'This is a comment'
    ]);
});

test('authenticated user can like discussion', function () {
    $user = User::factory()->create();
    $discussion = Discussion::factory()->create();

    Passport::actingAs($user);

    $response = $this->postJson("/api/discussions/{$discussion->id}/likes");

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('likes', [
        'likeable_id' => $discussion->id,
        'likeable_type' => Discussion::class,
        'user_id' => $user->id
    ]);

    // Test unlike
    $response = $this->deleteJson("/api/discussions/{$discussion->id}/likes");
    $response->assertStatus(200);

    $this->assertDatabaseMissing('likes', [
        'likeable_id' => $discussion->id,
        'user_id' => $user->id
    ]);
});
