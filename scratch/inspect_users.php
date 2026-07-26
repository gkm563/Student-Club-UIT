<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $stmt = $db->query("
        SELECT u.id, u.full_name, u.email, u.role, u.status, c.name as club_name 
        FROM users u 
        LEFT JOIN club_admins ca ON ca.user_id = u.id 
        LEFT JOIN clubs c ON c.id = ca.club_id 
        ORDER BY u.role DESC, u.id ASC
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($users, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
