<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    
    // TRUNCATE or DELETE dummy proposals from club_proposals table
    $db->exec("TRUNCATE TABLE club_proposals");
    
    echo "Successfully cleared all dummy proposal data from 'club_proposals' table!\n";
} catch (Exception $e) {
    echo "Error clearing table: " . $e->getMessage() . "\n";
}
