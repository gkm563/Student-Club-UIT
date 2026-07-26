<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $stmt = $db->query("
        SELECT u.id, u.email, u.full_name, u.role, c.name as club_name, c.short_name
        FROM users u
        LEFT JOIN club_admins ca ON ca.user_id = u.id
        LEFT JOIN clubs c ON ca.club_id = c.id
        ORDER BY u.role, c.name
    ");
    $users = $stmt->fetchAll();
    echo json_encode($users, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
