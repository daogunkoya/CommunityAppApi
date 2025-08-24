<?php

echo "📥 Importing data to production MySQL database...\n";

// Read the exported data
$jsonData = file_get_contents('local-data-export.json');
$importData = json_decode($jsonData, true);

if (!$importData) {
    echo "❌ Failed to read export data\n";
    exit(1);
}

// Connect to MySQL database
$host = 'mysql.c9k0rtix9.service.one';
$dbname = 'c9k0rtix9_matchgrinder';
$username = 'c9k0rtix9_matchgrinder';
$password = 'matchgrinder';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Connected to MySQL database\n";
} catch (PDOException $e) {
    echo "❌ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

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
