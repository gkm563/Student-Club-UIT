<?php
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<nav class="navbar navbar-expand-lg navbar-ccms sticky-top">
    <div class="container">
        <a class="navbar-brand navbar-brand-ccms d-flex align-items-center gap-2" href="/index.html">
            <img src="/assets/United Logo.webp" alt="United Group Logo" class="brand-logo-img">
            <span>ClubHub <span class="badge bg-primary-subtle text-primary border rounded-pill fs-6 px-2 py-1 ms-1">UIT</span></span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0 fw-semibold">
                <li class="nav-item">
                    <a class="nav-link <?= $currentUri === '/' || $currentUri === '/index.html' || $currentUri === '/index.php' ? 'active text-primary' : '' ?>" href="/index.html">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentUri, 'clubs') ? 'active text-primary' : '' ?>" href="/clubs.php">All Clubs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentUri, 'events') ? 'active text-primary' : '' ?>" href="/events.php">Events</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentUri, 'activities') ? 'active text-primary' : '' ?>" href="/activities.php">Activities</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentUri, 'gallery') ? 'active text-primary' : '' ?>" href="/gallery.php">Gallery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentUri, 'leadership') ? 'active text-primary' : '' ?>" href="/leadership.php">Leadership</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentUri, 'about') ? 'active text-primary' : '' ?>" href="/about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= str_contains($currentUri, 'contact') ? 'active text-primary' : '' ?>" href="/contact.php">Contact</a>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <button id="themeToggleBtn" class="btn btn-sm btn-outline-secondary rounded-circle px-2 py-1" title="Toggle Light/Dark Theme">
                    <i class="bi bi-moon-stars-fill"></i>
                </button>

                <?php if (is_logged_in()): ?>
                    <a href="/admin/dashboard.php" class="btn btn-primary btn-sm rounded-pill px-3 py-2 fw-bold">
                        <i class="bi bi-speedometer2 me-1"></i> Dashboard
                    </a>
                <?php else: ?>
                    <a href="/admin/login.php" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-2 fw-semibold">
                        <i class="bi bi-person-lock me-1"></i> Admin Portal
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
