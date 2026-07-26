<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();
$stmt = $db->query("DESCRIBE club_admins");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
