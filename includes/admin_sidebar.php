<?php
$currentAdminUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
?>
<div class="admin-sidebar p-3 flex-shrink-0 d-none d-md-block">
    <div class="d-flex align-items-center gap-3 mb-4 p-2">
        <img src="/assets/United Logo.webp" alt="United Group Logo" style="height: 38px;">
        <div>
            <span class="fw-bold d-block lh-1">ClubHub</span>
            <span class="small text-white-50" style="font-size: 0.65rem;">CLUB PORTAL</span>
        </div>
    </div>

    <nav class="d-flex flex-column gap-2">
        <a href="/admin/dashboard.php" class="admin-nav-link <?= ($currentAdminUri === '/admin/dashboard.php') ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="/admin/profile.php" class="admin-nav-link <?= ($currentAdminUri === '/admin/profile.php') ? 'active' : '' ?>">
            <i class="bi bi-gear"></i> Club & Roster Setup
        </a>
        <a href="/admin/events.php" class="admin-nav-link <?= ($currentAdminUri === '/admin/events.php') ? 'active' : '' ?>">
            <i class="bi bi-calendar-event"></i> Manage Events
        </a>
        <a href="/admin/gallery.php" class="admin-nav-link <?= ($currentAdminUri === '/admin/gallery.php') ? 'active' : '' ?>">
            <i class="bi bi-images"></i> Photo Gallery
        </a>
        <a href="/admin/recruitment.php" class="admin-nav-link <?= ($currentAdminUri === '/admin/recruitment.php') ? 'active' : '' ?>">
            <i class="bi bi-person-plus"></i> Recruitment Drive
        </a>
        <a href="/admin/logout.php" class="admin-nav-link text-danger mt-4">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </nav>
</div>
