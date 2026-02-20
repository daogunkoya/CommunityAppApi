<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Discussion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_report_discussion()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $discussion = Discussion::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user, 'api')->postJson('/api/reports', [
            'reportable_id' => $discussion->id,
            'reportable_type' => 'discussion',
            'reason' => 'Spam',
            'details' => 'This is spam content',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reports', [
            'user_id' => $user->id,
            'reportable_id' => $discussion->id,
            'reason' => 'Spam',
        ]);
    }

    public function test_user_can_block_another_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/users/block', [
            'blocked_user_id' => $otherUser->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('blocked_users', [
            'user_id' => $user->id,
            'blocked_user_id' => $otherUser->id,
        ]);
    }

    public function test_user_cannot_block_themselves()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->postJson('/api/users/block', [
            'blocked_user_id' => $user->id,
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_delete_account()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')->deleteJson('/api/profile');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
