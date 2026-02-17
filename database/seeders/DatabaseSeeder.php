<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\GameType;
use App\Models\GameEvent;
use App\Models\Discussion;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\GameEventParticipant;
use App\Models\UserSkillLevel;
use App\Models\UserPreferredFacility;
use App\Models\Tournament;
use App\Models\Community;
use App\Models\TournamentBracket;
use App\Models\TournamentMatch;
use App\Models\ConversationParticipant;
use App\Models\TypingIndicator;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        echo "🌱 Starting comprehensive database seeding...\n";

        // Create Game Types first (needed by other models)
        echo "🏀 Creating game types...\n";
        if (GameType::count() === 0) {
            $sports = [
                ['name' => 'Football', 'description' => 'The world\'s most popular sport, played with 11 players per team on a rectangular field.', 'icon_path' => '/icons/football.svg'],
                ['name' => 'Basketball', 'description' => 'A fast-paced team sport played on a court with two hoops, requiring skill and teamwork.', 'icon_path' => '/icons/basketball.svg'],
                ['name' => 'Tennis', 'description' => 'A racket sport played individually or in doubles, requiring agility and strategy.', 'icon_path' => '/icons/tennis.svg'],
                ['name' => 'Swimming', 'description' => 'A water-based sport that builds endurance and works all major muscle groups.', 'icon_path' => '/icons/swimming.svg'],
                ['name' => 'Cycling', 'description' => 'A cardiovascular sport that can be done on roads, trails, or in velodromes.', 'icon_path' => '/icons/cycling.svg'],
                ['name' => 'Running', 'description' => 'A fundamental sport that improves cardiovascular health and can be done anywhere.', 'icon_path' => '/icons/running.svg'],
                ['name' => 'Volleyball', 'description' => 'A team sport played on a court with a net, requiring coordination and teamwork.', 'icon_path' => '/icons/volleyball.svg'],
                ['name' => 'Baseball', 'description' => 'America\'s pastime, a bat-and-ball game played between two teams of nine players.', 'icon_path' => '/icons/baseball.svg'],
                ['name' => 'Soccer', 'description' => 'A team sport played with feet, emphasizing ball control and strategic play.', 'icon_path' => '/icons/soccer.svg'],
                ['name' => 'Golf', 'description' => 'A precision sport played on a course, requiring focus and technique.', 'icon_path' => '/icons/golf.svg'],
            ];

            $gameTypes = collect();
            foreach ($sports as $sport) {
                $gameTypes->push(GameType::create($sport));
            }
            echo "✅ Created {$gameTypes->count()} game types\n";
        } else {
            $gameTypes = GameType::all();
            echo "✅ Found {$gameTypes->count()} existing game types\n";
        }

        // Create test users (john@example.com and test@example.com)
        echo "👥 Creating test users...\n";
        $john = User::firstOrCreate(
            ['email' => 'john@example.com'],
            [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'password' => Hash::make('password'),
                'location' => 'London, UK',
                'gender' => 'male',
                'date_of_birth' => '1990-05-15',
                'bio' => 'Passionate sports enthusiast and community organizer',
                'phone' => '+44 7911 123456',
                'profile_picture' => 'https://ui-avatars.com/api/?name=John+Doe&background=random',
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => now(),
                'is_online' => false,
            ]
        );

        $test = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'password' => Hash::make('password123'),
                'location' => 'London, UK',
                'gender' => 'male',
                'date_of_birth' => '1990-05-15',
                'bio' => 'Test user for development',
                'phone' => '+44 7911 123456',
                'profile_picture' => 'https://ui-avatars.com/api/?name=Test+User&background=random',
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => now(),
                'is_online' => false,
            ]
        );

        echo "✅ Test users ready: john@example.com and test@example.com\n";

        // Create additional users if needed
        if (User::count() < 22) {
            echo "👥 Creating additional users...\n";
            $additionalUsers = User::factory(22 - User::count())->create();
            echo "✅ Created " . $additionalUsers->count() . " additional users\n";
        }

        $allUsers = User::all();
        echo "✅ Total users: {$allUsers->count()}\n";

        // Create Game Events
        echo "🎮 Creating game events...\n";
        $gameEvents = GameEvent::factory(15)->create([
            'organiser_id' => $allUsers->random()->id,
            'game_type_id' => $gameTypes->random()->id,
        ]);
        echo "✅ Created {$gameEvents->count()} game events\n";

        // Create Discussions
        echo "💬 Creating discussions...\n";
        $discussions = Discussion::factory(25)->create([
            'user_id' => $allUsers->random()->id,
            'game_type_id' => $gameTypes->random()->id,
        ]);
        echo "✅ Created {$discussions->count()} discussions\n";

        // Create Comments
        echo "💭 Creating comments...\n";
        $comments = Comment::factory(50)->create([
            'user_id' => $allUsers->random()->id,
            'discussion_id' => $discussions->random()->id,
        ]);
        echo "✅ Created {$comments->count()} comments\n";

        // Create Likes
        echo "👍 Creating likes...\n";
        $likes = Like::factory(100)->create([
            'user_id' => $allUsers->random()->id,
            'likeable_id' => $discussions->random()->id,
            'likeable_type' => Discussion::class,
        ]);
        echo "✅ Created {$likes->count()} likes\n";

        // Create Conversations
        echo "💬 Creating conversations...\n";
        $conversations = Conversation::factory(10)->create();
        echo "✅ Created {$conversations->count()} conversations\n";

        // Create Conversation Participants
        echo "👥 Creating conversation participants...\n";
        foreach ($conversations as $conversation) {
            $participantCount = rand(2, 5);
            $selectedUsers = $allUsers->random($participantCount);

            foreach ($selectedUsers as $user) {
                ConversationParticipant::factory()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $user->id,
                ]);
            }
        }
        echo "✅ Created conversation participants\n";

        // Create Messages
        echo "💬 Creating messages...\n";
        $messages = Message::factory(80)->create([
            'conversation_id' => $conversations->random()->id,
            'user_id' => $allUsers->random()->id,
        ]);
        echo "✅ Created {$messages->count()} messages\n";

        // Create Game Event Participants
        echo "🎯 Creating game event participants...\n";
        foreach ($gameEvents as $gameEvent) {
            $participantCount = rand(3, 8);
            $selectedUsers = $allUsers->random($participantCount);

            foreach ($selectedUsers as $user) {
                GameEventParticipant::factory()->create([
                    'game_event_id' => $gameEvent->id,
                    'user_id' => $user->id,
                ]);
            }
        }
        echo "✅ Created game event participants\n";

        // Create User Skill Levels
        echo "🏆 Creating user skill levels...\n";
        foreach ($allUsers as $user) {
            $skillCount = rand(1, 3);
            $selectedGameTypes = $gameTypes->random($skillCount);

            foreach ($selectedGameTypes as $gameType) {
                UserSkillLevel::firstOrCreate([
                    'user_id' => $user->id,
                    'game_type_id' => $gameType->id,
                ], [
                    'skill_level' => fake()->randomElement(['beginner', 'intermediate', 'advanced', 'expert']),
                ]);
            }
        }
        echo "✅ Created user skill levels\n";

        // Create User Preferred Facilities
        echo "🏟️ Creating user preferred facilities...\n";
        foreach ($allUsers as $user) {
            $facilityCount = rand(1, 2);

            for ($i = 0; $i < $facilityCount; $i++) {
                $facilityTypes = [
                    'Central Park Sports Complex',
                    'Downtown Recreation Center',
                    'Riverside Athletic Club',
                    'Community Sports Hub',
                    'Elite Training Center',
                    'Neighborhood Gym',
                    'University Sports Center',
                    'Professional Sports Arena',
                    'Outdoor Sports Park',
                    'Indoor Sports Facility',
                ];

                $facility = fake()->randomElement($facilityTypes);
                $city = fake()->city();
                $state = fake()->state();
                $facilityId = 'facility_' . strtolower(str_replace(' ', '_', $facility));

                UserPreferredFacility::firstOrCreate([
                    'user_id' => $user->id,
                    'facility_id' => $facilityId,
                ], [
                    'facility_name' => $facility,
                    'facility_address' => fake()->streetAddress() . ', ' . $city . ', ' . $state,
                    'latitude' => fake()->latitude(),
                    'longitude' => fake()->longitude(),
                    'membership_type' => fake()->randomElement(['member', 'pay-per-use', 'other']),
                ]);
            }
        }
        echo "✅ Created user preferred facilities\n";

        // Create Tournaments
        echo "🏆 Creating tournaments...\n";
        $tournaments = Tournament::factory(8)->create([
            'organiser_id' => $allUsers->random()->id,
            'game_type_id' => $gameTypes->random()->id,
            'approved_by' => $allUsers->random()->id,
        ]);
        echo "✅ Created {$tournaments->count()} tournaments\n";

        // Create Tournament Brackets
        echo "🏆 Creating tournament brackets...\n";
        foreach ($tournaments as $tournament) {
            $bracketCount = rand(1, 3);

            for ($i = 0; $i < $bracketCount; $i++) {
                TournamentBracket::factory()->create([
                    'tournament_id' => $tournament->id,
                ]);
            }
        }
        echo "✅ Created tournament brackets\n";

        // Create Tournament Matches
        echo "🏆 Creating tournament matches...\n";
        foreach ($tournaments as $tournament) {
            $matchCount = rand(4, 12);

            for ($i = 0; $i < $matchCount; $i++) {
                TournamentMatch::factory()->create([
                    'tournament_id' => $tournament->id,
                    'bracket_id' => $tournament->brackets->random()->id,
                    'player1_id' => $allUsers->random()->id,
                    'player2_id' => $allUsers->random()->id,
                ]);
            }
        }
        echo "✅ Created tournament matches\n";

        // Create Communities
        echo "🏘️ Creating communities...\n";
        $communities = Community::factory(6)->create();
        echo "✅ Created {$communities->count()} communities\n";

        // Create Typing Indicators
        echo "⌨️ Creating typing indicators...\n";
        for ($i = 0; $i < 5; $i++) {
            TypingIndicator::firstOrCreate([
                'user_id' => $allUsers->random()->id,
                'context_id' => $conversations->random()->id,
                'context_type' => Conversation::class,
            ], [
                'started_at' => fake()->dateTimeBetween('-5 minutes', 'now'),
                'expires_at' => fake()->dateTimeBetween('now', '+5 minutes'),
            ]);
        }
        echo "✅ Created typing indicators\n";

        echo "\n🎉 Database seeding completed successfully!\n";
        echo "📊 Final counts:\n";
        echo "   - Users: " . User::count() . "\n";
        echo "   - Game Types: " . GameType::count() . "\n";
        echo "   - Game Events: " . GameEvent::count() . "\n";
        echo "   - Discussions: " . Discussion::count() . "\n";
        echo "   - Comments: " . Comment::count() . "\n";
        echo "   - Likes: " . Like::count() . "\n";
        echo "   - Conversations: " . Conversation::count() . "\n";
        echo "   - Messages: " . Message::count() . "\n";
        echo "   - Game Event Participants: " . GameEventParticipant::count() . "\n";
        echo "   - User Skill Levels: " . UserSkillLevel::count() . "\n";
        echo "   - User Preferred Facilities: " . UserPreferredFacility::count() . "\n";
        echo "   - Tournaments: " . Tournament::count() . "\n";
        echo "   - Tournament Brackets: " . TournamentBracket::count() . "\n";
        echo "   - Tournament Matches: " . TournamentMatch::count() . "\n";
        echo "   - Communities: " . Community::count() . "\n";
        echo "   - Typing Indicators: " . TypingIndicator::count() . "\n";

        echo "\n🔑 Test Credentials:\n";
        echo "   - john@example.com / password\n";
        echo "   - test@example.com / password123\n";
        echo "\n✅ Both users are email verified and ready to use!\n";
    }
}
