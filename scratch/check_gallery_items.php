<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();
$stmt = $db->query("SELECT * FROM gallery_items");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($items, JSON_PRETTY_PRINT);
