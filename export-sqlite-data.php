<?php

echo "📤 Exporting data from local SQLite database...\n";

$dbPath = 'database/database.sqlite';
$pdo = new PDO("sqlite:$dbPath");

// Tables to export
$tables = [
    'users',
    'game_types',
    'communities',
    'games',
    'discussions',
    'messages',
    'conversations',
    'tournaments',
    'user_skill_levels',
    'user_communities',
    'likes',
    'comments'
];

$exportData = [];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT * FROM $table");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $exportData[$table] = $data;
        echo "✅ Exported {$table}: " . count($data) . " records\n";
    } catch (Exception $e) {
        echo "⚠️  Skipped {$table}: " . $e->getMessage() . "\n";
    }
}

// Save to JSON file
$jsonData = json_encode($exportData, JSON_PRETTY_PRINT);
file_put_contents('local-data-export.json', $jsonData);

echo "✅ Data export completed!\n";
echo "📁 Exported to: local-data-export.json\n";
echo "📊 Total tables exported: " . count($exportData) . "\n";
