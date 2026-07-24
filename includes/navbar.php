<?php
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<nav class="navbar navbar-expand-lg navbar-clubhub sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-3" href="/index.html">
            <img src="/assets/United Logo.webp" alt="United Group Logo" class="brand-logo-img">
            <span class="brand-logo-text">ClubHub</span>
        </a>

        <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarClubhub">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarClubhub">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= ($currentUri === '/index.html' || $currentUri === '/') ? 'active' : '' ?>" href="/index.html">Home</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= ($currentUri === '/clubs.html') ? 'active' : '' ?>" href="/clubs.html">Clubs</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= ($currentUri === '/events.html') ? 'active' : '' ?>" href="/events.html">Events</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= ($currentUri === '/gallery.html') ? 'active' : '' ?>" href="/gallery.html">Gallery</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= ($currentUri === '/about.html') ? 'active' : '' ?>" href="/about.html">About Us</a></li>
                <li class="nav-item"><a class="nav-link nav-link-clubhub <?= ($currentUri === '/contact.html') ? 'active' : '' ?>" href="/contact.html">Contact</a></li>
            </ul>

            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-link text-white-50 p-0 fs-5" title="Search"><i class="bi bi-search"></i></button>
            </div>
        </div>
    </div>
</nav>
