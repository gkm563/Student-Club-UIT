<?php
/**
 * Universal Header Component (ClubHub UIT)
 * Provides HTML <head> with Bootstrap 5 & Custom Design System CSS.
 */
if (!isset($assetPrefix)) {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (preg_match('#/(admin|club)/#i', $script)) {
        $assetPrefix = '../';
    } else {
        $assetPrefix = '';
    }
}
if (!isset($pageTitle)) {
    $pageTitle = 'USC UIT | United Student Club — UIT';
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

<!-- Vibrant Universal Top Navbar (USC UIT - United Student Club) -->
<nav class="navbar navbar-expand-lg navbar-clubhub sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="<?= $assetPrefix ?>index.html">
            <img src="<?= $assetPrefix ?>assets/img/usc-logo.png" alt="United Student Club Logo" class="brand-logo-img" style="height: 44px; width: 44px; object-fit: contain;">
            <div class="d-flex flex-column">
                <span class="brand-logo-text" style="font-size: 1.25rem; font-weight: 900; line-height: 1.1; color: #0f172a;">USC UIT</span>
                <span class="fw-extrabold text-danger" style="font-size: 0.68rem; letter-spacing: 0.5px; color: #c8102e !important;">UNITED STUDENT CLUB</span>
            </div>
        </a>

        <button class="navbar-toggler border-0 text-white shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarClubhub" aria-controls="navbarClubhub" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarClubhub">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 align-items-lg-center">
                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>index.html" id="nav-home">Home</a></li>

                <!-- Clubs Dropdown — 2 Main Wings -->
                <li class="nav-item dropdown">
                    <a class="nav-link nav-link-clubhub dropdown-toggle" href="<?= $assetPrefix ?>clubs.html" id="nav-clubs" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Clubs
                    </a>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-4 p-2 mt-2" aria-labelledby="nav-clubs">
                        <li>
                            <a class="dropdown-item rounded-3 py-2 px-3 fw-bold d-flex align-items-center gap-2" href="<?= $assetPrefix ?>clubs.html">
                                <i class="bi bi-grid-fill text-primary"></i> All USC UIT Clubs
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 px-3 fw-bold d-flex align-items-center gap-2" href="<?= $assetPrefix ?>clubs.html?wing=technical">
                                <i class="bi bi-code-slash text-primary"></i> Technical Wing Clubs
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 px-3 fw-bold d-flex align-items-center gap-2" href="<?= $assetPrefix ?>clubs.html?wing=cultural">
                                <i class="bi bi-palette-fill text-danger"></i> Cultural Wing Clubs
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>events.html" id="nav-events">Events</a></li>

                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>gallery.html" id="nav-gallery">Gallery</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>about.html" id="nav-about">About Us</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>contact.html" id="nav-contact">Contact</a></li>
            </ul>

            <div class="d-flex align-items-center gap-2 flex-wrap mt-3 mt-lg-0">
                <a href="<?= $assetPrefix ?>clubs.html" class="btn btn-sm btn-primary rounded-pill px-3 py-2 fw-bold shadow-sm">
                    <i class="bi bi-grid-fill me-1"></i> All Clubs
                </a>
            </div>
        </div>
    </div>
</nav>

