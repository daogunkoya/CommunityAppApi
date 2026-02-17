<?php

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\GameType;
use App\Models\GameEvent;
use App\Models\GameEventParticipant;
use App\Models\Discussion;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Message;
use App\Models\UserSkillLevel;
use App\Models\UserPreferredFacility;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🌱 Starting production seeding...\n";

// Create game types if they don't exist
if (GameType::count() === 0) {
    echo "🏀 Creating game types...\n";
    $sports = [
        ['name' => 'Basketball', 'description' => 'Team sport played with a ball and hoop', 'icon_path' => 'basketball.png', 'color' => '#ff6b35'],
        ['name' => 'Football', 'description' => 'Team sport played with a ball and goal', 'icon_path' => 'football.png', 'color' => '#4ecdc4'],
        ['name' => 'Tennis', 'description' => 'Racket sport played on a court', 'icon_path' => 'tennis.png', 'color' => '#45b7d1'],
        ['name' => 'Swimming', 'description' => 'Water sport in pools or open water', 'icon_path' => 'swimming.png', 'color' => '#96ceb4'],
        ['name' => 'Running', 'description' => 'Individual sport on tracks or roads', 'icon_path' => 'running.png', 'color' => '#feca57'],
        ['name' => 'Cycling', 'description' => 'Bicycle sport on roads or tracks', 'icon_path' => 'cycling.png', 'color' => '#ff9ff3'],
        ['name' => 'Golf', 'description' => 'Club and ball sport on courses', 'icon_path' => 'golf.png', 'color' => '#54a0ff'],
        ['name' => 'Volleyball', 'description' => 'Team sport with net and ball', 'icon_path' => 'volleyball.png', 'color' => '#5f27cd'],
        ['name' => 'Badminton', 'description' => 'Racket sport with shuttlecock', 'icon_path' => 'badminton.png', 'color' => '#00d2d3'],
        ['name' => 'Table Tennis', 'description' => 'Indoor racket sport', 'icon_path' => 'table-tennis.png', 'color' => '#ff6348'],
        ['name' => 'Cricket', 'description' => 'Bat and ball team sport', 'icon_path' => 'cricket.png', 'color' => '#2ed573'],
        ['name' => 'Hockey', 'description' => 'Stick and ball team sport', 'icon_path' => 'hockey.png', 'color' => '#1e90ff'],
        ['name' => 'Rugby', 'description' => 'Contact team sport with oval ball', 'icon_path' => 'rugby.png', 'color' => '#ff4757'],
        ['name' => 'Boxing', 'description' => 'Combat sport with gloves', 'icon_path' => 'boxing.png', 'color' => '#ff3838'],
        ['name' => 'Martial Arts', 'description' => 'Various combat and self-defense disciplines', 'icon_path' => 'martial-arts.png', 'color' => '#ff6b6b'],
    ];

    foreach ($sports as $sport) {
        GameType::create($sport);
    }
}

// Create users if they don't exist
if (User::count() === 0) {
    echo "👥 Creating users...\n";

    // Create test users
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

    User::create([
        'first_name' => 'Test',
        'last_name' => 'User',
        'email' => 'test@example.com',
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
    ];

    foreach ($users as $userData) {
        User::create([
            'first_name' => $userData['first_name'],
            'last_name' => $userData['last_name'],
            'email' => $userData['email'],
            'password' => Hash::make('password'),
            'location' => $userData['location'],
            'gender' => ['male', 'female', 'other'][array_rand(['male', 'female', 'other'])],
            'date_of_birth' => date('Y-m-d', strtotime('-' . rand(18, 50) . ' years')),
            'bio' => 'Sports enthusiast from ' . $userData['location'],
            'phone' => '+44 7911 ' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT),
            'profile_picture' => "https://ui-avatars.com/api/?name={$userData['first_name']}+{$userData['last_name']}&background=random",
            'is_active' => true,
            'email_verified_at' => now(),
            'last_login_at' => now(),
        ]);
    }
}

// Get all users and game types
$users = User::all();
$gameTypes = GameType::all();

echo "Found {$users->count()} users and {$gameTypes->count()} game types\n";

// Create game events
if (GameEvent::count() === 0) {
    echo "🎮 Creating game events...\n";
    $venues = [
        'Community Center', 'Sports Complex', 'Local Park', 'University Gym', 'Recreation Center',
        'Tennis Club', 'Swimming Pool', 'Football Field', 'Basketball Court', 'Athletics Track',
        'Golf Course', 'Boxing Gym', 'Martial Arts Dojo', 'Cricket Ground', 'Rugby Club'
    ];

    for ($i = 0; $i < 20; $i++) {
        $startDate = now()->addDays(rand(1, 30))->addHours(rand(0, 23));
        $gameType = $gameTypes->random();
        $organiser = $users->random();

        GameEvent::create([
            'game_type_id' => $gameType->id,
            'organiser_id' => $organiser->id,
            'location' => $venues[array_rand($venues)],
            'starts_at' => $startDate,
            'skill_level' => rand(1, 3),
            'max_participants' => rand(4, 20),
            'waiting_list_enabled' => rand(0, 1),
            'notes' => 'Join us for a great game!',
            'venue_booked' => rand(0, 1),
        ]);
    }
}

// Create discussions
if (Discussion::count() === 0) {
    echo "💬 Creating discussions...\n";
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
    ];

    foreach ($users as $index => $user) {
        $topicIndex = $index % count($topics);
        $gameType = $gameTypes->random();

        Discussion::create([
            'user_id' => $user->id,
            'game_type_id' => $gameType->id,
            'title' => $topics[$topicIndex],
            'body' => 'I would love to discuss this topic with fellow sports enthusiasts. What are your thoughts?',
            'is_featured' => rand(0, 10) === 0,
            'view_count' => rand(10, 500),
        ]);
    }
}

// Create conversations and messages
if (Conversation::count() === 0) {
    echo "💬 Creating conversations...\n";

    // Create direct conversations
    for ($i = 0; $i < 10; $i++) {
        $user1 = $users->random();
        $user2 = $users->random();

        if ($user1->id !== $user2->id) {
            $conversation = Conversation::create([
                'name' => null,
                'type' => 'direct',
                'created_by' => $user1->id,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user1->id,
                'joined_at' => now(),
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $user2->id,
                'joined_at' => now(),
            ]);

            // Add some messages
            for ($j = 0; $j < rand(3, 8); $j++) {
                $messageUser = $j % 2 === 0 ? $user1 : $user2;
                Message::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $messageUser->id,
                    'content' => 'Hello! How are you doing?',
                    'created_at' => now()->subMinutes(rand(1, 60)),
                ]);
            }
        }
    }
}

// Add game event participations
if (GameEventParticipant::count() === 0) {
    echo "👥 Adding game event participations...\n";
    $gameEvents = GameEvent::all();

    foreach ($users as $user) {
        $eventsToJoin = $gameEvents->random(min(3, $gameEvents->count()));
        foreach ($eventsToJoin as $event) {
            if ($event->organiser_id !== $user->id) {
                GameEventParticipant::create([
                    'game_event_id' => $event->id,
                    'user_id' => $user->id,
                    'is_waiting' => rand(0, 1),
                ]);
            }
        }
    }
}

echo "✅ Production seeding completed!\n";

// Show summary
echo "\n📊 Final Data Summary:\n";
echo "Users: " . User::count() . "\n";
echo "Game Events: " . GameEvent::count() . "\n";
echo "Discussions: " . Discussion::count() . "\n";
echo "Conversations: " . Conversation::count() . "\n";
echo "Messages: " . Message::count() . "\n";
echo "Game Event Participants: " . GameEventParticipant::count() . "\n";

echo "\n🔑 Test User Credentials:\n";
echo "Email: test@example.com\n";
echo "Password: password123\n";
echo "Email: john@example.com\n";
echo "Password: password\n";

echo "\n🎉 Production seeding completed successfully!\n";


