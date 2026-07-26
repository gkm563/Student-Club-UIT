<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $stmt = $db->query("DESCRIBE events");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Current columns in events table:\n";
    print_r($columns);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
