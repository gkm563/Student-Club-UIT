<?php
/**
 * Universal Header Component (ClubHub UIT)
 * Provides HTML <head> with Bootstrap 5 & Custom Design System CSS.
 */
if (!isset($assetPrefix) || empty($assetPrefix)) {
    if (str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') || str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/club/')) {
        $assetPrefix = '../';
    } else {
        $assetPrefix = '';
    }
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

<!-- Vibrant Universal Top Navbar (ClubHub UIT) -->
<nav class="navbar navbar-expand-lg navbar-clubhub sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="<?= $assetPrefix ?>index.html">
            <img src="<?= $assetPrefix ?>assets/United Logo.webp" alt="United Group Logo" class="brand-logo-img">
            <span class="brand-logo-text">ClubHub UIT</span>
        </a>

        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarClubhub" aria-controls="navbarClubhub" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarClubhub">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>index.html" id="nav-home">Home</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>clubs.html" id="nav-clubs">Clubs</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>events.html" id="nav-events">Events</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>gallery.html" id="nav-gallery">Gallery</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>about.html" id="nav-about">About Us</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>contact.html" id="nav-contact">Contact</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <a href="<?= $assetPrefix ?>clubs.html" class="btn btn-sm btn-light rounded-pill px-3 py-2 fw-bold text-primary shadow-sm">
                    <i class="bi bi-compass me-1"></i> Explore Clubs
                </a>
            </div>
        </div>
    </div>
</nav>
