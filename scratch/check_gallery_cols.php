<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();
$cols = $db->query("DESCRIBE gallery_items")->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_PRETTY_PRINT);
