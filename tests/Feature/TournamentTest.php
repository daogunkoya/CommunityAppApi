<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tournament;
use App\Models\GameType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class TournamentTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_tournaments()
    {
        $user = User::factory()->create();

        // Ensure at least one game type exists
        GameType::factory()->create(['name' => 'Tennis']);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/tournaments');

        $response->assertStatus(200);
    }

    public function test_can_create_tournament()
    {
        $user = User::factory()->create();
        $gameType = GameType::factory()->create(['name' => 'Tennis']);

        $data = [
            'name' => 'Test Tournament',
            'description' => 'A test tournament',
            'game_type_id' => $gameType->id,
            'location' => 'Test Location',
            'address' => '123 Test St',
            'starts_at' => now()->addDays(7)->toDateTimeString(),
            'ends_at' => now()->addDays(8)->toDateTimeString(),
            'registration_deadline' => now()->addDays(6)->toDateTimeString(),
            'max_participants' => 16,
            'skill_level' => 2,
            'entry_fee' => 10,
            'prize_pool' => 100,
            'waiting_list_enabled' => true,
        ];

        $response = $this->actingAs($user, 'api')
            ->postJson('/api/tournaments', $data);

        $response->assertStatus(201);
    }

    public function test_can_show_tournament()
    {
        $user = User::factory()->create();
        $gameType = GameType::factory()->create(['name' => 'Tennis']);
        $tournament = Tournament::factory()->create([
            'game_type_id' => $gameType->id,
            'organiser_id' => $user->id
        ]);

        $response = $this->actingAs($user, 'api')
            ->getJson("/api/tournaments/{$tournament->id}");

        $response->assertStatus(200);
    }

    public function test_can_get_available_game_types()
    {
        $user = User::factory()->create();
        GameType::factory()->create(['name' => 'Tennis']);

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/tournaments/available-game-types');

        $response->assertStatus(200);
    }

    public function test_can_get_upcoming_matches()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'api')
            ->getJson('/api/tournaments/upcoming-matches');

        $response->assertStatus(200);
    }
}
