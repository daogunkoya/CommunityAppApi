<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Events;

use App\Actions\Events\CreateGameEventAction;
use App\Models\City;
use App\Models\Community;
use App\Models\Conversation;
use App\Models\GameEvent;
use App\Models\GameType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateGameEventActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_game_event_and_joins_organiser(): void
    {
        $user = User::factory()->create();
        $gameType = GameType::factory()->create(['name' => 'Tennis']);

        $data = [
            'game_type_id' => $gameType->id,
            'location' => 'Hyde Park',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'skill_level' => 1,
            'max_participants' => 4,
            'waiting_list_enabled' => true,
            'venue_booked' => false,
            // Community data
            'community_name' => 'Westminster',
            'city' => 'London',
            'borough' => 'Westminster',
        ];

        $action = new CreateGameEventAction();
        $event = $action->execute($data, $user);

        // Assert Event Created
        $this->assertInstanceOf(GameEvent::class, $event);
        $this->assertDatabaseHas('game_events', [
            'id' => $event->id,
            'location' => 'Hyde Park',
            'organiser_id' => $user->id,
        ]);

        // Assert Community Created
        $this->assertDatabaseHas('communities', [
            'name' => 'Westminster',
        ]);

        // Assert Event Linked to Community
        $this->assertNotNull($event->community_id);

        // Assert Participant (Organiser joined)
        $this->assertTrue($event->participants()->where('user_id', $user->id)->exists());

        // Assert Community Conversation Created
        $this->assertDatabaseHas('conversations', [
            'type' => 'community',
            'context_id' => $event->community_id,
        ]);

        // Assert User in Conversation
        $conversation = Conversation::where('type', 'community')
            ->where('context_id', $event->community_id)
            ->first();

        $this->assertTrue($conversation->participants()->where('users.id', $user->id)->exists());
    }

    public function test_it_reuses_existing_community(): void
    {
        $user = User::factory()->create();
        $gameType = GameType::factory()->create();

        $community = Community::factory()->create([
            'name' => 'Existing Community',
            'city' => 'London',
            'state' => 'England',
            'country' => 'UK',
        ]);

        $data = [
            'game_type_id' => $gameType->id,
            'location' => 'Some Park',
            'starts_at' => now()->addDay()->toDateTimeString(),
            'skill_level' => 2,
            'community_name' => 'Existing Community',
            'city' => 'London',
        ];

        $action = new CreateGameEventAction();
        $event = $action->execute($data, $user);

        $this->assertEquals($community->id, $event->community_id);
        $this->assertEquals(1, Community::where('name', 'Existing Community')->count());
    }
}
