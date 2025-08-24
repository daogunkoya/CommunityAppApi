<?php

// Export Production Data Script
// This script exports data from local SQLite to JSON files for production import

require_once 'vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Connect to local SQLite database
$pdo = new PDO('sqlite:database/database.sqlite');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "🔄 Exporting data from local database...\n";

// Export game_types
echo "📊 Exporting game_types...\n";
$gameTypes = $pdo->query("SELECT * FROM game_types")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('production-data/game_types.json', json_encode($gameTypes, JSON_PRETTY_PRINT));

// Export users (excluding sensitive data)
echo "👥 Exporting users...\n";
$users = $pdo->query("SELECT id, first_name, last_name, email, date_of_birth, gender, location, latitude, longitude, radius, main_goal, auth_provider, auth_provider_id, profile_picture, is_active, created_at, updated_at FROM users")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('production-data/users.json', json_encode($users, JSON_PRETTY_PRINT));

// Export user_skill_levels
echo "🎯 Exporting user_skill_levels...\n";
$skillLevels = $pdo->query("SELECT * FROM user_skill_levels")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('production-data/user_skill_levels.json', json_encode($skillLevels, JSON_PRETTY_PRINT));

// Export communities
echo "🏘️ Exporting communities...\n";
$communities = $pdo->query("SELECT * FROM communities")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('production-data/communities.json', json_encode($communities, JSON_PRETTY_PRINT));

// Export user_communities
echo "👥 Exporting user_communities...\n";
$userCommunities = $pdo->query("SELECT * FROM user_communities")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('production-data/user_communities.json', json_encode($userCommunities, JSON_PRETTY_PRINT));

// Export games
echo "🎮 Exporting games...\n";
$games = $pdo->query("SELECT * FROM games")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('production-data/games.json', json_encode($games, JSON_PRETTY_PRINT));

// Export discussions
echo "💬 Exporting discussions...\n";
$discussions = $pdo->query("SELECT * FROM discussions")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('production-data/discussions.json', json_encode($discussions, JSON_PRETTY_PRINT));

// Export tournaments
echo "🏆 Exporting tournaments...\n";
$tournaments = $pdo->query("SELECT * FROM tournaments")->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('production-data/tournaments.json', json_encode($tournaments, JSON_PRETTY_PRINT));

echo "✅ Data export completed!\n";
echo "📁 Check the 'production-data' directory for exported JSON files.\n";
