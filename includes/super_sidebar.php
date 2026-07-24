<?php
/**
 * Super Admin (Dean) Sidebar
 * Included in all /admin/super/*.php pages
 */
$currentSuperUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$deanName  = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'Dean Sir';
$firstName = explode(' ', trim($deanName))[0];
?>
<aside class="super-sidebar d-none d-md-flex flex-column"
       style="width:260px;min-height:100vh;flex-shrink:0;
              background:linear-gradient(180deg,#0f172a 0%,#1e1b4b 60%,#0f172a 100%);
              color:#fff;position:sticky;top:0;overflow-y:auto;
              box-shadow:4px 0 24px rgba(0,0,0,0.3);">

    <!-- Brand -->
    <div class="p-4" style="border-bottom:1px solid rgba(255,255,255,0.1);">
        <div class="d-flex align-items-center gap-3 mb-1">
            <img src="/assets/United Logo.webp" alt="ClubHub" style="height:28px;opacity:0.9;">
            <div>
                <div class="fw-bold text-white lh-1" style="font-size:0.95rem;">ClubHub</div>
                <div class="text-white-50" style="font-size:0.58rem;letter-spacing:1.5px;">ADMIN PORTAL</div>
            </div>
        </div>
        <div class="text-white-50 mt-2" style="font-size:0.68rem;">United Institute of Technology</div>
    </div>

    <!-- Nav -->
    <nav class="px-3 py-3 flex-grow-1">

        <a href="/admin/super/index.php"
           class="admin-nav-link <?= $currentSuperUri === '/admin/super/index.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>

        <p class="sidebar-section-label">Club Management</p>
        <a href="/admin/super/clubs.php"
           class="admin-nav-link <?= $currentSuperUri === '/admin/super/clubs.php' ? 'active' : '' ?>">
            <i class="bi bi-trophy"></i> Clubs
            <i class="bi bi-chevron-right ms-auto" style="font-size:0.6rem;opacity:0.5;"></i>
        </a>
        <a href="/admin/super/categories.php"
           class="admin-nav-link <?= $currentSuperUri === '/admin/super/categories.php' ? 'active' : '' ?>">
            <i class="bi bi-grid-3x3-gap"></i> Categories
        </a>
        <a href="/admin/super/users.php"
           class="admin-nav-link <?= $currentSuperUri === '/admin/super/users.php' ? 'active' : '' ?>">
            <i class="bi bi-person-gear"></i> Club Admins
        </a>

        <p class="sidebar-section-label">Content Management</p>
        <a href="/admin/super/clubs.php"
           class="admin-nav-link">
            <i class="bi bi-calendar-event"></i> Events
        </a>
        <a href="/admin/super/messages.php"
           class="admin-nav-link <?= $currentSuperUri === '/admin/super/messages.php' ? 'active' : '' ?>">
            <i class="bi bi-megaphone"></i> Announcements
        </a>

        <p class="sidebar-section-label">User & Access</p>
        <a href="/admin/super/users.php"
           class="admin-nav-link <?= $currentSuperUri === '/admin/super/users.php' ? 'active' : '' ?>">
            <i class="bi bi-people"></i> Users
        </a>
        <a href="/admin/super/audit-logs.php"
           class="admin-nav-link <?= in_array($currentSuperUri, ['/admin/super/audit-logs.php','/admin/super/logs.php']) ? 'active' : '' ?>">
            <i class="bi bi-bar-chart-line"></i> Reports & Analytics
        </a>

        <p class="sidebar-section-label">System & Settings</p>
        <a href="/admin/super/logs.php"
           class="admin-nav-link <?= $currentSuperUri === '/admin/super/logs.php' ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i> System Logs
        </a>
        <a href="/" target="_blank" class="admin-nav-link">
            <i class="bi bi-box-arrow-up-right"></i> View Website
        </a>

        <div class="mt-3">
            <a href="/admin/logout.php"
               class="admin-nav-link"
               style="color:#fca5a5!important;">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </a>
        </div>
    </nav>

    <!-- Dean profile at bottom -->
    <div class="p-3" style="border-top:1px solid rgba(255,255,255,0.1);">
        <div class="d-flex align-items-center gap-3">
            <div class="position-relative">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                     style="width:38px;height:38px;background:linear-gradient(135deg,#6366f1,#a855f7);font-size:0.9rem;flex-shrink:0;">
                    <?= strtoupper(substr($firstName, 0, 1)) ?>
                </div>
                <span class="position-absolute bottom-0 end-0 rounded-circle"
                      style="width:9px;height:9px;background:#22c55e;border:2px solid #0f172a;"></span>
            </div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="text-white fw-semibold text-truncate" style="font-size:0.82rem;"><?= htmlspecialchars($deanName) ?></div>
                <div class="text-white-50" style="font-size:0.65rem;">Dean (Student Affairs)</div>
            </div>
        </div>
    </div>
</aside>
