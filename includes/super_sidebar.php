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
        background: #ffffff;
        min-height: 100vh;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: 4px 0 24px rgba(15, 23, 42, 0.05);
        z-index: 1050;
        border-right: 1px solid #e2e8f0;
    }
    
    .super-sidebar-brand {
        padding: 4px 4px 16px 4px;
        border-bottom: 1px solid #f1f5f9;
    }

    .brand-logo-card {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 10px 12px;
        transition: all 0.2s ease;
    }
    .brand-logo-card:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
    }

    .admin-nav-link {
        color: #475569;
        padding: 11px 15px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.88rem;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 4px;
        position: relative;
        border: 1px solid transparent;
    }
    .admin-nav-link i {
        font-size: 1.2rem;
        width: 24px;
        text-align: center;
        flex-shrink: 0;
        color: #64748b;
        transition: transform 0.2s ease, color 0.2s ease;
    }
    
    .admin-nav-link:hover {
        background: #f1f5f9;
        color: #0f172a;
        transform: translateX(4px);
        border-color: #e2e8f0;
    }
    .admin-nav-link:hover i {
        color: #4f46e5;
        transform: scale(1.12);
    }
    
    .admin-nav-link.active {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #ffffff !important;
        font-weight: 700;
        box-shadow: 0 4px 16px rgba(79, 70, 229, 0.35);
        border-color: transparent;
    }
    .admin-nav-link.active i {
        color: #ffffff !important;
    }

    .sidebar-section-label {
        color: #94a3b8;
        font-size: 0.65rem;
        letter-spacing: 1.5px;
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
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        padding: 12px;
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
<header class="d-lg-none text-dark p-3 d-flex align-items-center justify-content-between shadow-sm sticky-top w-100 bg-white border-bottom" style="z-index:1030;">
    <div class="d-flex align-items-center gap-2.5">
        <button class="btn btn-outline-dark btn-sm rounded-circle p-2" type="button" id="superSidebarToggleBtn" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="d-flex align-items-center gap-2">
            <img src="../../assets/United Logo.webp" style="height: 28px; width: auto;" alt="United Logo">
            <div>
                <div class="fw-bold text-dark small lh-1">Dean Portal</div>
                <div class="text-muted" style="font-size:0.6rem;">UNITED INSTITUTE</div>
            </div>
        </div>
    </div>
    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small">EXECUTIVE GOVERNANCE</span>
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
                    <div class="bg-white rounded-3 p-1.5 shadow-xs d-flex align-items-center justify-content-center border" style="width: 38px; height: 38px; flex-shrink: 0;">
                        <img src="../../assets/United Logo.webp" alt="United Logo" style="max-height: 26px; width: auto; object-fit: contain;">
                    </div>
                    <div>
                        <div class="fw-bold text-dark lh-1 mb-1" style="font-size: 0.96rem; letter-spacing: -0.2px;">ClubHub UIT</div>
                        <span class="badge rounded-pill px-2.5 py-1 text-uppercase fw-bold shadow-xs" style="background: linear-gradient(135deg, #4f46e5, #7c3aed); color: #ffffff; font-size: 0.6rem; letter-spacing: 0.8px; font-weight: 800;">
                            <i class="bi bi-shield-fill-check me-1"></i> DEAN GOVERNANCE
                        </span>
                    </div>
                </div>
                <button type="button" class="btn-close d-lg-none" id="superSidebarCloseBtn" aria-label="Close"></button>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="nav flex-column">

            <a href="index.php"
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
               class="admin-nav-link <?= (str_contains($currentSuperUri, 'clubs.php') || str_contains($currentSuperUri, 'club-detail.php')) ? 'active' : '' ?>">
                <i class="bi bi-trophy-fill"></i>
                <span>Campus Student Clubs</span>
            </a>

            <a href="events.php"
               class="admin-nav-link <?= str_contains($currentSuperUri, 'events.php') ? 'active' : '' ?>">
                <i class="bi bi-calendar-event-fill"></i>
                <span>Events Governance</span>
            </a>

            <a href="categories.php"
               class="admin-nav-link <?= str_contains($currentSuperUri, 'categories.php') ? 'active' : '' ?>">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                <span>Categories</span>
            </a>

            <a href="users.php"
               class="admin-nav-link <?= str_contains($currentSuperUri, 'users.php') ? 'active' : '' ?>">
                <i class="bi bi-shield-lock-fill"></i>
                <span>System Main Admins</span>
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

            <a href="../../index.html" target="_blank" class="admin-nav-link text-primary">
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
                     style="width:38px;height:38px;background:linear-gradient(135deg,#4f46e5,#7c3aed);font-size:0.92rem;flex-shrink:0;">
                    <?= strtoupper(substr($firstName, 0, 1)) ?>
                </div>
                <span class="position-absolute bottom-0 end-0 rounded-circle"
                      style="width:9px;height:9px;background:#22c55e;border:2px solid #ffffff; box-shadow:0 0 4px #22c55e;"></span>
            </div>
            <div class="flex-grow-1 overflow-hidden">
                <div class="text-dark fw-bold text-truncate" style="font-size:0.84rem;"><?= htmlspecialchars($deanName) ?></div>
                <div class="text-muted" style="font-size:0.65rem;">Dean (Student Affairs)</div>
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
