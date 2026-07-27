<?php
/**
 * Super Admin (Dean Sir) Executive Universal Responsive Sidebar
 * Included in all /admin/super/*.php pages
 */
$currentSuperUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$deanName  = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'Dean Sir';
$firstName = explode(' ', trim($deanName))[0];

// Dynamic Badge Counters
$pendingPropBadge = 0;
$unreadMsgBadge = 0;

if (class_exists('Database')) {
    try {
        $dbConn = Database::getConnection();
        $pendingPropBadge = (int)$dbConn->query("SELECT COUNT(*) FROM club_proposals WHERE status = 'pending'")->fetchColumn();
        $unreadMsgBadge   = (int)$dbConn->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();
    } catch (Exception $e) {
        // Quiet fallback
    }
}
?>
<style>
    :root {
        --super-sidebar-width: 280px;
    }
    
    .super-sidebar {
        width: var(--super-sidebar-width);
        background: linear-gradient(180deg, #090d16 0%, #0f172a 35%, #181537 75%, #090c15 100%);
        min-height: 100vh;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 6px 0 32px rgba(0,0,0,0.45);
        z-index: 1050;
        border-right: 1px solid rgba(255, 255, 255, 0.07);
    }
    
    .super-sidebar-brand {
        padding: 6px 8px 18px 8px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    }

    .brand-logo-card {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 16px;
        padding: 10px 12px;
        backdrop-filter: blur(10px);
        transition: transform 0.2s ease;
    }
    .brand-logo-card:hover {
        background: rgba(255, 255, 255, 0.08);
    }

    .admin-nav-link {
        color: rgba(255, 255, 255, 0.75);
        padding: 11px 15px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.88rem;
        transition: all 0.22s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 4px;
        position: relative;
        border: 1px solid transparent;
    }
    .admin-nav-link i {
        font-size: 1.2rem;
        width: 24px;
        text-align: center;
        flex-shrink: 0;
        transition: transform 0.2s ease, color 0.2s ease;
    }
    
    .admin-nav-link:hover {
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
        transform: translateX(4px);
        border-color: rgba(255, 255, 255, 0.1);
    }
    .admin-nav-link:hover i {
        color: #818cf8;
        transform: scale(1.1);
    }
    
    .admin-nav-link.active {
        background: linear-gradient(135deg, rgba(99,102,241,0.25) 0%, rgba(168,85,247,0.2) 100%);
        color: #ffffff;
        font-weight: 700;
        border: 1px solid rgba(168,85,247,0.4);
        box-shadow: 0 4px 20px rgba(99, 102, 241, 0.25);
    }
    .admin-nav-link.active::before {
        content: '';
        position: absolute;
        left: -12px;
        top: 20%;
        bottom: 20%;
        width: 4px;
        border-radius: 4px;
        background: linear-gradient(180deg, #818cf8 0%, #c084fc 100%);
        box-shadow: 0 0 10px #c084fc;
    }
    .admin-nav-link.active i {
        color: #a855f7;
    }

    .sidebar-section-label {
        color: rgba(255, 255, 255, 0.4);
        font-size: 0.65rem;
        letter-spacing: 1.6px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 0 12px;
        margin: 18px 0 8px;
    }

    .nav-badge-pill {
        font-size: 0.68rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
        margin-left: auto;
    }

    .dean-footer-card {
        background: rgba(15, 23, 42, 0.7);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 18px;
        padding: 12px;
        backdrop-filter: blur(8px);
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
            background: rgba(9, 13, 22, 0.7);
            backdrop-filter: blur(6px);
            z-index: 1040;
            display: none;
        }
        .sidebar-backdrop.show {
            display: block;
        }
    }
</style>

<!-- Mobile Header Bar (< 992px) -->
<header class="d-lg-none text-white p-3 d-flex align-items-center justify-content-between shadow-sm sticky-top w-100" style="background: #090d16 !important; z-index:1030; border-bottom: 1px solid rgba(255,255,255,0.1);">
    <div class="d-flex align-items-center gap-2.5">
        <button class="btn btn-outline-light btn-sm rounded-circle p-2" type="button" id="superSidebarToggleBtn" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="d-flex align-items-center gap-2">
            <img src="../../assets/United Logo.webp" style="height: 28px; width: auto;" alt="United Logo">
            <div>
                <div class="fw-bold text-white small lh-1">Dean Sir Portal</div>
                <div class="text-white-50" style="font-size:0.6rem;">UNITED INSTITUTE</div>
            </div>
        </div>
    </div>
    <span class="badge bg-purple-subtle text-purple border rounded-pill px-2.5 py-1 small" style="background:#f5f3ff; color:#7c3aed;">EXECUTIVE GOVERNANCE</span>
</header>

<!-- Mobile Sidebar Overlay Backdrop -->
<div class="sidebar-backdrop" id="superSidebarBackdrop"></div>

<!-- Sidebar Drawer -->
<aside class="super-sidebar p-3 p-md-3.5 d-flex flex-column justify-content-between flex-shrink-0" id="superSidebar">
    <div>
        <!-- Glassmorphic Brand Header -->
        <div class="super-sidebar-brand mb-3">
            <div class="brand-logo-card d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2.5">
                    <div class="bg-white rounded-3 p-1.5 shadow-xs d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; flex-shrink: 0;">
                        <img src="../../assets/United Logo.webp" alt="United Logo" style="max-height: 28px; width: auto; object-fit: contain;">
                    </div>
                    <div>
                        <div class="fw-bold text-white lh-1" style="font-size: 0.95rem; letter-spacing: -0.2px;">ClubHub UIT</div>
                        <span class="badge bg-warning bg-opacity-20 text-warning border border-warning-10 rounded-pill px-2 py-0.5 mt-1" style="font-size: 0.58rem; letter-spacing: 0.8px;">
                            DEAN GOVERNANCE
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white d-lg-none" id="superSidebarCloseBtn" aria-label="Close"></button>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="nav flex-column">

            <a href="../dashboard.php"
               class="admin-nav-link <?= (str_contains($currentSuperUri, 'dashboard.php') || str_contains($currentSuperUri, 'index.php')) ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i>
                <span>Executive Dashboard</span>
            </a>

            <!-- SECTION 1: GOVERNANCE -->
            <div class="sidebar-section-label">Campus Governance</div>
            
            <a href="proposals.php"
               class="admin-nav-link <?= str_contains($currentSuperUri, 'proposals.php') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-text-fill"></i>
                <span>Proposals Center</span>
                <?php if ($pendingPropBadge > 0): ?>
                    <span class="nav-badge-pill bg-warning text-dark shadow-xs"><?= $pendingPropBadge ?></span>
                <?php endif; ?>
            </a>

            <a href="clubs.php"
               class="admin-nav-link <?= str_contains($currentSuperUri, 'clubs.php') ? 'active' : '' ?>">
                <i class="bi bi-trophy-fill"></i>
                <span>Manage Clubs</span>
            </a>

            <a href="categories.php"
               class="admin-nav-link <?= str_contains($currentSuperUri, 'categories.php') ? 'active' : '' ?>">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                <span>Categories</span>
            </a>

            <a href="users.php"
               class="admin-nav-link <?= str_contains($currentSuperUri, 'users.php') ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i>
                <span>Club Accounts</span>
            </a>

            <!-- SECTION 2: AUDIT & HELPDESK -->
            <div class="sidebar-section-label">Supervision & Audit</div>

            <a href="messages.php"
               class="admin-nav-link <?= str_contains($currentSuperUri, 'messages.php') ? 'active' : '' ?>">
                <i class="bi bi-inbox-fill"></i>
                <span>Helpdesk Messages</span>
                <?php if ($unreadMsgBadge > 0): ?>
                    <span class="nav-badge-pill bg-danger text-white shadow-xs"><?= $unreadMsgBadge ?></span>
                <?php endif; ?>
            </a>

            <a href="audit-logs.php"
               class="admin-nav-link <?= (str_contains($currentSuperUri, 'audit-logs.php') || str_contains($currentSuperUri, 'logs.php')) ? 'active' : '' ?>">
                <i class="bi bi-shield-check"></i>
                <span>Security Audit Logs</span>
            </a>

            <!-- SECTION 3: EXTERNAL -->
            <div class="sidebar-section-label">Quick Portals</div>

            <a href="../../index.html" target="_blank" class="admin-nav-link text-info">
                <i class="bi bi-globe2"></i>
                <span>Public Student Portal</span>
                <i class="bi bi-arrow-up-right small ms-auto opacity-50"></i>
            </a>
        </nav>
    </div>

    <!-- Dean Profile Footer Card -->
    <div class="dean-footer-card mt-4">
        <div class="d-flex align-items-center gap-2.5 mb-2.5">
            <div class="position-relative">
                <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white shadow-sm"
                     style="width:38px;height:38px;background:linear-gradient(135deg,#6366f1,#a855f7);font-size:0.92rem;flex-shrink:0;">
                    <?= strtoupper(substr($firstName, 0, 1)) ?>
                </div>
                <span class="position-absolute bottom-0 end-0 rounded-circle"
                      style="width:9px;height:9px;background:#22c55e;border:2px solid #090d16; box-shadow:0 0 6px #22c55e;"></span>
            </div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="text-white fw-bold text-truncate" style="font-size:0.84rem;"><?= htmlspecialchars($deanName) ?></div>
                <div class="text-white-50" style="font-size:0.65rem;">Dean (Student Affairs)</div>
            </div>
        </div>
        <a href="../logout.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold py-1.5" style="font-size:0.78rem;">
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
