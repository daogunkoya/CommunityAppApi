<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\GameType;
use App\Models\GameEvent;
use App\Models\Discussion;
use App\Models\Comment;
use App\Models\Like;
use App\Models\GameEventParticipant;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->truncateTables();
        $this->createSportTypes();
        $this->createUsers();
        $this->createGameEvents();
        $this->createDiscussions();
        $this->createComments();
        $this->createLikes();
        $this->createEventParticipants();
    }

    private function truncateTables(): void
    {
        // Disable foreign key checks for MySQL
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }

        // Truncate tables in correct order
        DB::table('game_event_participants')->truncate();
        DB::table('likes')->truncate();
        DB::table('comments')->truncate();
        DB::table('discussions')->truncate();
        DB::table('game_events')->truncate();
        DB::table('users')->truncate();
        DB::table('game_types')->truncate();

        // Re-enable foreign key checks for MySQL
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function createSportTypes(): void
    {
        $sports = [
            ['name' => 'Basketball', 'description' => 'Team sport played with a ball and hoop', 'icon_path' => 'basketball.png'],
            ['name' => 'Football', 'description' => 'Soccer - the beautiful game', 'icon_path' => 'football.png'],
            ['name' => 'Tennis', 'description' => 'Racket sport for singles or doubles', 'icon_path' => 'tennis.png'],
            ['name' => 'Swimming', 'description' => 'Water-based fitness and competition', 'icon_path' => 'swimming.png'],
            ['name' => 'Cycling', 'description' => 'Road and mountain biking', 'icon_path' => 'cycling.png'],
            ['name' => 'Running', 'description' => 'Track, road, and trail running', 'icon_path' => 'running.png'],
            ['name' => 'Volleyball', 'description' => 'Team sport with net and ball', 'icon_path' => 'volleyball.png'],
            ['name' => 'Badminton', 'description' => 'Racket sport with shuttlecock', 'icon_path' => 'badminton.png'],
            ['name' => 'Table Tennis', 'description' => 'Indoor table tennis', 'icon_path' => 'table-tennis.png'],
            ['name' => 'Cricket', 'description' => 'Bat and ball team sport', 'icon_path' => 'cricket.png'],
            ['name' => 'Hockey', 'description' => 'Field hockey with sticks', 'icon_path' => 'hockey.png'],
            ['name' => 'Rugby', 'description' => 'Contact team sport', 'icon_path' => 'rugby.png'],
            ['name' => 'Golf', 'description' => 'Precision club and ball sport', 'icon_path' => 'golf.png'],
            ['name' => 'Boxing', 'description' => 'Combat sport with gloves', 'icon_path' => 'boxing.png'],
            ['name' => 'Martial Arts', 'description' => 'Various fighting disciplines', 'icon_path' => 'martial-arts.png'],
        ];

        foreach ($sports as $sport) {
            GameType::create($sport);
        }
    }

    private function createUsers(): void
    {
        // Create test user
        User::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
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
        ]);

        // Create additional users
        $users = [
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
                'password' => Hash::make('password'),
                'location' => $userData['location'],
                'gender' => fake()->randomElement(['male', 'female', 'other']),
                'date_of_birth' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
                'bio' => fake()->paragraph(),
                'phone' => fake()->phoneNumber(),
                'profile_picture' => "https://ui-avatars.com/api/?name={$userData['first_name']}+{$userData['last_name']}&background=random",
                'is_active' => true,
                'email_verified_at' => now(),
                'last_login_at' => fake()->dateTimeBetween('-30 days', 'now'),
            ]);
        }
    }

    private function createGameEvents(): void
    {
        $users = User::all();
        $gameTypes = GameType::all();
        $venues = [
            'Community Center', 'Sports Complex', 'Local Park', 'University Gym', 'Recreation Center',
            'Tennis Club', 'Swimming Pool', 'Football Field', 'Basketball Court', 'Athletics Track',
            'Golf Course', 'Boxing Gym', 'Martial Arts Dojo', 'Cricket Ground', 'Rugby Club'
        ];

        for ($i = 0; $i < 30; $i++) {
            $startDate = fake()->dateTimeBetween('now', '+30 days');
            $gameType = $gameTypes->random();
            $organiser = $users->random();

            GameEvent::create([
                'game_type_id' => $gameType->id,
                'organiser_id' => $organiser->id,
                'location' => fake()->randomElement($venues),
                'starts_at' => $startDate,
                'skill_level' => rand(1, 3),
                'max_participants' => fake()->optional(0.8)->numberBetween(4, 20),
                'waiting_list_enabled' => fake()->boolean(70),
                'notes' => fake()->optional(0.7)->paragraph(),
                'venue_booked' => fake()->boolean(80),
            ]);
        }
    }

    private function createDiscussions(): void
    {
        $users = User::all();
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
            'Cycling hill climbing tips'
        ];

        $bodies = [
            "I've been trying different protein powders but haven't found one that mixes well and doesn't have that chalky taste. Any recommendations for something that's both effective and palatable?",
            "After my intense cardio sessions, I've been feeling more tired than usual. I'm wondering if I'm missing something in my recovery routine. What do you all do for post-workout recovery?",
            "I'm trying to establish a consistent workout routine but can't decide between morning and evening workouts. What time works better for building habits and seeing results?",
            "I've been practicing my shooting but my accuracy isn't improving. Any specific drills or techniques that helped you improve your basketball shooting?",
            "Just started swimming lessons and I'm struggling with my breathing technique. Any tips for beginners on how to coordinate breathing with strokes?",
            "I love Brooks Ghost for long distances - they provide excellent cushioning and support.",
            "Practice your toss consistently and try the continental grip for better control.",
            "Always wear a helmet, use lights at night, and follow traffic rules. Safety first!",
            "Work on your passing accuracy and vision. Practice with cones to improve your spatial awareness.",
            "Krav Maga is excellent for self-defense - it's practical and focuses on real-world scenarios.",
            "Focus on your grip and stance. The fundamentals are key to a consistent swing.",
            "Communication is crucial. Use hand signals and call the ball clearly.",
            "Practice shadow footwork drills and focus on staying on your toes.",
            "Learn the basic topspin and backspin serves first - they're most effective for beginners.",
            "Focus on your stance and watch the ball closely. Timing is everything in batting.",
            "Practice stick handling with cones and work on your wrist movement.",
            "Keep your head up, wrap your arms around the ball carrier, and drive through with your legs.",
            "Shadow boxing with a mirror helps improve form and speed.",
            "Practice distance control with different length putts. Speed is more important than line.",
            "It varies by discipline, but typically 3-6 months between belts for beginners.",
            "Try breathing every 3 strokes for freestyle - it helps establish a rhythm.",
            "Gradual progression, proper shoes, and listening to your body are key.",
            "Stay low, move your feet, and keep your hands up. Anticipation is crucial.",
            "One up, one back positioning works well for most doubles situations.",
            "Use lower gears, maintain a steady cadence, and practice on smaller hills first."
        ];

        for ($i = 0; $i < 25; $i++) {
            $user = $users->random();
            $topic = $topics[$i % count($topics)];
            $body = $bodies[$i % count($bodies)];

            Discussion::create([
                'title' => $topic,
                'body' => $body,
                'user_id' => $user->id,
                'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
                'updated_at' => fake()->dateTimeBetween('-30 days', 'now'),
            ]);
        }
    }

    private function createComments(): void
    {
        $users = User::all();
        $discussions = Discussion::all();
        $comments = [
            "Great question! I've been using Optimum Nutrition Gold Standard and it mixes really well with just water or milk.",
            "I've found that stretching and foam rolling after intense workouts really helps with recovery. Also, make sure you're getting enough protein within 30 minutes.",
            "I prefer morning workouts because it sets a positive tone for the day and I'm less likely to skip them.",
            "Try the Mikan drill - it's great for improving shooting form and consistency.",
            "Focus on exhaling underwater and inhaling when your head is above water. Start with basic breathing drills.",
            "I love Brooks Ghost for long distances - they provide excellent cushioning and support.",
            "Practice your toss consistently and try the continental grip for better control.",
            "Always wear a helmet, use lights at night, and follow traffic rules. Safety first!",
            "Work on your passing accuracy and vision. Practice with cones to improve your spatial awareness.",
            "Krav Maga is excellent for self-defense - it's practical and focuses on real-world scenarios.",
            "Focus on your grip and stance. The fundamentals are key to a consistent swing.",
            "Communication is crucial. Use hand signals and call the ball clearly.",
            "Practice shadow footwork drills and focus on staying on your toes.",
            "Learn the basic topspin and backspin serves first - they're most effective for beginners.",
            "Focus on your stance and watch the ball closely. Timing is everything in batting.",
            "Practice stick handling with cones and work on your wrist movement.",
            "Keep your head up, wrap your arms around the ball carrier, and drive through with your legs.",
            "Shadow boxing with a mirror helps improve form and speed.",
            "Practice distance control with different length putts. Speed is more important than line.",
            "It varies by discipline, but typically 3-6 months between belts for beginners.",
            "Try breathing every 3 strokes for freestyle - it helps establish a rhythm.",
            "Gradual progression, proper shoes, and listening to your body are key.",
            "Stay low, move your feet, and keep your hands up. Anticipation is crucial.",
            "One up, one back positioning works well for most doubles situations.",
            "Use lower gears, maintain a steady cadence, and practice on smaller hills first."
        ];

        foreach ($discussions as $discussion) {
            $commentCount = rand(2, 8);
            for ($i = 0; $i < $commentCount; $i++) {
                $user = $users->random();
                $comment = $comments[array_rand($comments)];

                Comment::create([
                    'body' => $comment,
                    'user_id' => $user->id,
                    'discussion_id' => $discussion->id,
                    'created_at' => fake()->dateTimeBetween($discussion->created_at, 'now'),
                    'updated_at' => fake()->dateTimeBetween($discussion->created_at, 'now'),
                ]);
            }
        }
    }

    private function createLikes(): void
    {
        $users = User::all();
        $discussions = Discussion::all();

        foreach ($discussions as $discussion) {
            $likeCount = rand(0, 15);
            $randomUsers = $users->random($likeCount);

            foreach ($randomUsers as $user) {
                Like::create([
                    'user_id' => $user->id,
                    'likeable_type' => Discussion::class,
                    'likeable_id' => $discussion->id,
                ]);
            }
        }
    }

    private function createEventParticipants(): void
    {
        $users = User::all();
        $events = GameEvent::all();

        foreach ($events as $event) {
            $participantCount = rand(1, min($event->max_participants ?? 10, 10));
            $randomUsers = $users->random($participantCount);

            foreach ($randomUsers as $user) {
                // Skip if user is already the organiser
                if ($user->id === $event->organiser_id) {
                    continue;
                }

                GameEventParticipant::create([
                    'game_event_id' => $event->id,
                    'user_id' => $user->id,
                    'is_waiting' => fake()->boolean(20), // 20% chance of being on waiting list
                ]);
            }
        }
    }
}
