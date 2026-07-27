<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();
echo "Total Proposals in DB: " . $db->query("SELECT COUNT(*) FROM club_proposals")->fetchColumn() . "\n";
