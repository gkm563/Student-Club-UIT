<?php
require_once __DIR__ . '/../config/database.php';

function update_gfg_members(PDO $db, string $dbType) {
    // Check if member_count column exists
    try { $db->exec("ALTER TABLE clubs ADD COLUMN member_count INTEGER DEFAULT 600"); } catch (Exception $ex) {}
    
    $stmt = $db->prepare("UPDATE clubs SET member_count = 600 WHERE id = 'clb_gfg_sc_uit_2026'");
    $stmt->execute();
    echo "[✓] Updated GFG SC UIT member count to 600+ in {$dbType}\n";
}

try {
    $mainDb = Database::getConnection();
    update_gfg_members($mainDb, "Main Database");
} catch (Exception $e) {
    echo "[X] Main DB Error: " . $e->getMessage() . "\n";
}

$sqliteFile = __DIR__ . '/ccms.sqlite';
if (file_exists($sqliteFile)) {
    try {
        $sqliteDb = new PDO("sqlite:" . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        update_gfg_members($sqliteDb, "SQLite File");
    } catch (Exception $e) {
        echo "[X] SQLite Error: " . $e->getMessage() . "\n";
    }
}
