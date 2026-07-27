<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();

try {
    $db->exec("ALTER TABLE gallery_items ADD COLUMN event_id VARCHAR(64) NULL AFTER club_id");
    echo "Successfully added event_id column to gallery_items\n";
} catch (Exception $e) {
    echo "Column already exists or error: " . $e->getMessage() . "\n";
}

// Update GDGOC inaugural event photos
$stmt = $db->prepare("
    UPDATE gallery_items
    SET event_id = 'evt_gdgoc_tfug_inaugural_2024'
    WHERE club_id = 'clb_gdgoc_uit_2026' OR caption LIKE '%TFUG%'
");
$stmt->execute();
echo "Updated " . $stmt->rowCount() . " gallery items for event_id evt_gdgoc_tfug_inaugural_2024\n";
