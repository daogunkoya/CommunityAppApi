#!/bin/bash

# Local Database Seeding Script for MatchGrinder
# This script seeds the local database with comprehensive test data

echo "🌱 Starting local database seeding..."

# Navigate to API directory
cd "$(dirname "$0")"

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the CommunityNetworkApi directory."
    exit 1
fi

echo "📊 Current database status:"
php artisan tinker --execute="echo 'Users: ' . App\Models\User::count(); echo 'Game Events: ' . App\Models\GameEvent::count(); echo 'Discussions: ' . App\Models\Discussion::count(); echo 'Tournaments: ' . App\Models\Tournament::count(); echo 'Conversations: ' . App\Models\Conversation::count();"

echo "🧹 Clearing existing data..."
php artisan tinker --execute="DB::statement('SET FOREIGN_KEY_CHECKS=0'); DB::table('typing_indicators')->truncate(); DB::table('messages')->truncate(); DB::table('conversation_participants')->truncate(); DB::table('conversations')->truncate(); DB::table('tournament_matches')->truncate(); DB::table('tournament_brackets')->truncate(); DB::table('tournaments')->truncate(); DB::table('likes')->truncate(); DB::table('comments')->truncate(); DB::table('discussions')->truncate(); DB::table('game_event_participants')->truncate(); DB::table('game_events')->truncate(); DB::table('user_preferred_facilities')->truncate(); DB::table('user_skill_levels')->truncate(); DB::table('games')->truncate(); DB::table('users')->truncate(); DB::table('communities')->truncate(); DB::table('game_types')->truncate(); DB::statement('SET FOREIGN_KEY_CHECKS=1');"

echo "🌱 Running comprehensive database seeding..."
php artisan db:seed --class=MasterSeeder

echo "📊 Final database status:"
php artisan tinker --execute="echo 'Users: ' . App\Models\User::count(); echo 'Game Events: ' . App\Models\GameEvent::count(); echo 'Discussions: ' . App\Models\Discussion::count(); echo 'Tournaments: ' . App\Models\Tournament::count(); echo 'Conversations: ' . App\Models\Conversation::count(); echo 'Comments: ' . App\Models\Comment::count(); echo 'Likes: ' . App\Models\Like::count(); echo 'Messages: ' . App\Models\Message::count();"

echo "✅ Local database seeding completed successfully!"
echo ""
echo "🔑 Test User Credentials:"
echo "   Email: test@example.com"
echo "   Password: password123"
echo ""
echo "📊 Data Summary:"
echo "   - 22 Users (including test user)"
echo "   - 15 Game Types"
echo "   - 5 Communities"
echo "   - 50 Game Events"
echo "   - 40 Discussions"
echo "   - 5 Tournaments"
echo "   - 20 Conversations"
echo "   - Comprehensive relationships and interactions"
echo ""
echo "🌐 You can now test the application at: http://localhost:8080"
echo "🔑 Login with: test@example.com / password123"


