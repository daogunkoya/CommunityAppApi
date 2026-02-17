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

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Fixing user content relationships...\n";

// Get all users
$users = User::all();
$gameTypes = GameType::all();

// Ensure test user exists
if (!User::where('email', 'test@example.com')->exists()) {
    echo "👤 Creating test user...\n";
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
    $users = User::all(); // Refresh users collection
}

echo "Found {$users->count()} users and {$gameTypes->count()} game types\n";

// 1. Ensure every user has skill levels
echo "🎯 Adding skill levels for all users...\n";
foreach ($users as $user) {
    $existingSkillLevels = $user->skillLevels()->count();
    if ($existingSkillLevels < 2) {
        $sportsToAdd = $gameTypes->random(3);
        foreach ($sportsToAdd as $sport) {
            UserSkillLevel::firstOrCreate([
                'user_id' => $user->id,
                'game_type_id' => $sport->id,
            ], [
                'skill_level' => ['beginner', 'intermediate', 'advanced', 'expert'][rand(0, 3)],
            ]);
        }
    }
}

// 2. Ensure every user has preferred facilities
echo "🏟️ Adding preferred facilities for all users...\n";
$facilities = [
    'Community Center', 'Sports Complex', 'Local Park', 'University Gym',
    'Recreation Center', 'Tennis Club', 'Swimming Pool', 'Football Field',
    'Basketball Court', 'Athletics Track', 'Golf Course', 'Boxing Gym',
    'Martial Arts Dojo', 'Cricket Ground', 'Rugby Club'
];

foreach ($users as $user) {
    $existingFacilities = $user->preferredFacilities()->count();
    if ($existingFacilities < 2) {
        $facilitiesToAdd = array_rand(array_flip($facilities), 3);
        if (!is_array($facilitiesToAdd)) {
            $facilitiesToAdd = [$facilitiesToAdd];
        }
        foreach ($facilitiesToAdd as $facility) {
            UserPreferredFacility::firstOrCreate([
                'user_id' => $user->id,
                'facility_name' => $facility,
            ], [
                'facility_id' => 'facility_' . strtolower(str_replace(' ', '_', $facility)),
                'facility_address' => $facility . ', London, UK',
                'latitude' => 51.5074 + (rand(-10, 10) / 100),
                'longitude' => -0.1278 + (rand(-10, 10) / 100),
                'membership_type' => ['member', 'pay-per-use', 'other'][rand(0, 2)],
            ]);
        }
    }
}

// 3. Ensure every user participates in some game events
echo "🎮 Adding game event participation for all users...\n";
$gameEvents = GameEvent::all();
foreach ($users as $user) {
    $existingParticipations = GameEventParticipant::where('user_id', $user->id)->count();
    if ($existingParticipations < 2) {
        $eventsToJoin = $gameEvents->random(min(3, $gameEvents->count()));
        foreach ($eventsToJoin as $event) {
            if ($event->organiser_id !== $user->id) {
                GameEventParticipant::firstOrCreate([
                    'game_event_id' => $event->id,
                    'user_id' => $user->id,
                ], [
                    'is_waiting' => rand(0, 1),
                ]);
            }
        }
    }
}

// 4. Ensure every user has some discussions
echo "💬 Adding discussions for all users...\n";
$discussionTopics = [
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
    'Running injury prevention'
];

$discussionBodies = [
    "I've been trying different protein powders but haven't found one that mixes well and doesn't have that chalky taste. Any recommendations?",
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
    "I keep getting running injuries. What are the best ways to prevent common running injuries?"
];

foreach ($users as $index => $user) {
    $existingDiscussions = Discussion::where('user_id', $user->id)->count();
    if ($existingDiscussions < 1) {
        $topicIndex = $index % count($discussionTopics);
        $gameType = $gameTypes->random();

        Discussion::create([
            'user_id' => $user->id,
            'game_type_id' => $gameType->id,
            'title' => $discussionTopics[$topicIndex],
            'body' => $discussionBodies[$topicIndex],
            'is_featured' => rand(0, 10) === 0, // 10% chance
            'view_count' => rand(10, 500),
        ]);
    }
}

// 5. Ensure every user has some comments
echo "💭 Adding comments for all users...\n";
$discussions = Discussion::all();
foreach ($users as $user) {
    $existingComments = Comment::where('user_id', $user->id)->count();
    if ($existingComments < 2) {
        $discussionsToCommentOn = $discussions->random(min(3, $discussions->count()));
        foreach ($discussionsToCommentOn as $discussion) {
            if ($discussion->user_id !== $user->id) {
                Comment::create([
                    'user_id' => $user->id,
                    'discussion_id' => $discussion->id,
                    'body' => "Great discussion! I've been thinking about this too. " . fake()->sentence(),
                ]);
            }
        }
    }
}

// 6. Ensure every user has some likes
echo "👍 Adding likes for all users...\n";
$discussions = Discussion::all();
$comments = Comment::all();

foreach ($users as $user) {
    $existingLikes = Like::where('user_id', $user->id)->count();
    if ($existingLikes < 3) {
        // Like some discussions
        $discussionsToLike = $discussions->random(min(2, $discussions->count()));
        foreach ($discussionsToLike as $discussion) {
            if ($discussion->user_id !== $user->id) {
                Like::firstOrCreate([
                    'user_id' => $user->id,
                    'likeable_type' => Discussion::class,
                    'likeable_id' => $discussion->id,
                ]);
            }
        }

        // Like some comments
        $commentsToLike = $comments->random(min(2, $comments->count()));
        foreach ($commentsToLike as $comment) {
            if ($comment->user_id !== $user->id) {
                Like::firstOrCreate([
                    'user_id' => $user->id,
                    'likeable_type' => Comment::class,
                    'likeable_id' => $comment->id,
                ]);
            }
        }
    }
}

// 7. Ensure every user has conversations and messages
echo "💬 Adding conversations and messages for all users...\n";
foreach ($users as $user) {
    $existingConversations = ConversationParticipant::where('user_id', $user->id)->count();
    if ($existingConversations < 1) {
        // Create a direct conversation with another user
        $otherUser = $users->where('id', '!=', $user->id)->random();

        $conversation = Conversation::create([
            'name' => null,
            'type' => 'direct',
            'created_by' => $user->id,
        ]);

        // Add both users to the conversation
        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'joined_at' => now(),
        ]);

        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $otherUser->id,
            'joined_at' => now(),
        ]);

        // Add some messages to the conversation
        for ($i = 0; $i < rand(3, 8); $i++) {
            $messageUser = $i % 2 === 0 ? $user : $otherUser;
            Message::create([
                'conversation_id' => $conversation->id,
                'user_id' => $messageUser->id,
                'content' => fake()->sentence(),
                'created_at' => now()->subMinutes(rand(1, 60)),
            ]);
        }
    }
}

// 8. Create some group conversations
echo "👥 Creating group conversations...\n";
$groupConversationNames = [
    'London Sports Enthusiasts',
    'Tennis Club Chat',
    'Basketball Team',
    'Running Group',
    'Swimming Squad'
];

for ($i = 0; $i < 3; $i++) {
    $conversation = Conversation::create([
        'name' => $groupConversationNames[$i],
        'type' => 'group',
        'created_by' => $users->random()->id,
    ]);

    // Add 4-6 users to the group
    $groupMembers = $users->random(rand(4, 6));
    foreach ($groupMembers as $member) {
        ConversationParticipant::create([
            'conversation_id' => $conversation->id,
            'user_id' => $member->id,
            'joined_at' => now(),
        ]);
    }

    // Add some messages to the group
    for ($j = 0; $j < rand(5, 15); $j++) {
        $messageUser = $groupMembers->random();
        Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $messageUser->id,
            'content' => fake()->sentence(),
            'created_at' => now()->subMinutes(rand(1, 120)),
        ]);
    }
}

echo "✅ User content relationships fixed!\n";

// Show summary
echo "\n📊 Final Data Summary:\n";
echo "Users: " . User::count() . "\n";
echo "Game Events: " . GameEvent::count() . "\n";
echo "Discussions: " . Discussion::count() . "\n";
echo "Comments: " . Comment::count() . "\n";
echo "Likes: " . Like::count() . "\n";
echo "Conversations: " . Conversation::count() . "\n";
echo "Messages: " . Message::count() . "\n";
echo "Game Event Participants: " . GameEventParticipant::count() . "\n";
echo "User Skill Levels: " . UserSkillLevel::count() . "\n";
echo "User Preferred Facilities: " . UserPreferredFacility::count() . "\n";

echo "\n🔑 Test User Credentials:\n";
echo "Email: test@example.com\n";
echo "Password: password123\n";

echo "\n🎉 All users now have content and relationships!\n";
