<?php
$url = 'http://172.16.12.8/uit/api/gallery.php?event_id=evt_gdgoc_tfug_inaugural_2024';
$res = file_get_contents($url);
echo "Gallery API Response for evt_gdgoc_tfug_inaugural_2024:\n";
echo $res . "\n";
