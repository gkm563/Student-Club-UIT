<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    // Add event_id column if it doesn't exist
    $db->exec("ALTER TABLE gallery_items ADD COLUMN IF NOT EXISTS event_id VARCHAR(36) NULL AFTER club_id");
    echo "Column event_id checked/added successfully in gallery_items table.\n";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
