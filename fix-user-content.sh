#!/bin/bash

# Fix User Content Association Script
# This script ensures every user has some content associated with them

echo "🔧 Fixing user content associations..."

cd "$(dirname "$0")"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the CommunityNetworkApi directory."
    exit 1
fi

echo "📊 Current user content status:"
php artisan tinker --execute="
echo '=== USER CONTENT ANALYSIS ===' . PHP_EOL;
\$users = App\Models\User::all();
foreach(\$users as \$user) {
    echo \$user->email . ':' . PHP_EOL;
    echo '  - Discussions: ' . App\Models\Discussion::where('user_id', \$user->id)->count() . PHP_EOL;
    echo '  - Game Events (organizer): ' . App\Models\GameEvent::where('organiser_id', \$user->id)->count() . PHP_EOL;
    echo '  - Game Event Participants: ' . App\Models\GameEventParticipant::where('user_id', \$user->id)->count() . PHP_EOL;
    echo '  - Conversations: ' . App\Models\ConversationParticipant::where('user_id', \$user->id)->count() . PHP_EOL;
    echo '  - Messages: ' . App\Models\Message::where('user_id', \$user->id)->count() . PHP_EOL;
}
"

echo "🔧 Creating missing content for users..."
php artisan tinker --execute="
echo 'Creating additional content for users...' . PHP_EOL;

// Get all users
\$users = App\Models\User::all();
\$gameTypes = App\Models\GameType::all();
\$discussions = App\Models\Discussion::all();

// Ensure every user has at least one discussion
foreach(\$users as \$user) {
    if (App\Models\Discussion::where('user_id', \$user->id)->count() == 0) {
        App\Models\Discussion::create([
            'user_id' => \$user->id,
            'game_type_id' => \$gameTypes->random()->id,
            'title' => 'My first discussion by ' . \$user->first_name,
            'body' => 'Hello everyone! I am ' . \$user->first_name . ' and I am excited to join this community. Looking forward to connecting with fellow sports enthusiasts!',
            'is_featured' => false,
            'view_count' => rand(5, 50),
        ]);
        echo 'Created discussion for ' . \$user->email . PHP_EOL;
    }
}

// Ensure every user has at least one game event they're organizing
foreach(\$users as \$user) {
    if (App\Models\GameEvent::where('organiser_id', \$user->id)->count() == 0) {
        App\Models\GameEvent::create([
            'game_type_id' => \$gameTypes->random()->id,
            'organiser_id' => \$user->id,
            'title' => \$user->first_name . '\'s ' . \$gameTypes->random()->name . ' Event',
            'location' => 'Local Sports Center',
            'starts_at' => now()->addDays(rand(1, 30)),
            'skill_level' => rand(1, 3),
            'max_participants' => rand(8, 20),
            'waiting_list_enabled' => true,
            'notes' => 'Join me for a fun ' . \$gameTypes->random()->name . ' session! All skill levels welcome.',
            'venue_booked' => true,
        ]);
        echo 'Created game event for ' . \$user->email . PHP_EOL;
    }
}

// Create some conversations and ensure users participate
\$existingConversations = App\Models\Conversation::count();
if (\$existingConversations < 10) {
    for (\$i = 0; \$i < 10; \$i++) {
        \$conversation = App\Models\Conversation::create([
            'name' => 'Group Chat ' . (\$i + 1),
            'type' => 'group',
            'created_by' => \$users->random()->id,
        ]);
        
        // Add 3-6 participants to each conversation
        \$participants = \$users->random(rand(3, 6));
        foreach (\$participants as \$participant) {
            App\Models\ConversationParticipant::create([
                'conversation_id' => \$conversation->id,
                'user_id' => \$participant->id,
                'joined_at' => now(),
            ]);
            
            // Add some messages from each participant
            for (\$j = 0; \$j < rand(1, 5); \$j++) {
                App\Models\Message::create([
                    'conversation_id' => \$conversation->id,
                    'user_id' => \$participant->id,
                    'content' => 'Hello from ' . \$participant->first_name . '! Message ' . (\$j + 1),
                    'created_at' => now()->subMinutes(rand(1, 60)),
                ]);
            }
        }
        echo 'Created conversation with ' . \$participants->count() . ' participants' . PHP_EOL;
    }
}

// Ensure every user participates in at least one game event
foreach(\$users as \$user) {
    if (App\Models\GameEventParticipant::where('user_id', \$user->id)->count() == 0) {
        \$gameEvent = App\Models\GameEvent::inRandomOrder()->first();
        if (\$gameEvent && \$gameEvent->organiser_id != \$user->id) {
            App\Models\GameEventParticipant::create([
                'game_event_id' => \$gameEvent->id,
                'user_id' => \$user->id,
                'is_waiting' => false,
                'joined_at' => now(),
            ]);
            echo 'Added ' . \$user->email . ' to game event' . PHP_EOL;
        }
    }
}

echo 'Content creation completed!' . PHP_EOL;
"

echo "📊 Final user content status:"
php artisan tinker --execute="
echo '=== FINAL USER CONTENT ANALYSIS ===' . PHP_EOL;
\$users = App\Models\User::all();
foreach(\$users as \$user) {
    echo \$user->email . ':' . PHP_EOL;
    echo '  - Discussions: ' . App\Models\Discussion::where('user_id', \$user->id)->count() . PHP_EOL;
    echo '  - Game Events (organizer): ' . App\Models\GameEvent::where('organiser_id', \$user->id)->count() . PHP_EOL;
    echo '  - Game Event Participants: ' . App\Models\GameEventParticipant::where('user_id', \$user->id)->count() . PHP_EOL;
    echo '  - Conversations: ' . App\Models\ConversationParticipant::where('user_id', \$user->id)->count() . PHP_EOL;
    echo '  - Messages: ' . App\Models\Message::where('user_id', \$user->id)->count() . PHP_EOL;
}

echo '=== TOTAL COUNTS ===' . PHP_EOL;
echo 'Users: ' . App\Models\User::count() . PHP_EOL;
echo 'Discussions: ' . App\Models\Discussion::count() . PHP_EOL;
echo 'Game Events: ' . App\Models\GameEvent::count() . PHP_EOL;
echo 'Conversations: ' . App\Models\Conversation::count() . PHP_EOL;
echo 'Messages: ' . App\Models\Message::count() . PHP_EOL;
"

echo "✅ User content associations fixed!"
echo ""
echo "🔑 Test Users:"
echo "   - test@example.com / password123"
echo "   - john@example.com / password123"
echo "   - sarah@example.com / password123"
echo "   - mike@example.com / password123"
echo ""
echo "🌐 Test the application at: http://localhost:8080"
echo "🔑 All users now have discussions, game events, and conversations!"


