<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use App\Models\User;
use App\Models\GameType;
use App\Models\GameEvent;
use App\Models\GameEventParticipant;
use App\Models\Discussion;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Tournament;
use App\Models\TournamentBracket;
use App\Models\TournamentMatch;
use App\Models\Community;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\TypingIndicator;
use App\Models\UserSkillLevel;
use App\Models\UserPreferredFacility;
use App\Models\Game;
use Carbon\Carbon;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Starting comprehensive database seeding...');

        $faker = \Faker\Factory::create();

        // Clear all tables
        $this->truncateAllTables();

        // Seed in order of dependencies
        $this->seedGameTypes($faker);
        $this->seedUsers($faker);
        $this->seedCommunities($faker);
        $this->seedGameEvents($faker);
        $this->seedGameEventParticipants($faker);
        $this->seedDiscussions($faker);
        $this->seedComments($faker);
        $this->seedLikes($faker);
        $this->seedTournaments($faker);
        $this->seedTournamentBrackets($faker);
        $this->seedTournamentMatches($faker);
        $this->seedConversations($faker);
        $this->seedConversationParticipants($faker);
        $this->seedMessages($faker);
        $this->seedTypingIndicators($faker);
        $this->seedUserSkillLevels($faker);
        $this->seedUserPreferredFacilities($faker);
        $this->seedGames($faker);

        $this->command->info('✅ Database seeding completed successfully!');
    }

    private function truncateAllTables(): void
    {
        $this->command->info('🧹 Truncating all tables...');

        $tables = [
            'typing_indicators',
            'messages',
            'conversation_participants',
            'conversations',
            'tournament_matches',
            'tournament_brackets',
            'tournaments',
            'likes',
            'comments',
            'discussions',
            'game_event_participants',
            'game_events',
            'user_preferred_facilities',
            'user_skill_levels',
            'games',
            'users',
            'communities',
            'game_types',
        ];

        // Handle different database types
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function seedGameTypes(): void
    {
        $this->command->info('🏀 Seeding game types...');

        $sports = [
            ['name' => 'Basketball', 'description' => 'Team sport played with a ball and hoop', 'icon_path' => 'basketball.png', 'color' => '#ff6b35'],
            ['name' => 'Football', 'description' => 'Soccer - the beautiful game', 'icon_path' => 'football.png', 'color' => '#4ecdc4'],
            ['name' => 'Tennis', 'description' => 'Racket sport for singles or doubles', 'icon_path' => 'tennis.png', 'color' => '#45b7d1'],
            ['name' => 'Swimming', 'description' => 'Water-based fitness and competition', 'icon_path' => 'swimming.png', 'color' => '#96ceb4'],
            ['name' => 'Cycling', 'description' => 'Road and mountain biking', 'icon_path' => 'cycling.png', 'color' => '#feca57'],
            ['name' => 'Running', 'description' => 'Track, road, and trail running', 'icon_path' => 'running.png', 'color' => '#ff9ff3'],
            ['name' => 'Volleyball', 'description' => 'Team sport with net and ball', 'icon_path' => 'volleyball.png', 'color' => '#54a0ff'],
            ['name' => 'Badminton', 'description' => 'Racket sport with shuttlecock', 'icon_path' => 'badminton.png', 'color' => '#5f27cd'],
            ['name' => 'Table Tennis', 'description' => 'Indoor table tennis', 'icon_path' => 'table-tennis.png', 'color' => '#00d2d3'],
            ['name' => 'Cricket', 'description' => 'Bat and ball team sport', 'icon_path' => 'cricket.png', 'color' => '#ff9f43'],
            ['name' => 'Hockey', 'description' => 'Field hockey with sticks', 'icon_path' => 'hockey.png', 'color' => '#10ac84'],
            ['name' => 'Rugby', 'description' => 'Contact team sport', 'icon_path' => 'rugby.png', 'color' => '#ee5a24'],
            ['name' => 'Golf', 'description' => 'Precision club and ball sport', 'icon_path' => 'golf.png', 'color' => '#2ed573'],
            ['name' => 'Boxing', 'description' => 'Combat sport with gloves', 'icon_path' => 'boxing.png', 'color' => '#ff3838'],
            ['name' => 'Martial Arts', 'description' => 'Various fighting disciplines', 'icon_path' => 'martial-arts.png', 'color' => '#3742fa'],
        ];

        foreach ($sports as $sport) {
            GameType::create($sport);
        }
    }

    private function seedUsers(): void
    {
        $this->command->info('👥 Seeding users...');

        // Create test user
        User::create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
            'location' => 'London, UK',
            'gender' => 'male',
            'date_of_birth' => '1990-05-15',
            'bio' => 'Passionate sports enthusiast and community organizer',
            'phone' => '+44 7911 123456',
            'profile_picture' => 'https://ui-avatars.com/api/?name=Test+User&background=random',
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now(),
            'is_online' => false,
        ]);

        // Create additional users
        $users = [
            ['first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'location' => 'London, UK'],
            ['first_name' => 'Sarah', 'last_name' => 'Johnson', 'email' => 'sarah@example.com', 'location' => 'Manchester, UK'],
            ['first_name' => 'Mike', 'last_name' => 'Rodriguez', 'email' => 'mike@example.com', 'location' => 'Birmingham, UK'],
            ['first_name' => 'Emily', 'last_name' => 'Zhang', 'email' => 'emily@example.com', 'location' => 'Liverpool, UK'],
            ['first_name' => 'David', 'last_name' => 'Kim', 'email' => 'david@example.com', 'location' => 'Leeds, UK'],
            ['first_name' => 'Lisa', 'last_name' => 'Brown', 'email' => 'lisa@example.com', 'location' => 'Sheffield, UK'],
            ['first_name' => 'Carlos', 'last_name' => 'Martinez', 'email' => 'carlos@example.com', 'location' => 'Bristol, UK'],
            ['first_name' => 'Anna', 'last_name' => 'Wilson', 'email' => 'anna@example.com', 'location' => 'Newcastle, UK'],
            ['first_name' => 'James', 'last_name' => 'Taylor', 'email' => 'james@example.com', 'location' => 'Cardiff, UK'],
            ['first_name' => 'Maria', 'last_name' => 'Garcia', 'email' => 'maria@example.com', 'location' => 'Edinburgh, UK'],
            ['first_name' => 'Tom', 'last_name' => 'Anderson', 'email' => 'tom@example.com', 'location' => 'Glasgow, UK'],
            ['first_name' => 'Sophie', 'last_name' => 'Clark', 'email' => 'sophie@example.com', 'location' => 'Nottingham, UK'],
            ['first_name' => 'Alex', 'last_name' => 'White', 'email' => 'alex@example.com', 'location' => 'Oxford, UK'],
            ['first_name' => 'Emma', 'last_name' => 'Davis', 'email' => 'emma@example.com', 'location' => 'Cambridge, UK'],
            ['first_name' => 'Ryan', 'last_name' => 'Miller', 'email' => 'ryan@example.com', 'location' => 'York, UK'],
            ['first_name' => 'Chloe', 'last_name' => 'Thompson', 'email' => 'chloe@example.com', 'location' => 'Brighton, UK'],
            ['first_name' => 'Daniel', 'last_name' => 'Harris', 'email' => 'daniel@example.com', 'location' => 'Bath, UK'],
            ['first_name' => 'Grace', 'last_name' => 'Lewis', 'email' => 'grace@example.com', 'location' => 'Chester, UK'],
            ['first_name' => 'Oliver', 'last_name' => 'Walker', 'email' => 'oliver@example.com', 'location' => 'Durham, UK'],
            ['first_name' => 'Isabella', 'last_name' => 'Hall', 'email' => 'isabella@example.com', 'location' => 'Canterbury, UK'],
            ['first_name' => 'Lucas', 'last_name' => 'Young', 'email' => 'lucas@example.com', 'location' => 'Worcester, UK'],
        ];

        foreach ($users as $userData) {
            User::create([
                'first_name' => $userData['first_name'],
                'last_name' => $userData['last_name'],
                'email' => $userData['email'],
                'password' => Hash::make('password123'),
                'location' => $userData['location'],
                'gender' => $faker->randomElement(['male', 'female', 'other']),
                'date_of_birth' => $faker->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                'bio' => $faker->paragraph(),
                'phone' => $faker->phoneNumber(),
                'profile_picture' => "https://ui-avatars.com/api/?name={$userData['first_name']}+{$userData['last_name']}&background=random",
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => $faker->dateTimeBetween('-30 days', 'now'),
                'is_online' => $faker->boolean(20),
            ]);
        }
    }

    private function seedCommunities(): void
    {
        $this->command->info('🏘️ Seeding communities...');

        $communities = [
            [
                'name' => 'London Sports Enthusiasts',
                'description' => 'A vibrant community for sports lovers in London',
                'location' => 'London, UK',
                'sport_type' => 'Mixed',
                'member_count' => 150,
                'is_active' => true,
            ],
            [
                'name' => 'Manchester Football Club',
                'description' => 'Local football community for all skill levels',
                'location' => 'Manchester, UK',
                'sport_type' => 'Football',
                'member_count' => 89,
                'is_active' => true,
            ],
            [
                'name' => 'Birmingham Tennis Network',
                'description' => 'Tennis players of all levels welcome',
                'location' => 'Birmingham, UK',
                'sport_type' => 'Tennis',
                'member_count' => 67,
                'is_active' => true,
            ],
            [
                'name' => 'Liverpool Running Club',
                'description' => 'Join us for regular running sessions',
                'location' => 'Liverpool, UK',
                'sport_type' => 'Running',
                'member_count' => 234,
                'is_active' => true,
            ],
            [
                'name' => 'Leeds Basketball League',
                'description' => 'Competitive basketball for serious players',
                'location' => 'Leeds, UK',
                'sport_type' => 'Basketball',
                'member_count' => 45,
                'is_active' => true,
            ],
        ];

        foreach ($communities as $community) {
            Community::create($community);
        }
    }

    private function seedGameEvents(): void
    {
        $this->command->info('🎮 Seeding game events...');

        $users = User::all();
        $gameTypes = GameType::all();
        $communities = Community::all();
        $venues = [
            'Community Center', 'Sports Complex', 'Local Park', 'University Gym', 'Recreation Center',
            'Tennis Club', 'Swimming Pool', 'Football Field', 'Basketball Court', 'Athletics Track',
            'Golf Course', 'Boxing Gym', 'Martial Arts Dojo', 'Cricket Ground', 'Rugby Club'
        ];

        for ($i = 0; $i < 50; $i++) {
            $startDate = $faker->dateTimeBetween('now', '+60 days');
            $gameType = $gameTypes->random();
            $organiser = $users->random();
            $community = $faker->boolean(70) ? $communities->random() : null;

            GameEvent::create([
                'game_type_id' => $gameType->id,
                'organiser_id' => $organiser->id,
                'community_id' => $community ? $community->id : null,
                'title' => $faker->sentence(3, false),
                'location' => $faker->randomElement($venues),
                'starts_at' => $startDate,
                'skill_level' => rand(1, 3),
                'max_participants' => $faker->optional(0.8)->numberBetween(4, 25),
                'waiting_list_enabled' => $faker->boolean(70),
                'notes' => $faker->optional(0.7)->paragraph(),
                'venue_booked' => $faker->boolean(80),
            ]);
        }
    }

    private function seedGameEventParticipants(): void
    {
        $this->command->info('👥 Seeding game event participants...');

        $users = User::all();
        $gameEvents = GameEvent::all();

        foreach ($gameEvents as $event) {
            $participantCount = rand(2, min($event->max_participants ?? 10, 8));
            $participants = $users->random($participantCount);

            foreach ($participants as $participant) {
                if ($participant->id !== $event->organiser_id) {
                    GameEventParticipant::create([
                        'game_event_id' => $event->id,
                        'user_id' => $participant->id,
                        'is_waiting' => $faker->boolean(20),
                        'joined_at' => $faker->dateTimeBetween('-7 days', 'now'),
                    ]);
                }
            }
        }
    }

    private function seedDiscussions(): void
    {
        $this->command->info('💬 Seeding discussions...');

        $users = User::all();
        $gameTypes = GameType::all();

        $topics = [
            'Best protein powder recommendations?',
            'Recovery tips after intense workouts',
            'Morning vs evening workout routines',
            'How to improve basketball shooting accuracy',
            'Swimming technique tips for beginners',
            'Best running shoes for long distances',
            'Tennis serve improvement techniques',
            'Cycling safety tips for beginners',
            'Football training drills for midfielders',
            'Martial arts for self-defense',
            'Golf swing improvement advice',
            'Volleyball team coordination strategies',
            'Badminton footwork drills',
            'Table tennis serve techniques',
            'Cricket batting tips for beginners',
            'Hockey stick handling skills',
            'Rugby tackling techniques',
            'Boxing combination drills',
            'Golf putting tips',
            'Martial arts belt progression',
            'Swimming breathing techniques',
            'Running injury prevention',
            'Basketball defense strategies',
            'Tennis doubles positioning',
            'Cycling hill climbing tips',
            'Football goalkeeper training',
            'Tennis backhand improvement',
            'Swimming freestyle technique',
            'Running interval training',
            'Basketball dribbling skills',
            'Golf driving range tips',
            'Martial arts sparring techniques',
            'Volleyball serving strategies',
            'Badminton smash technique',
            'Table tennis spin techniques',
            'Cricket bowling tips',
            'Hockey penalty corner strategies',
            'Rugby scrum techniques',
            'Boxing footwork drills',
            'Golf chipping techniques'
        ];

        $bodies = [
            "I've been trying different protein powders but haven't found one that mixes well and doesn't have that chalky taste. Any recommendations for something that's both effective and palatable?",
            "After a really intense workout session, I find it hard to recover properly. What are your go-to recovery methods?",
            "I'm trying to decide between morning and evening workouts. What works best for you and why?",
            "My basketball shooting accuracy has been inconsistent lately. Any tips on improving form and consistency?",
            "I'm new to swimming and struggling with my breathing technique. Any advice for beginners?",
            "I'm training for a marathon and need recommendations for running shoes that provide good support for long distances.",
            "My tennis serve keeps going into the net. How can I improve my serve technique?",
            "I'm planning to start cycling but concerned about safety. What are the essential safety tips for beginners?",
            "I play midfield in football and want to improve my training. Any specific drills you'd recommend?",
            "I'm interested in learning martial arts for self-defense. Which discipline would be best for beginners?",
            "My golf swing feels inconsistent. Any tips on improving swing mechanics?",
            "Our volleyball team needs to work on coordination. Any team drills that help with positioning and communication?",
            "I'm struggling with footwork in badminton. Any specific drills to improve movement on court?",
            "My table tennis serve keeps getting returned easily. How can I add more spin and variation?",
            "I'm new to cricket and want to improve my batting. Any basic tips for beginners?",
            "My hockey stick handling needs work. Any drills to improve control and speed?",
            "I'm learning rugby and need help with proper tackling technique. Any safety tips?",
            "I want to improve my boxing combinations. Any drills to practice different punch sequences?",
            "My golf putting is inconsistent. Any tips on improving putting accuracy and distance control?",
            "I'm working towards my next martial arts belt. Any advice on progression and what to focus on?",
            "I struggle with breathing while swimming freestyle. Any techniques to improve breathing rhythm?",
            "I keep getting running injuries. What are the best ways to prevent common running injuries?",
            "Our basketball team needs to improve our defense. Any strategies for better team defense?",
            "I play tennis doubles and need help with positioning. Any tips on court positioning and communication?",
            "I'm struggling with hill climbing on my bike. Any techniques to improve climbing performance?",
            "I'm a football goalkeeper and want to improve my training. Any specific drills for goalkeepers?",
            "My tennis backhand is weak. Any tips on improving backhand technique and power?",
            "I want to improve my freestyle swimming technique. Any drills to work on form?",
            "I'm incorporating interval training into my running routine. Any tips on structuring intervals?",
            "My basketball dribbling needs work. Any drills to improve ball handling and control?",
            "I'm practicing at the driving range. Any tips on making the most of range sessions?",
            "I'm new to martial arts sparring. Any safety tips and techniques for beginners?",
            "Our volleyball team needs serving strategies. Any tips on different serve types and placement?",
            "I want to improve my badminton smash. Any techniques to increase power and accuracy?",
            "My table tennis spin shots aren't effective. Any tips on adding more spin?",
            "I'm learning cricket bowling. Any tips on proper bowling technique and line/length?",
            "Our hockey team needs penalty corner strategies. Any tips on execution and positioning?",
            "I'm learning rugby scrum technique. Any safety tips and proper form?",
            "I want to improve my boxing footwork. Any drills to work on movement and positioning?",
            "My golf chipping is inconsistent. Any tips on improving short game accuracy?"
        ];

        for ($i = 0; $i < 40; $i++) {
            $user = $users->random();
            $gameType = $faker->boolean(60) ? $gameTypes->random() : null;

            Discussion::create([
                'user_id' => $user->id,
                'game_type_id' => $gameType ? $gameType->id : null,
                'title' => $topics[$i],
                'body' => $bodies[$i],
                'is_featured' => $faker->boolean(10),
                'view_count' => $faker->numberBetween(10, 500),
            ]);
        }
    }

    private function seedComments(): void
    {
        $this->command->info('💭 Seeding comments...');

        $users = User::all();
        $discussions = Discussion::all();

        foreach ($discussions as $discussion) {
            $commentCount = rand(2, 8);

            for ($i = 0; $i < $commentCount; $i++) {
                Comment::create([
                    'user_id' => $users->random()->id,
                    'discussion_id' => $discussion->id,
                    'body' => $faker->paragraph(),
                    'created_at' => $faker->dateTimeBetween($discussion->created_at, 'now'),
                ]);
            }
        }
    }

    private function seedLikes(): void
    {
        $this->command->info('👍 Seeding likes...');

        $users = User::all();
        $discussions = Discussion::all();
        $comments = Comment::all();

        // Like discussions
        foreach ($discussions as $discussion) {
            $likeCount = rand(0, 15);
            $likers = $users->random(min($likeCount, $users->count()));

            foreach ($likers as $liker) {
                Like::create([
                    'user_id' => $liker->id,
                    'likeable_type' => Discussion::class,
                    'likeable_id' => $discussion->id,
                ]);
            }
        }

        // Like comments
        foreach ($comments as $comment) {
            if ($faker->boolean(40)) {
                $likeCount = rand(0, 8);
                $likers = $users->random(min($likeCount, $users->count()));

                foreach ($likers as $liker) {
                    Like::create([
                        'user_id' => $liker->id,
                        'likeable_type' => Comment::class,
                        'likeable_id' => $comment->id,
                    ]);
                }
            }
        }
    }

    private function seedTournaments(): void
    {
        $this->command->info('🏆 Seeding tournaments...');

        $users = User::all();
        $gameTypes = GameType::all();

        $tournaments = [
            [
                'name' => 'Summer Tennis Championship',
                'description' => 'Join the biggest tennis event of the season! Compete against the best players in the region.',
                'sport' => 'Tennis',
                'start_date' => Carbon::now()->addDays(30),
                'end_date' => Carbon::now()->addDays(32),
                'location' => 'Tennis Club, London',
                'max_participants' => 32,
                'entry_fee' => 25.00,
                'prize_pool' => 1000.00,
                'format' => 'Single Elimination',
                'skill_level' => 'Mixed',
                'is_approved' => true,
            ],
            [
                'name' => 'Basketball 3v3 Tournament',
                'description' => 'Fast-paced 3v3 basketball tournament for all skill levels.',
                'sport' => 'Basketball',
                'start_date' => Carbon::now()->addDays(15),
                'end_date' => Carbon::now()->addDays(15),
                'location' => 'Sports Complex, Manchester',
                'max_participants' => 24,
                'entry_fee' => 15.00,
                'prize_pool' => 500.00,
                'format' => 'Round Robin',
                'skill_level' => 'Intermediate',
                'is_approved' => true,
            ],
            [
                'name' => 'Football 5-a-side League',
                'description' => 'Weekly 5-a-side football league running for 8 weeks.',
                'sport' => 'Football',
                'start_date' => Carbon::now()->addDays(7),
                'end_date' => Carbon::now()->addDays(56),
                'location' => 'Football Field, Birmingham',
                'max_participants' => 40,
                'entry_fee' => 50.00,
                'prize_pool' => 800.00,
                'format' => 'League',
                'skill_level' => 'Mixed',
                'is_approved' => true,
            ],
            [
                'name' => 'Swimming Gala',
                'description' => 'Annual swimming competition with multiple events.',
                'sport' => 'Swimming',
                'start_date' => Carbon::now()->addDays(45),
                'end_date' => Carbon::now()->addDays(45),
                'location' => 'Swimming Pool, Liverpool',
                'max_participants' => 50,
                'entry_fee' => 20.00,
                'prize_pool' => 600.00,
                'format' => 'Multiple Events',
                'skill_level' => 'Mixed',
                'is_approved' => true,
            ],
            [
                'name' => 'Golf Championship',
                'description' => '18-hole golf championship for serious players.',
                'sport' => 'Golf',
                'start_date' => Carbon::now()->addDays(20),
                'end_date' => Carbon::now()->addDays(20),
                'location' => 'Golf Course, Leeds',
                'max_participants' => 60,
                'entry_fee' => 75.00,
                'prize_pool' => 1500.00,
                'format' => 'Stroke Play',
                'skill_level' => 'Advanced',
                'is_approved' => true,
            ],
        ];

        foreach ($tournaments as $tournamentData) {
            $gameType = $gameTypes->where('name', $tournamentData['sport'])->first();

            Tournament::create([
                'name' => $tournamentData['name'],
                'description' => $tournamentData['description'],
                'game_type_id' => $gameType ? $gameType->id : null,
                'start_date' => $tournamentData['start_date'],
                'end_date' => $tournamentData['end_date'],
                'location' => $tournamentData['location'],
                'max_participants' => $tournamentData['max_participants'],
                'entry_fee' => $tournamentData['entry_fee'],
                'prize_pool' => $tournamentData['prize_pool'],
                'format' => $tournamentData['format'],
                'skill_level' => $tournamentData['skill_level'],
                'is_approved' => $tournamentData['is_approved'],
                'organiser_id' => $users->random()->id,
            ]);
        }
    }

    private function seedTournamentBrackets(): void
    {
        $this->command->info('🏗️ Seeding tournament brackets...');

        $tournaments = Tournament::all();

        foreach ($tournaments as $tournament) {
            $bracketCount = rand(1, 3);

            for ($i = 0; $i < $bracketCount; $i++) {
                TournamentBracket::create([
                    'tournament_id' => $tournament->id,
                    'name' => "Bracket " . ($i + 1),
                    'description' => $faker->sentence(),
                    'max_teams' => rand(8, 16),
                ]);
            }
        }
    }

    private function seedTournamentMatches(): void
    {
        $this->command->info('⚽ Seeding tournament matches...');

        $tournaments = Tournament::all();
        $users = User::all();

        foreach ($tournaments as $tournament) {
            $matchCount = rand(4, 12);

            for ($i = 0; $i < $matchCount; $i++) {
                $player1 = $users->random();
                $player2 = $users->random();

                TournamentMatch::create([
                    'tournament_id' => $tournament->id,
                    'player1_id' => $player1->id,
                    'player2_id' => $player2->id,
                    'winner_id' => $faker->boolean(50) ? $player1->id : $player2->id,
                    'score' => $faker->numberBetween(1, 3) . '-' . $faker->numberBetween(0, 2),
                    'match_date' => $faker->dateTimeBetween($tournament->start_date, $tournament->end_date),
                    'status' => $faker->randomElement(['scheduled', 'in_progress', 'completed']),
                ]);
            }
        }
    }

    private function seedConversations(): void
    {
        $this->command->info('💬 Seeding conversations...');

        $users = User::all();

        // Create some direct conversations
        for ($i = 0; $i < 15; $i++) {
            $user1 = $users->random();
            $user2 = $users->random();

            if ($user1->id !== $user2->id) {
                Conversation::create([
                    'name' => $faker->optional(0.3)->sentence(3, false),
                    'type' => 'direct',
                    'created_by' => $user1->id,
                ]);
            }
        }

        // Create some group conversations
        for ($i = 0; $i < 5; $i++) {
            Conversation::create([
                'name' => $faker->sentence(3, false),
                'type' => 'group',
                'created_by' => $users->random()->id,
            ]);
        }
    }

    private function seedConversationParticipants(): void
    {
        $this->command->info('👥 Seeding conversation participants...');

        $users = User::all();
        $conversations = Conversation::all();

        foreach ($conversations as $conversation) {
            if ($conversation->type === 'direct') {
                // Add two participants for direct conversations
                $participants = $users->random(2);
                foreach ($participants as $participant) {
                    ConversationParticipant::create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $participant->id,
                        'joined_at' => $conversation->created_at,
                    ]);
                }
            } else {
                // Add multiple participants for group conversations
                $participantCount = rand(3, 8);
                $participants = $users->random($participantCount);
                foreach ($participants as $participant) {
                    ConversationParticipant::create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $participant->id,
                        'joined_at' => $faker->dateTimeBetween($conversation->created_at, 'now'),
                    ]);
                }
            }
        }
    }

    private function seedMessages(): void
    {
        $this->command->info('💬 Seeding messages...');

        $conversations = Conversation::all();
        $users = User::all();

        foreach ($conversations as $conversation) {
            $messageCount = rand(5, 25);

            for ($i = 0; $i < $messageCount; $i++) {
                $participant = $conversation->participants->random();

                Message::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $participant->user_id,
                    'content' => $faker->paragraph(),
                    'created_at' => $faker->dateTimeBetween($conversation->created_at, 'now'),
                ]);
            }
        }
    }

    private function seedTypingIndicators(): void
    {
        $this->command->info('⌨️ Seeding typing indicators...');

        $conversations = Conversation::all();
        $users = User::all();

        // Create some active typing indicators
        for ($i = 0; $i < 5; $i++) {
            $conversation = $conversations->random();
            $participant = $conversation->participants->random();

            TypingIndicator::create([
                'conversation_id' => $conversation->id,
                'user_id' => $participant->user_id,
                'is_typing' => true,
                'started_at' => now(),
            ]);
        }
    }

    private function seedUserSkillLevels(): void
    {
        $this->command->info('🎯 Seeding user skill levels...');

        $users = User::all();
        $gameTypes = GameType::all();

        foreach ($users as $user) {
            $skillCount = rand(2, 6);
            $selectedSports = $gameTypes->random($skillCount);

            foreach ($selectedSports as $sport) {
                UserSkillLevel::create([
                    'user_id' => $user->id,
                    'game_type_id' => $sport->id,
                    'skill_level' => rand(1, 3),
                ]);
            }
        }
    }

    private function seedUserPreferredFacilities(): void
    {
        $this->command->info('🏟️ Seeding user preferred facilities...');

        $users = User::all();
        $facilities = [
            'Community Center', 'Sports Complex', 'Local Park', 'University Gym',
            'Recreation Center', 'Tennis Club', 'Swimming Pool', 'Football Field',
            'Basketball Court', 'Athletics Track', 'Golf Course', 'Boxing Gym',
            'Martial Arts Dojo', 'Cricket Ground', 'Rugby Club'
        ];

        foreach ($users as $user) {
            $facilityCount = rand(1, 4);
            $selectedFacilities = array_rand(array_flip($facilities), $facilityCount);

            if (!is_array($selectedFacilities)) {
                $selectedFacilities = [$selectedFacilities];
            }

            foreach ($selectedFacilities as $facility) {
                UserPreferredFacility::create([
                    'user_id' => $user->id,
                    'facility_name' => $facility,
                    'preference_level' => rand(1, 5),
                ]);
            }
        }
    }

    private function seedGames(): void
    {
        $this->command->info('🎮 Seeding games...');

        $gameTypes = GameType::all();
        $users = User::all();

        for ($i = 0; $i < 20; $i++) {
            $gameType = $gameTypes->random();
            $organiser = $users->random();

            Game::create([
                'name' => $faker->sentence(3, false),
                'game_type_id' => $gameType->id,
                'organiser_id' => $organiser->id,
                'description' => $faker->paragraph(),
                'location' => $faker->city() . ', UK',
                'max_players' => rand(4, 12),
                'is_active' => true,
            ]);
        }
    }
}
