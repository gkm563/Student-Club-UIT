<?php
/**
 * Reset Events Script - ClubHub UIT
 * Deletes all events from database while preserving all official campus clubs.
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    
    // Clear events table
    $db->exec("DELETE FROM events");
    
    // Check remaining event count
    $stmt = $db->query("SELECT COUNT(*) FROM events");
    $count = $stmt->fetchColumn();
    
    // Check remaining clubs count
    $stmtClubs = $db->query("SELECT COUNT(*) FROM clubs");
    $clubsCount = $stmtClubs->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'message' => 'All events deleted successfully. Ready for real campus event entries.',
        'total_events' => (int)$count,
        'total_clubs' => (int)$clubsCount
    ], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
