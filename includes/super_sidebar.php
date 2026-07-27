<?php
/**
 * Super Admin (Dean Sir) Universal Sidebar
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

    <!-- Brand Header -->
    <div class="p-4" style="border-bottom:1px solid rgba(255,255,255,0.1);">
        <div class="d-flex align-items-center gap-3 mb-1">
            <img src="../../assets/United Logo.webp" alt="ClubHub Logo" style="height:32px; width:auto; object-fit:contain;">
            <div>
                <div class="fw-bold text-white lh-1" style="font-size:0.95rem;">ClubHub UIT</div>
                <div class="text-white-50" style="font-size:0.6rem;letter-spacing:1.5px;">DEAN SIR PORTAL</div>
            </div>
        </div>
        <div class="text-white-50 mt-2" style="font-size:0.68rem;">United Institute of Technology</div>
    </div>

    <!-- Navigation -->
    <nav class="px-3 py-3 flex-grow-1">

        <a href="../dashboard.php"
           class="admin-nav-link <?= (str_contains($currentSuperUri, 'dashboard.php') || str_contains($currentSuperUri, 'index.php')) ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Executive Overview
        </a>

        <p class="sidebar-section-label">Campus Governance</p>
        <a href="clubs.php"
           class="admin-nav-link <?= str_contains($currentSuperUri, 'clubs.php') ? 'active' : '' ?>">
            <i class="bi bi-trophy"></i> Manage Clubs
        </a>
        <a href="categories.php"
           class="admin-nav-link <?= str_contains($currentSuperUri, 'categories.php') ? 'active' : '' ?>">
            <i class="bi bi-grid-3x3-gap"></i> Categories
        </a>
        <a href="users.php"
           class="admin-nav-link <?= str_contains($currentSuperUri, 'users.php') ? 'active' : '' ?>">
            <i class="bi bi-person-gear"></i> Club Accounts
        </a>

        <p class="sidebar-section-label">Supervision & Audit</p>
        <a href="messages.php"
           class="admin-nav-link <?= str_contains($currentSuperUri, 'messages.php') ? 'active' : '' ?>">
            <i class="bi bi-inbox-fill"></i> Help Desk Messages
        </a>
        <a href="audit-logs.php"
           class="admin-nav-link <?= (str_contains($currentSuperUri, 'audit-logs.php') || str_contains($currentSuperUri, 'logs.php')) ? 'active' : '' ?>">
            <i class="bi bi-journal-text"></i> Security Audit Logs
        </a>

        <p class="sidebar-section-label">Quick Links</p>
        <a href="../../index.html" target="_blank" class="admin-nav-link">
            <i class="bi bi-box-arrow-up-right"></i> View Campus Website
        </a>

        <div class="mt-4 pt-3 border-top border-white-10">
            <a href="../logout.php"
               class="admin-nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </a>
        </div>
    </nav>

    <!-- Dean Profile Footer -->
    <div class="p-3" style="border-top:1px solid rgba(255,255,255,0.1);">
        <div class="d-flex align-items-center gap-3">
            <div class="position-relative">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm"
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
