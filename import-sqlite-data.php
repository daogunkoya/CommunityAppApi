<?php

echo "📥 Importing data to production SQLite database...\n";

// Read the exported data
$jsonData = file_get_contents('local-data-export.json');
$importData = json_decode($jsonData, true);

if (!$importData) {
    echo "❌ Failed to read export data\n";
    exit(1);
}

// Connect to production database
$dbPath = 'database/database.sqlite';
$pdo = new PDO("sqlite:$dbPath");

// Import data table by table
foreach ($importData as $table => $records) {
    if (empty($records)) {
        echo "⏭️  Skipping {$table}: no records\n";
        continue;
    }

    try {
        // Insert new data
        foreach ($records as $record) {
            // Remove id to let database auto-increment
            unset($record['id']);

            $columns = implode(', ', array_keys($record));
            $placeholders = ':' . implode(', :', array_keys($record));
            $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($record);
        }

        echo "✅ Imported {$table}: " . count($records) . " records\n";
    } catch (Exception $e) {
        echo "❌ Failed to import {$table}: " . $e->getMessage() . "\n";
    }
}

echo "✅ Data import completed!\n";
echo "📊 Total tables imported: " . count($importData) . "\n";
