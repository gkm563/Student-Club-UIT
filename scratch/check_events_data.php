<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();
$stmt = $db->query("SELECT id, title, slug, event_date, registered_count, status FROM events");
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($events, JSON_PRETTY_PRINT);
