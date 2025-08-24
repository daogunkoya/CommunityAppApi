<?php

echo "📥 Importing data to production database...\n";

// Load Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Read the exported data
$jsonData = file_get_contents('local-data-export.json');
$importData = json_decode($jsonData, true);

if (!$importData) {
    echo "❌ Failed to read export data\n";
    exit(1);
}

// Import data table by table
foreach ($importData as $table => $records) {
    if (empty($records)) {
        echo "⏭️  Skipping {$table}: no records\n";
        continue;
    }

    try {
        // Clear existing data (optional - comment out if you want to keep existing)
        // DB::table($table)->truncate();

        // Insert new data
        foreach ($records as $record) {
            // Remove id to let database auto-increment
            unset($record['id']);
            DB::table($table)->insert($record);
        }

        echo "✅ Imported {$table}: " . count($records) . " records\n";
    } catch (Exception $e) {
        echo "❌ Failed to import {$table}: " . $e->getMessage() . "\n";
    }
}

echo "✅ Data import completed!\n";
echo "📊 Total tables imported: " . count($importData) . "\n";
