<?php
$url = 'http://172.16.12.8/uit/event-detail.html?id=evt_gdgoc_tfug_inaugural_2024';
$content = file_get_contents($url);

echo "Checking Event Detail HTML Render:\n";
echo "completedGallerySection full width present: " . (str_contains($content, 'id="completedGallerySection"') ? "YES" : "NO") . "\n";
echo "eventGalleryModal fullscreen present: " . (str_contains($content, 'id="eventGalleryModal"') ? "YES" : "NO") . "\n";
echo "lightboxPrevBtn present: " . (str_contains($content, 'id="lightboxPrevBtn"') ? "YES" : "NO") . "\n";
echo "lightboxNextBtn present: " . (str_contains($content, 'id="lightboxNextBtn"') ? "YES" : "NO") . "\n";
