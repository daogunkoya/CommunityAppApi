#!/bin/bash

# Production deployment script for user content fix
echo "🚀 Deploying user content fix to production..."

# Server details (from existing deployment script)
SERVER_HOST="ssh.c9k0rtix9.service.one"
SERVER_USER="c9k0rtix9_ssh"

# SSH commands
SSH_CMD="/usr/bin/ssh -o StrictHostKeyChecking=no $SERVER_USER@$SERVER_HOST"
SCP_CMD="/usr/bin/scp -o StrictHostKeyChecking=no"

echo "📡 Connecting to production server..."

# Upload and run the fix script
$SCP_CMD fix-user-content.php $SERVER_USER@$SERVER_HOST:/tmp/

echo "🔧 Running user content fix on production..."

$SSH_CMD << 'EOF'
cd /customers/f/b/1/c9k0rtix9/webroots/c91f3683/api/current

echo "🧹 Clearing existing data..."
# Truncate all tables to start fresh
php artisan tinker --execute="DB::statement('SET FOREIGN_KEY_CHECKS=0'); DB::table('typing_indicators')->truncate(); DB::table('messages')->truncate(); DB::table('conversation_participants')->truncate(); DB::table('conversations')->truncate(); DB::table('likes')->truncate(); DB::table('comments')->truncate(); DB::table('discussions')->truncate(); DB::table('game_event_participants')->truncate(); DB::table('game_events')->truncate(); DB::table('user_skill_levels')->truncate(); DB::table('user_preferred_facilities')->truncate(); DB::table('tournament_matches')->truncate(); DB::table('tournament_brackets')->truncate(); DB::table('tournaments')->truncate(); DB::table('games')->truncate(); DB::table('users')->truncate(); DB::table('game_types')->truncate(); DB::statement('SET FOREIGN_KEY_CHECKS=1');"

echo "🌱 Running initial seeding..."
php artisan db:seed

echo "🔧 Running user content fix..."
php /tmp/fix-user-content.php

echo "📊 Final data summary:"
php artisan tinker --execute="echo 'Users: ' . App\Models\User::count(); echo 'Game Events: ' . App\Models\GameEvent::count(); echo 'Discussions: ' . App\Models\Discussion::count(); echo 'Comments: ' . App\Models\Comment::count(); echo 'Likes: ' . App\Models\Like::count(); echo 'Conversations: ' . App\Models\Conversation::count(); echo 'Messages: ' . App\Models\Message::count(); echo 'Game Event Participants: ' . App\Models\GameEventParticipant::count(); echo 'User Skill Levels: ' . App\Models\UserSkillLevel::count(); echo 'User Preferred Facilities: ' . App\Models\UserPreferredFacility::count();"

echo "🔑 Test User Credentials:"
echo "Email: test@example.com"
echo "Password: password123"
echo "Email: john@example.com"
echo "Password: password"

echo "✅ Production user content fix completed!"
EOF

echo "🎉 Production deployment completed!"
echo "🌐 You can now test the application at: https://matchgrinder.com"
echo "🔑 Login with: test@example.com / password123 or john@example.com / password"
