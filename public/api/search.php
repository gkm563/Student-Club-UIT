<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

try {
    $db = Database::getConnection();
    $searchTerm = "%$query%";
    $stmt = $db->prepare("
        SELECT c.id, c.name, c.short_name, c.slug, c.tagline, cat.name AS category_name
        FROM clubs c
        JOIN categories cat ON c.category_id = cat.id
        WHERE c.deleted_at IS NULL
          AND (c.name LIKE ? OR c.short_name LIKE ? OR c.tagline LIKE ? OR cat.name LIKE ?)
        LIMIT 8
    ");
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    $results = $stmt->fetchAll();

    echo json_encode(['results' => $results]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error', 'results' => []]);
}
