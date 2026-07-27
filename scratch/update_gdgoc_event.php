<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();

// 1. Update event details
$stmt = $db->prepare("
    UPDATE events
    SET venue = 'United Institute Of Technology, NH 2, Naini, Prayagraj 211010',
        event_date = '2024-10-01 14:00:00',
        registered_count = 45,
        actual_attended = 42,
        status = 'completed'
    WHERE id = 'evt_gdgoc_tfug_inaugural_2024'
");
$stmt->execute();
echo "Updated event evt_gdgoc_tfug_inaugural_2024\n";

// 2. Update gallery items to link to event_id
$stmt2 = $db->prepare("
    UPDATE gallery_items
    SET event_id = 'evt_gdgoc_tfug_inaugural_2024'
    WHERE club_id = 'clb_gdgoc_uit_2026' OR caption LIKE '%TFUG%'
");
$stmt2->execute();
echo "Updated " . $stmt2->rowCount() . " gallery items for event_id evt_gdgoc_tfug_inaugural_2024\n";
