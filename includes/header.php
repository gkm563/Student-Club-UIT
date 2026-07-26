<?php
/**
 * Universal Header Component (ClubHub UIT)
 * Provides HTML <head> with Bootstrap 5 & Custom Design System CSS.
 */
if (!isset($assetPrefix)) {
    $assetPrefix = '';
}
if (!isset($pageTitle)) {
    $pageTitle = 'ClubHub UIT | Official Campus Club Portal';
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom Vibrant Design System -->
    <link rel="stylesheet" href="<?= $assetPrefix ?>assets/css/style.css">
</head>
<body>

<?php
include __DIR__ . '/header.html';
?>
