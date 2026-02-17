<?php

use App\Models\User;
use App\Models\Conversation;
use App\Models\Message;
use Laravel\Passport\Passport;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('authenticated user can list conversations', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    // Create a conversation for the user
    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach([$user->id, $otherUser->id]);

    // Create another conversation NOT for the user
    $otherConversation = Conversation::factory()->create();
    $otherConversation->participants()->attach([$otherUser->id]);

    Passport::actingAs($user);

    $response = $this->getJson('/api/conversations');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'type',
                    'unread_count'
                ]
            ]
        ])
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $conversation->id);
});

test('authenticated user can create direct conversation', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Passport::actingAs($user);

    $response = $this->postJson('/api/conversations', [
        'type' => 'direct',
        'participant_ids' => [$otherUser->id]
    ]);

    $response->assertStatus(200); // Controller returns 200 for new format

    $conversationId = $response->json('data.id');

    $this->assertDatabaseHas('conversations', [
        'id' => $conversationId,
        'type' => 'direct'
    ]);

    $this->assertDatabaseHas('conversation_participants', [
        'conversation_id' => $conversationId,
        'user_id' => $user->id
    ]);
});

test('existing direct conversation is returned if tried to create again', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $conversation = Conversation::factory()->create(['type' => 'direct']);
    $conversation->participants()->attach([$user->id, $otherUser->id]);

    Passport::actingAs($user);

    $response = $this->postJson('/api/conversations', [
        'type' => 'direct',
        'participant_ids' => [$otherUser->id]
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $conversation->id);

    // Ensure no new conversation is created
    $this->assertEquals(1, Conversation::count());
});

test('authenticated user can send message', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach($user->id);

    Passport::actingAs($user);

    $response = $this->postJson("/api/conversations/{$conversation->id}/messages", [
        'content' => 'Hello World'
    ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('messages', [
        'conversation_id' => $conversation->id,
        'user_id' => $user->id,
        'content' => 'Hello World'
    ]);

    $conversation->refresh();
    $this->assertNotNull($conversation->last_message_id);
});

test('authenticated user can list messages', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach($user->id);

    Message::factory()->count(3)->create([
        'conversation_id' => $conversation->id,
        'user_id' => $user->id
    ]);

    Passport::actingAs($user);

    $response = $this->getJson("/api/conversations/{$conversation->id}/messages");

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('listing messages marks them as read', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $conversation = Conversation::factory()->create();
    $conversation->participants()->attach([$user->id, $otherUser->id]);

    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'user_id' => $otherUser->id,
        'read_at' => null
    ]);

    Passport::actingAs($user);

    $this->getJson("/api/conversations/{$conversation->id}/messages");

    $this->assertDatabaseHas('messages', [
        'id' => $message->id,
        'read_at' => now() // approximately
    ]);

    // Strict check that it's not null
    $message->refresh();
    $this->assertNotNull($message->read_at);
});

test('non-participant cannot access conversation', function () {
    $user = User::factory()->create();
    $conversation = Conversation::factory()->create();
    // User NOT attached

    Passport::actingAs($user);

    $response = $this->getJson("/api/conversations/{$conversation->id}");

    $response->assertStatus(404); // Using findOrFail/whereHas, so 404 is expected
});
