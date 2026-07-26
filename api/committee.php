<?php
/**
 * RESTful API Endpoint for Management Committee & Institutional Leadership (ClubHub UIT)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    $stmt = $db->query("
        SELECT id, name, designation, role_title, photo, bio, order_index
        FROM management_committee
        ORDER BY order_index ASC
    ");
    $committee = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'data' => $committee
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
