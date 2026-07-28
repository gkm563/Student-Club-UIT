<?php
/**
 * RESTful API Endpoint for Events Data (ClubHub UIT)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getConnection();

    // Fetch Events with Organizing Club Info (Excluding Private/Hidden/Drafted/Archived events)
    $stmt = $db->query("
        SELECT e.*, c.name as club_name, c.short_name as club_short_name, c.logo as club_logo, c.id as club_id
        FROM events e
        JOIN clubs c ON e.club_id = c.id
        WHERE c.status = 'active' AND e.status NOT IN ('draft', 'hidden', 'archived')
        ORDER BY e.event_date ASC
    ");
    $allEvents = $stmt->fetchAll();

    $now = date('Y-m-d H:i:s');
    $upcoming = [];
    $past = [];

    foreach ($allEvents as $event) {
        if ($event['event_date'] >= $now) {
            $upcoming[] = $event;
        } else {
            $past[] = $event;
        }
    }

    echo json_encode([
        'status' => 'success',
        'total' => count($allEvents),
        'upcoming' => $upcoming,
        'past' => $past,
        'data' => $allEvents
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
