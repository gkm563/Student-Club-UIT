<?php
/**
 * RESTful API Endpoint for Student Leaders Roster (ClubHub UIT)
 * Fetches top active student club leaders from the leadership database table.
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    $stmt = $db->query("
        SELECT l.*, c.name as club_name, c.short_name as club_short_name, c.slug as club_slug
        FROM leadership l
        JOIN clubs c ON l.club_id = c.id
        ORDER BY l.order_index ASC, l.name ASC
    ");
    $leaders = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'total' => count($leaders),
        'data' => $leaders
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
