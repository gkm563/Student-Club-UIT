<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $stmt = $db->query("DESCRIBE events");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Current columns in events table:\n";
    print_r($columns);

    // List of columns to ensure exist
    $desiredColumns = [
        'tagline'             => "VARCHAR(255) NULL AFTER title",
        'event_type'          => "VARCHAR(100) DEFAULT 'Hands-on Workshop' AFTER tagline",
        'speaker_name'        => "VARCHAR(255) NULL",
        'speaker_designation' => "VARCHAR(255) NULL",
        'agenda_timeline'     => "TEXT NULL",
        'target_audience'     => "VARCHAR(255) DEFAULT 'All UIT Departments & Years'",
        'outcomes_summary'    => "TEXT NULL",
        'registered_count'    => "INT DEFAULT 0",
        'actual_attended'     => "INT DEFAULT 0",
        'budget_utilized'     => "DECIMAL(10,2) DEFAULT 0.00"
    ];

    foreach ($desiredColumns as $col => $definition) {
        if (!in_array($col, $columns)) {
            echo "Adding missing column '$col'...\n";
            $db->exec("ALTER TABLE events ADD COLUMN $col $definition");
            echo "Column '$col' added successfully!\n";
        }
    }

    echo "Schema update completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
