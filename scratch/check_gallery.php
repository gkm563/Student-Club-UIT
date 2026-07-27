<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();
$cols = $db->query('DESCRIBE gallery_items')->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $c) echo $c['Field'] . ' - ' . $c['Type'] . "\n";
echo "\n\n--- Sample rows ---\n";
$rows = $db->query('SELECT id, club_id, media_url, caption, created_at FROM gallery_items LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) print_r($r);
