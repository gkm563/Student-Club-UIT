<?php
/**
 * CCMS Development Router
 */

$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve static assets from public/assets
$staticFile = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($staticFile) && !is_dir($staticFile) && str_contains($uri, '/assets/')) {
    $ext = pathinfo($staticFile, PATHINFO_EXTENSION);
    $mimes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'svg' => 'image/svg+xml',
        'html' => 'text/html'
    ];
    header("Content-Type: " . ($mimes[$ext] ?? 'text/plain'));
    readfile($staticFile);
    exit;
}

// Home page landing routes to index.html
if ($uri === '/' || $uri === '/index.html') {
    header("Content-Type: text/html; charset=UTF-8");
    readfile(__DIR__ . '/public/index.html');
    exit;
}

// Clean URL routing: /clubs/geeksforgeeks
if (preg_match('#^/clubs/([a-zA-Z0-9\-]+)/?$#', $uri, $m)) {
    $_GET['slug'] = $m[1];
    require __DIR__ . '/public/club-detail.php';
    exit;
}

// Dedicated Club Lead Login Route
if ($uri === '/club-login' || $uri === '/club-login.php') {
    require __DIR__ . '/club-login.php';
    exit;
}

// Admin routes
if (str_starts_with($uri, '/admin/')) {
    $target = __DIR__ . $uri;
    if (file_exists($target) && !is_dir($target)) {
        require $target;
        exit;
    }
}

// API routes
if (str_starts_with($uri, '/api/')) {
    $target = __DIR__ . '/public' . $uri;
    if (file_exists($target)) {
        require $target;
        exit;
    }
}

// Public PHP pages
$target = __DIR__ . '/public' . $uri;
if (file_exists($target) && !is_dir($target)) {
    require $target;
    exit;
}

// Default fallback to index.html
header("Content-Type: text/html; charset=UTF-8");
readfile(__DIR__ . '/public/index.html');
