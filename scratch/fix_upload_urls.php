<?php
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();

// Fix old upload URLs that are missing /UIT/ prefix
$rows = $db->query("SELECT id, media_url FROM gallery_items WHERE media_url LIKE '/uploads/%'")->fetchAll(PDO::FETCH_ASSOC);
$fixed = 0;
foreach ($rows as $row) {
    $newUrl = '/UIT' . $row['media_url'];
    $db->prepare("UPDATE gallery_items SET media_url = ? WHERE id = ?")->execute([$newUrl, $row['id']]);
    echo "Fixed: " . $row['media_url'] . " → " . $newUrl . "\n";
    $fixed++;
}

// Also fix events table banner URLs
$evRows = $db->query("SELECT id, banner FROM events WHERE banner LIKE '/uploads/%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($evRows as $row) {
    $newUrl = '/UIT' . $row['banner'];
    $db->prepare("UPDATE events SET banner = ? WHERE id = ?")->execute([$newUrl, $row['id']]);
    echo "Fixed event banner: " . $row['banner'] . " → " . $newUrl . "\n";
    $fixed++;
}

// Also fix clubs table logo/cover_image URLs
$clubRows = $db->query("SELECT id, logo, cover_image FROM clubs WHERE logo LIKE '/uploads/%' OR cover_image LIKE '/uploads/%'")->fetchAll(PDO::FETCH_ASSOC);
foreach ($clubRows as $row) {
    if (!empty($row['logo']) && str_starts_with($row['logo'], '/uploads/')) {
        $newUrl = '/UIT' . $row['logo'];
        $db->prepare("UPDATE clubs SET logo = ? WHERE id = ?")->execute([$newUrl, $row['id']]);
        echo "Fixed club logo: " . $row['logo'] . " → " . $newUrl . "\n"; $fixed++;
    }
    if (!empty($row['cover_image']) && str_starts_with($row['cover_image'], '/uploads/')) {
        $newUrl = '/UIT' . $row['cover_image'];
        $db->prepare("UPDATE clubs SET cover_image = ? WHERE id = ?")->execute([$newUrl, $row['id']]);
        echo "Fixed club cover: " . $row['cover_image'] . " → " . $newUrl . "\n"; $fixed++;
    }
}

echo "\n✅ Total fixed: $fixed records\n";
