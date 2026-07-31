<?php
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<nav class="navbar navbar-expand-lg navbar-clubhub sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="index.html">
            <img src="assets/img/usc-logo.png" alt="United Student Club Logo" class="brand-logo-img" style="height: 44px; width: 44px; object-fit: contain;">
            <div class="d-flex flex-column">
                <span class="brand-logo-text" style="font-size: 1.25rem; font-weight: 900; line-height: 1.1; color: #0f172a;">USC UIT</span>
                <span class="fw-extrabold text-danger" style="font-size: 0.68rem; letter-spacing: 0.5px; color: #c8102e !important;">UNITED STUDENT CLUB</span>
            </div>
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

        </div>
    </div>
</nav>
