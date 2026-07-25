<?php
/**
 * Keep ONLY GDGoC UIT and GFG SC UIT Clubs in Database
 * Removes all other extra/dummy clubs, events, leadership, and gallery items.
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = Database::getConnection();
    echo "===========================================\n";
    echo "  Cleaning Database: Keeping ONLY GDGoC & GFG SC UIT \n";
    echo "===========================================\n";

    $allowedSlugs = ['gdgoc-uit', 'gfgsc-uit'];
    $allowedClubIds = ['clb_gdgoc_uit_2026', 'clb_gfg_sc_uit_2026'];

    // 1. Delete events not in allowed clubs
    $inClause = "'" . implode("','", $allowedClubIds) . "'";
    $deletedEvents = $db->exec("DELETE FROM events WHERE club_id NOT IN ($inClause)");
    echo "[+] Removed $deletedEvents events from non-active clubs.\n";

    // 2. Delete leadership entries not in allowed clubs
    $deletedLeaders = $db->exec("DELETE FROM leadership WHERE club_id NOT IN ($inClause)");
    echo "[+] Removed $deletedLeaders leadership entries from non-active clubs.\n";

    // 3. Delete gallery items not in allowed clubs
    $deletedGallery = $db->exec("DELETE FROM gallery_items WHERE club_id NOT IN ($inClause)");
    echo "[+] Removed $deletedGallery gallery items from non-active clubs.\n";

    // 4. Delete clubs not in allowed list
    $deletedClubs = $db->exec("DELETE FROM clubs WHERE id NOT IN ($inClause)");
    echo "[+] Removed $deletedClubs clubs from database.\n";

    // Re-verify remaining counts
    $clubsCount = (int)$db->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
    $eventsCount = (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn();
    $leadersCount = (int)$db->query("SELECT COUNT(*) FROM leadership")->fetchColumn();
    $galleryCount = (int)$db->query("SELECT COUNT(*) FROM gallery_items")->fetchColumn();

    echo "-------------------------------------------\n";
    echo "  Remaining Live Records:\n";
    echo "  - Active Clubs: $clubsCount (GDGOC UIT & GFG SC UIT)\n";
    echo "  - Total Events: $eventsCount\n";
    echo "  - Leadership Roster: $leadersCount\n";
    echo "  - Gallery Items: $galleryCount\n";
    echo "===========================================\n";

} catch (Exception $e) {
    echo "[-] Error cleaning database: " . $e->getMessage() . "\n";
}
