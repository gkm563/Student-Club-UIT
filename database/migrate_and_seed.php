<?php
/**
 * Automated Migration & Seeder Script for CCMS Database (MySQL & SQLite)
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    echo "===========================================\n";
    echo "  Migrating & Seeding Database ($driver)   \n";
    echo "===========================================\n";

    if ($driver === 'mysql') {
        // Drop existing tables cleanly to re-apply schema with all updated columns
        $db->exec("SET FOREIGN_KEY_CHECKS = 0;");
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($tables as $tbl) {
            $db->exec("DROP TABLE IF EXISTS `$tbl`");
        }
        $db->exec("SET FOREIGN_KEY_CHECKS = 1;");
        echo "[+] Reset old MySQL tables.\n";

        // Execute updated schema.sql
        $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
        $db->exec($schemaSql);
        echo "[+] MySQL Schema executed successfully.\n";
    }

    // Now execute all seeder files sequentially
    require_once __DIR__ . '/seed_all_wings.php';
    require_once __DIR__ . '/seed_official_clubs.php';
    require_once __DIR__ . '/seed_gemini_builders_club.php';
    require_once __DIR__ . '/update_gdgoc_real_data.php';
    require_once __DIR__ . '/update_gfg_real_data.php';
    require_once __DIR__ . '/update_gdgoc_detailed_events.php';
    require_once __DIR__ . '/seed_dean_governance_data.php';

    echo "===========================================\n";
    echo "  Database migration & seeding COMPLETE!   \n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "[-] Error: " . $e->getMessage() . "\n";
    exit(1);
}
