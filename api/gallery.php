<?php
/**
 * RESTful API Endpoint for Campus Gallery Media Items (ClubHub UIT)
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $db = Database::getConnection();

    $eventId = $_GET['event_id'] ?? null;
    $clubId  = $_GET['club_id'] ?? null;

    if ($eventId) {
        $stmt = $db->prepare("
            SELECT g.*, c.name as club_name, c.short_name as club_short_name
            FROM gallery_items g
            LEFT JOIN clubs c ON g.club_id = c.id
            WHERE g.event_id = ?
            ORDER BY g.created_at DESC
        ");
        $stmt->execute([$eventId]);
    } else if ($clubId) {
        $stmt = $db->prepare("
            SELECT g.*, c.name as club_name, c.short_name as club_short_name
            FROM gallery_items g
            LEFT JOIN clubs c ON g.club_id = c.id
            WHERE g.club_id = ? AND g.event_id IS NULL
            ORDER BY g.created_at DESC
        ");
        $stmt->execute([$clubId]);
    } else {
        $stmt = $db->query("
            SELECT g.*, c.name as club_name, c.short_name as club_short_name, cat.slug as category_slug, cat.name as category_name
            FROM gallery_items g
            JOIN clubs c ON g.club_id = c.id
            LEFT JOIN categories cat ON c.category_id = cat.id
            ORDER BY g.created_at DESC
        ");
    }
    $items = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'total' => count($items),
        'data' => $items
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
