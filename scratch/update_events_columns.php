<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $db->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS tagline VARCHAR(255) NULL AFTER title");
    $db->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS event_type VARCHAR(100) DEFAULT 'Workshop' AFTER status");
    $db->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS speaker_name VARCHAR(191) NULL AFTER Outcomes_summary");
    $db->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS speaker_designation VARCHAR(191) NULL AFTER speaker_name");
    $db->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS agenda_timeline TEXT NULL AFTER speaker_designation");
    $db->exec("ALTER TABLE events ADD COLUMN IF NOT EXISTS target_audience VARCHAR(255) DEFAULT 'All Departments & Years' AFTER agenda_timeline");
    echo "Successfully updated events table schema with advanced detailed fields!\n";
} catch (Exception $e) {
    echo "Error updating events schema: " . $e->getMessage() . "\n";
}
