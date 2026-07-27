<?php
/**
 * Super Admin (Dean Sir) Universal Responsive Sidebar Component
 * Included in all /admin/super/*.php pages
 */
$currentSuperUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$deanName  = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'Dean Sir';
$firstName = explode(' ', trim($deanName))[0];
?>
<style>
    :root {
        --super-sidebar-width: 270px;
    }
    .super-sidebar {
        width: var(--super-sidebar-width);
        background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 60%, #0f172a 100%);
        min-height: 100vh;
        transition: all 0.3s ease;
        box-shadow: 4px 0 24px rgba(0,0,0,0.3);
        z-index: 1050;
    }
    .admin-nav-link {
        color: rgba(255,255,255,0.72);
        padding: 12px 16px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        margin-bottom: 3px;
    }
    .admin-nav-link i {
        font-size: 1.15rem;
        width: 22px;
        text-align: center;
        flex-shrink: 0;
    }
    .admin-nav-link:hover {
        background: rgba(255,255,255,0.12);
        color: #ffffff;
        transform: translateX(3px);
    }
    .admin-nav-link.active {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: #ffffff;
        box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
    }
    .sidebar-section-label {
        color: rgba(255,255,255,0.38);
        font-size: 0.65rem;
        letter-spacing: 1.5px;
        font-weight: 700;
        text-transform: uppercase;
        padding: 0 14px;
        margin: 18px 0 6px;
    }
    @media (max-width: 991.98px) {
        .super-sidebar {
            position: fixed;
            top: 0;
            left: -100%;
            height: 100vh;
        }
        .super-sidebar.show {
            left: 0;
        }
        .sidebar-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            z-index: 1040;
            display: none;
        }
        .sidebar-backdrop.show {
            display: block;
        }
    }
</style>

<!-- Mobile Header Bar (< 992px) -->
<header class="d-lg-none bg-dark text-white p-3 d-flex align-items-center justify-content-between shadow-sm sticky-top w-100" style="background: #0f172a !important; z-index:1030;">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-light btn-sm rounded-circle p-2" type="button" id="superSidebarToggleBtn" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="d-flex align-items-center gap-2">
            <img src="../../assets/United Logo.webp" style="height: 28px; width: auto;" alt="United Logo">
            <span class="fw-bold text-white small">Dean Sir Portal</span>
        </div>
    </div>
    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small">SUPER ADMIN</span>
</header>

<!-- Mobile Sidebar Overlay Backdrop -->
<div class="sidebar-backdrop" id="superSidebarBackdrop"></div>

<!-- Sidebar Drawer -->
<aside class="super-sidebar p-3 p-md-4 d-flex flex-column justify-content-between flex-shrink-0 shadow-lg" id="superSidebar">
    <div>
        <!-- Brand Header -->
        <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom border-white-10">
            <div class="d-flex align-items-center gap-3">
                <img src="../../assets/United Logo.webp" alt="ClubHub Logo" style="height:34px; width:auto; object-fit:contain;">
                <div>
                    <div class="fw-bold text-white lh-1" style="font-size:0.95rem;">ClubHub UIT</div>
                    <div class="text-white-50" style="font-size:0.6rem;letter-spacing:1.5px;">DEAN SIR PORTAL</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none" id="superSidebarCloseBtn" aria-label="Close"></button>
        </div>

        <!-- Navigation -->
        <nav class="nav flex-column gap-1">

            <a href="../dashboard.php"
               class="admin-nav-link <?= (str_contains($currentSuperUri, 'dashboard.php') || str_contains($currentSuperUri, 'index.php')) ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> Executive Overview
            </a>

            <div class="sidebar-section-label">Campus Governance</div>
            <a href="proposals.php"
               class="admin-nav-link <?= str_contains($currentSuperUri, 'proposals.php') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text"></i> Proposals Center
            </a>
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

            <div class="sidebar-section-label">Supervision & Audit</div>
            <a href="messages.php"
               class="admin-nav-link <?= str_contains($currentSuperUri, 'messages.php') ? 'active' : '' ?>">
                <i class="bi bi-inbox-fill"></i> Help Desk Messages
            </a>
            <a href="audit-logs.php"
               class="admin-nav-link <?= (str_contains($currentSuperUri, 'audit-logs.php') || str_contains($currentSuperUri, 'logs.php')) ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i> Security Audit Logs
            </a>

            <div class="sidebar-section-label">Quick Links</div>
            <a href="../../index.html" target="_blank" class="admin-nav-link text-info">
                <i class="bi bi-box-arrow-up-right"></i> View Campus Website
            </a>
        </nav>
    </div>

    <!-- Dean Profile Footer -->
    <div class="pt-3 border-top border-white-10 mt-4">
        <div class="d-flex align-items-center gap-2.5 mb-3">
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
        <a href="../logout.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold">
            <i class="bi bi-box-arrow-right me-1"></i> Sign Out
        </a>
    </div>
</aside>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('superSidebarToggleBtn');
        const closeBtn = document.getElementById('superSidebarCloseBtn');
        const sidebar = document.getElementById('superSidebar');
        const backdrop = document.getElementById('superSidebarBackdrop');

        function openSidebar() {
            if (sidebar) sidebar.classList.add('show');
            if (backdrop) backdrop.classList.add('show');
        }

        function closeSidebar() {
            if (sidebar) sidebar.classList.remove('show');
            if (backdrop) backdrop.classList.remove('show');
        }

        if (toggleBtn) toggleBtn.addEventListener('click', openSidebar);
        if (closeBtn) closeBtn.addEventListener('click', closeSidebar);
        if (backdrop) backdrop.addEventListener('click', closeSidebar);
    });
</script>
