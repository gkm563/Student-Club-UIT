<?php
/**
 * RESTful API Endpoint for Live Community Statistics (ClubHub UIT)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getConnection();

    // Real DB Counts
    $clubsCount = (int)$db->query("SELECT COUNT(*) FROM clubs WHERE status != 'suspended'")->fetchColumn();
    $leadersCount = (int)$db->query("SELECT COUNT(*) FROM leadership")->fetchColumn();
    $eventsCount = (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $activitiesCount = (int)$db->query("SELECT COUNT(*) FROM events WHERE status = 'completed'")->fetchColumn();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'clubs' => $clubsCount,
            'members' => $leadersCount,
            'events' => $eventsCount,
            'activities' => $activitiesCount
        ]
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
