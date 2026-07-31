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
            <img src="<?= $assetPrefix ?>assets/United Logo.webp" alt="United Group Logo" class="brand-logo-img">
            <div class="d-flex flex-column">
                <span class="brand-logo-text" style="font-size: 1.25rem; font-weight: 900; line-height: 1.1; color: #0f172a;">USC UIT</span>
                <span class="fw-extrabold text-primary" style="font-size: 0.68rem; letter-spacing: 0.5px;">UNITED STUDENT CLUB</span>
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
                        Clubs &amp; Wings
                    </a>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-4 p-2 mt-2" aria-labelledby="nav-clubs">
                        <li>
                            <a class="dropdown-item rounded-3 py-2 px-3 fw-bold d-flex align-items-center gap-2" href="<?= $assetPrefix ?>clubs.html">
                                <i class="bi bi-grid-fill text-primary"></i> All USC UIT Clubs
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li class="dropdown-header small text-uppercase fw-bold text-muted px-3 py-1">The 2 Main Wings</li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2.5 px-3 fw-bold text-primary d-flex align-items-center gap-2.5" href="<?= $assetPrefix ?>developers-club.html">
                                <i class="bi bi-code-slash fs-5 text-primary"></i>
                                <div>
                                    <span>Developers Club UIT</span>
                                    <small class="text-muted fw-semibold d-block" style="font-size:0.72rem;">Official Technical Umbrella Council</small>
                                </div>
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2.5 px-3 fw-bold text-danger d-flex align-items-center gap-2.5" href="<?= $assetPrefix ?>cultural-club.html">
                                <i class="bi bi-palette-fill fs-5 text-danger"></i>
                                <div>
                                    <span>Cultural Club UIT</span>
                                    <small class="text-muted fw-semibold d-block" style="font-size:0.72rem;">Official Cultural Umbrella Council</small>
                                </div>
                            </a>
                        </li>
                    </ul>
                </li>

                <!-- Events Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link nav-link-clubhub dropdown-toggle" href="<?= $assetPrefix ?>events.html" id="nav-events" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Events
                    </a>
                    <ul class="dropdown-menu border-0 shadow-lg rounded-4 p-2 mt-2" aria-labelledby="nav-events">
                        <li>
                            <a class="dropdown-item rounded-3 py-2 px-3 fw-bold d-flex align-items-center gap-2" href="<?= $assetPrefix ?>events.html">
                                <i class="bi bi-calendar-week text-primary"></i> All Campus Events
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 px-3 fw-bold text-primary d-flex align-items-center gap-2" href="<?= $assetPrefix ?>tech-events.html">
                                <i class="bi bi-cpu text-primary"></i> Developers Club Events
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded-3 py-2 px-3 fw-bold text-danger d-flex align-items-center gap-2" href="<?= $assetPrefix ?>cultural-events.html">
                                <i class="bi bi-music-note-beamed text-danger"></i> Cultural Club Events
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>gallery.html" id="nav-gallery">Gallery</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>about.html" id="nav-about">About Us</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub" href="<?= $assetPrefix ?>contact.html" id="nav-contact">Contact</a></li>
            </ul>

            <!-- 2 Main Wings Action Buttons (Always Visible & Responsive) -->
            <div class="d-flex align-items-center gap-2 flex-wrap mt-3 mt-lg-0">
                <a href="<?= $assetPrefix ?>developers-club.html" class="btn btn-sm btn-wing-dev rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1.5 shadow-sm" title="Developers Club UIT — Official Technical Council">
                    <i class="bi bi-code-slash fs-6 text-primary"></i> <span>Developers</span>
                </a>
                <a href="<?= $assetPrefix ?>cultural-club.html" class="btn btn-sm btn-wing-cult rounded-pill px-3 py-2 d-inline-flex align-items-center gap-1.5 shadow-sm" title="Cultural Club UIT — Official Cultural Council">
                    <i class="bi bi-palette-fill fs-6 text-danger"></i> <span>Cultural</span>
                </a>
                <a href="<?= $assetPrefix ?>clubs.html" class="btn btn-sm btn-primary rounded-pill px-3 py-2 fw-bold text-white shadow-sm d-inline-flex align-items-center gap-1">
                    <i class="bi bi-compass"></i> <span>Explore Clubs</span>
                </a>
            </div>
        </div>
    </div>
</nav>

