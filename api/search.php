<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    $db = Database::getConnection();
    $searchTerm = "%$query%";

    // 1. Search Clubs (up to 5)
    $stmtClubs = $db->prepare("
        SELECT 
            c.id, 
            c.name, 
            c.short_name, 
            c.slug, 
            c.tagline, 
            c.logo,
            cat.name AS category_name, 
            'club' AS item_type,
            CONCAT('club-detail.html?id=', c.id) AS url
        FROM clubs c
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE (c.deleted_at IS NULL OR c.deleted_at = '') 
          AND (c.status IS NULL OR c.status = 'active')
          AND (c.name LIKE ? OR c.short_name LIKE ? OR c.tagline LIKE ? OR c.description LIKE ? OR cat.name LIKE ?)
        ORDER BY c.name ASC
        LIMIT 5
    ");
    $stmtClubs->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $clubs = $stmtClubs->fetchAll(PDO::FETCH_ASSOC);

    // 2. Search Events (up to 5)
    $stmtEvents = $db->prepare("
        SELECT 
            e.id, 
            e.title AS name, 
            COALESCE(c.short_name, 'Event') AS short_name, 
            e.slug, 
            e.venue AS tagline, 
            c.logo,
            COALESCE(cat.name, 'Event') AS category_name, 
            'event' AS item_type,
            CONCAT('event-detail.html?id=', e.id) AS url
        FROM events e
        LEFT JOIN clubs c ON e.club_id = c.id
        LEFT JOIN categories cat ON c.category_id = cat.id
        WHERE (e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ? OR c.name LIKE ?)
        ORDER BY e.event_date DESC
        LIMIT 5
    ");
    $stmtEvents->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $events = $stmtEvents->fetchAll(PDO::FETCH_ASSOC);

    $results = array_merge($clubs, $events);

    echo json_encode(['results' => $results, 'status' => 'success']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage(), 'results' => []]);
}
