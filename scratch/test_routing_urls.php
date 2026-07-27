<?php
$urls = [
    'http://172.16.12.8/uit/index.html',
    'http://172.16.12.8/uit/clubs.html',
    'http://172.16.12.8/uit/clubs.html?category=technical',
    'http://172.16.12.8/uit/clubs.html?category=cultural',
    'http://172.16.12.8/uit/events.html'
];

foreach ($urls as $url) {
    $headers = @get_headers($url);
    $status = $headers ? $headers[0] : 'FAILED TO CONNECT';
    echo "$url => $status\n";
}
