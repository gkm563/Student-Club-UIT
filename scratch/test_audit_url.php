<?php
$urls = [
    'http://172.16.12.8/uit/admin/super/audit-logs.php',
    'http://172.16.12.8/uit/admin/super/logs.php',
    'http://172.16.12.8/uit/admin/super/index.php'
];

foreach ($urls as $url) {
    $headers = @get_headers($url);
    $status = $headers ? $headers[0] : 'FAILED TO CONNECT';
    echo "$url => $status\n";
}
