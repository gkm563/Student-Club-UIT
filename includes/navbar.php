<?php
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<nav class="navbar navbar-expand-lg navbar-clubhub sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="index.html">
            <img src="assets/United Logo.webp" alt="United Group Logo" class="brand-logo-img">
            <span class="brand-logo-text">ClubHub UIT</span>
        </a>

        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarClubhub">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarClubhub">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= (str_contains($currentUri, 'index.html') || $currentUri === '/') ? 'active' : '' ?>" href="index.html">Home</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= str_contains($currentUri, 'clubs.html') ? 'active' : '' ?>" href="clubs.html">Clubs</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= str_contains($currentUri, 'events.html') ? 'active' : '' ?>" href="events.html">Events</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= str_contains($currentUri, 'gallery.html') ? 'active' : '' ?>" href="gallery.html">Gallery</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= str_contains($currentUri, 'about.html') ? 'active' : '' ?>" href="about.html">About Us</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= str_contains($currentUri, 'contact.html') ? 'active' : '' ?>" href="contact.html">Contact</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <a href="clubs.html" class="btn btn-sm btn-light rounded-pill px-3 py-2 fw-bold text-primary shadow-sm">
                    <i class="bi bi-compass me-1"></i> Explore Clubs
                </a>
            </div>
        </div>
    </div>
</nav>
