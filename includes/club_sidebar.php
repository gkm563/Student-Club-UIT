<?php
// Club Portal Universal Sidebar
// Expected: $club array, $adminName, $firstName from including page
// Also needs $totalEvents if available (optional)

$currentUri = $_SERVER['REQUEST_URI'] ?? '';

// Fallbacks if variables not yet set
if (!isset($adminName)) {
    $adminName = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Club Lead';
}
if (!isset($firstName)) {
    $firstName = explode(' ', trim($adminName))[0];
}
if (!isset($totalEvents)) {
    // Try to count from DB if $club and $db are available
    try {
        if (isset($club['id']) && isset($db)) {
            $__evtStmt = $db->prepare("SELECT COUNT(*) FROM events WHERE club_id = ?");
            $__evtStmt->execute([$club['id']]);
            $totalEvents = $__evtStmt->fetchColumn();
        } else {
            $totalEvents = '';
        }
    } catch (Exception $e) {
        $totalEvents = '';
    }
}

// Determine which path prefix to use based on current file location
// Files in /admin/ need ../club/ prefix for dashboard, files in /club/ need ../admin/ prefix
$inAdmin = str_contains($currentUri, '/admin/') || str_contains($_SERVER['SCRIPT_FILENAME'] ?? '', DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR);

$dashLink         = $inAdmin ? '../club/dashboard.php'         : 'dashboard.php';
$eventsLink       = $inAdmin ? 'events.php'                   : '../admin/events.php';
$createLink       = $inAdmin ? 'create-event.php'             : '../admin/create-event.php';
$profileLink      = $inAdmin ? 'profile.php'                  : '../admin/profile.php';
$galleryLink      = $inAdmin ? 'gallery.php'                  : '../admin/gallery.php';
$recruitLink      = $inAdmin ? 'recruitment.php'              : '../admin/recruitment.php';
$logoutLink       = $inAdmin ? 'logout.php'                   : '../admin/logout.php';
$publicLink       = (isset($club['id']) ? ($inAdmin ? '../club-detail.html?id=' . htmlspecialchars($club['id']) : '../club-detail.html?id=' . htmlspecialchars($club['id'])) : '#');

// Active state detection helper
function __clubNavActive(string $uri, array $patterns): string {
    foreach ($patterns as $p) {
        if (str_contains($uri, $p)) return 'active';
    }
    return '';
}
?>
<!-- Club Portal Sidebar — Universal Component -->
<style>
    .club-sidebar-wrap {
        width: 270px;
        min-height: 100vh;
        background: linear-gradient(180deg, #0f172a 0%, #064e3b 60%, #0f172a 100%);
        flex-shrink: 0;
        position: sticky;
        top: 0;
        overflow-y: auto;
        box-shadow: 4px 0 24px rgba(0,0,0,0.25);
        transition: all 0.3s ease;
    }
    .club-sidebar-wrap .club-nav-link {
        color: rgba(255,255,255,0.72);
        padding: 11px 16px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 12px;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.875rem;
        transition: all 0.2s ease;
        margin-bottom: 3px;
    }
    .club-sidebar-wrap .club-nav-link i {
        font-size: 1.1rem;
        width: 20px;
        text-align: center;
        flex-shrink: 0;
    }
    .club-sidebar-wrap .club-nav-link:hover {
        background: rgba(255,255,255,0.1);
        color: #fff;
        transform: translateX(3px);
    }
    .club-sidebar-wrap .club-nav-link.active {
        background: linear-gradient(135deg, #10b981, #059669);
        color: #fff !important;
        box-shadow: 0 4px 14px rgba(16,185,129,0.35);
    }
    .club-sidebar-wrap .border-white-10 { border-color: rgba(255,255,255,0.1) !important; }

    /* Mobile: Sidebar off-canvas via .show class */
    @media (max-width: 991.98px) {
        .club-sidebar-wrap {
            position: fixed;
            top: 0;
            left: -100%;
            z-index: 1055;
            height: 100vh;
            width: 270px;
        }
        .club-sidebar-wrap.show {
            left: 0;
        }
        .club-sidebar-backdrop {
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            z-index: 1050;
            display: none;
        }
        .club-sidebar-backdrop.show { display: block; }
    }
</style>

<!-- Mobile Header Bar -->
<header class="d-lg-none text-white p-3 d-flex align-items-center justify-content-between shadow-sm sticky-top" style="background: #0f172a; z-index: 1040;">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-light btn-sm rounded-circle p-2" type="button" id="clubSidebarToggleBtn" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <img src="<?= e($club['logo'] ?? '../assets/United Logo.webp') ?>" class="rounded-2 bg-white p-1" style="width: 32px; height: 32px; object-fit: cover;" alt="" onerror="this.src='../assets/United Logo.webp'">
        <span class="fw-bold text-white small text-truncate" style="max-width: 160px;"><?= e($club['short_name'] ?? $club['name'] ?? 'Club Portal') ?></span>
    </div>
    <span class="badge bg-success-subtle text-success border rounded-pill px-2 py-1 small">LEAD PORTAL</span>
</header>

<!-- Mobile Backdrop Overlay -->
<div class="club-sidebar-backdrop" id="clubSidebarBackdrop"></div>

<!-- Sidebar -->
<aside class="club-sidebar-wrap p-3 p-md-4 d-flex flex-column justify-content-between" id="clubSidebarEl">
    <div>
        <!-- Brand Header -->
        <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom border-white-10">
            <div class="d-flex align-items-center gap-3">
                <img src="<?= e($club['logo'] ?? '../assets/United Logo.webp') ?>"
                     class="rounded-3 bg-white p-1 shadow-sm"
                     style="width: 44px; height: 44px; object-fit: cover; flex-shrink: 0;"
                     alt="<?= e($club['name'] ?? 'Club') ?>"
                     onerror="this.src='../assets/United Logo.webp'">
                <div class="overflow-hidden">
                    <h6 class="fw-bold mb-0 text-white text-truncate" style="max-width: 145px; font-size: 0.88rem;" title="<?= e($club['name'] ?? '') ?>">
                        <?= e($club['short_name'] ?? $club['name'] ?? 'My Club') ?>
                    </h6>
                    <span class="badge bg-success-subtle text-success border rounded-pill px-2 py-0 small" style="font-size: 0.6rem;">OFFICIAL LEAD PORTAL</span>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white d-lg-none flex-shrink-0" id="clubSidebarCloseBtn" aria-label="Close"></button>
        </div>

        <!-- Welcome Greeting -->
        <div class="mb-3 px-1">
            <div class="text-white-50 mb-0" style="font-size: 0.67rem; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 600;">Welcome back 👋</div>
            <div class="text-white fw-semibold" style="font-size: 0.9rem;"><?= e($firstName) ?></div>
        </div>

        <!-- Navigation -->
        <nav class="nav flex-column gap-0 mb-2">
            <p class="text-white-50 px-2 mb-1 mt-1" style="font-size: 0.6rem; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700;">MAIN MENU</p>

            <a href="<?= $dashLink ?>" class="club-nav-link <?= __clubNavActive($currentUri, ['club/dashboard', 'club/dashboard.php']) ?>">
                <i class="bi bi-speedometer2"></i> Dashboard Overview
            </a>
            <a href="<?= $eventsLink ?>" class="club-nav-link <?= __clubNavActive($currentUri, ['events.php', 'event-detail.php']) ?>">
                <i class="bi bi-calendar-event"></i> Manage Events
                <?php if ($totalEvents !== ''): ?>
                    <span class="badge bg-success rounded-pill ms-auto" style="font-size: 0.65rem;"><?= $totalEvents ?></span>
                <?php endif; ?>
            </a>
            <a href="<?= $createLink ?>" class="club-nav-link <?= __clubNavActive($currentUri, ['create-event.php']) ?>" style="color: #60a5fa !important; font-weight: 600;">
                <i class="bi bi-plus-circle-fill"></i> + Create New Event
            </a>
            <a href="<?= $profileLink ?>" class="club-nav-link <?= __clubNavActive($currentUri, ['profile.php']) ?>">
                <i class="bi bi-person-vcard"></i> Club Profile & Roster
            </a>
            <a href="<?= $galleryLink ?>" class="club-nav-link <?= __clubNavActive($currentUri, ['gallery.php']) ?>">
                <i class="bi bi-images"></i> Photo Gallery
            </a>
            <a href="<?= $recruitLink ?>" class="club-nav-link <?= __clubNavActive($currentUri, ['recruitment.php']) ?>">
                <i class="bi bi-person-plus"></i> Recruitment Drive
            </a>

            <div class="border-top border-white-10 my-2"></div>

            <p class="text-white-50 px-2 mb-1" style="font-size: 0.6rem; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700;">QUICK LINKS</p>

            <a href="<?= $publicLink ?>" target="_blank" class="club-nav-link">
                <i class="bi bi-box-arrow-up-right text-info"></i> Public Chapter Page
            </a>
            <a href="<?= $logoutLink ?>" class="club-nav-link" style="color: #fca5a5 !important;">
                <i class="bi bi-box-arrow-right"></i> Sign Out
            </a>
        </nav>
    </div>

    <!-- Bottom User Badge -->
    <div class="p-3 rounded-3 mt-3" style="background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                 style="width: 36px; height: 36px; background: linear-gradient(135deg, #10b981, #059669); font-size: 0.9rem;">
                <?= strtoupper(substr($firstName, 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                <div class="text-white fw-semibold text-truncate" style="font-size: 0.8rem;"><?= e($adminName) ?></div>
                <div class="text-white-50 text-truncate" style="font-size: 0.65rem;"><?= e($club['short_name'] ?? 'Club') ?> Admin</div>
            </div>
        </div>
    </div>
</aside>

<script>
(function() {
    // Universal club sidebar toggle — works on any page
    const toggleBtn  = document.getElementById('clubSidebarToggleBtn');
    const closeBtn   = document.getElementById('clubSidebarCloseBtn');
    const sidebarEl  = document.getElementById('clubSidebarEl');
    const backdrop   = document.getElementById('clubSidebarBackdrop');

    function openSidebar()  { sidebarEl?.classList.add('show');    backdrop?.classList.add('show'); }
    function closeSidebar() { sidebarEl?.classList.remove('show'); backdrop?.classList.remove('show'); }

    toggleBtn?.addEventListener('click', openSidebar);
    closeBtn?.addEventListener('click', closeSidebar);
    backdrop?.addEventListener('click', closeSidebar);
})();
</script>
