<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();
$stmt = $db->query("SELECT id, club_id, title, status, event_date FROM events WHERE club_id = 'clb_gdgoc_uit_2026'");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rows);
