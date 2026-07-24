<?php
/**
 * RESTful API Endpoint for Events Data (ClubHub UIT)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../config/database.php';

try {
    $db = Database::getConnection();

    // Fetch Events with Organizing Club Info
    $stmt = $db->query("
        SELECT e.*, c.name as club_name, c.short_name as club_short_name, c.logo as club_logo, c.id as club_id
        FROM events e
        JOIN clubs c ON e.club_id = c.id
        WHERE c.status != 'suspended'
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
