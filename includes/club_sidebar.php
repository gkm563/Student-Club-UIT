<?php
// Club Portal Universal Sidebar — Premium Redesign
// Expects: $club array, $adminName, $firstName from including page

$currentUri = $_SERVER['REQUEST_URI'] ?? '';

if (!isset($adminName)) {
    $adminName = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Club Lead';
}
if (!isset($firstName)) {
    $firstName = explode(' ', trim($adminName))[0];
}
if (!isset($totalEvents)) {
    try {
        if (isset($club['id']) && isset($db)) {
            $__evtStmt = $db->prepare("SELECT COUNT(*) FROM events WHERE club_id = ?");
            $__evtStmt->execute([$club['id']]);
            $totalEvents = $__evtStmt->fetchColumn();
        } else { $totalEvents = ''; }
    } catch (Exception $e) { $totalEvents = ''; }
}

$inAdmin  = str_contains($currentUri, '/admin/')
         || str_contains($_SERVER['SCRIPT_FILENAME'] ?? '', DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR);

$dashLink   = $inAdmin ? '../club/dashboard.php'              : 'dashboard.php';
$eventsLink = $inAdmin ? 'events.php'                         : '../admin/events.php';
$createLink = $inAdmin ? 'create-event.php'                   : '../admin/create-event.php';
$profileLink= $inAdmin ? 'profile.php'                        : '../admin/profile.php';
$galleryLink= $inAdmin ? 'gallery.php'                        : '../admin/gallery.php';
$recruitLink= $inAdmin ? 'recruitment.php'                    : '../admin/recruitment.php';
$logoutLink = $inAdmin ? 'logout.php'                         : '../admin/logout.php';
$publicLink = isset($club['id']) ? ($inAdmin ? '../club-detail.html?id=' : '../club-detail.html?id=') . htmlspecialchars($club['id']) : '#';

if (!function_exists('__clubNavActive')) {
    function __clubNavActive(string $uri, array $patterns): string {
        foreach ($patterns as $p) {
            if (str_contains($uri, $p)) return 'csb-active';
        }
        return '';
    }
}

$clubInitials = strtoupper(substr($club['name'] ?? 'C', 0, 2));
$userInitial  = strtoupper(substr($firstName, 0, 1));
$clubStatus   = $club['status'] ?? 'active';
?>
<style>
/* ═══════════════════════════════════════
   CLUB SIDEBAR — PREMIUM DESIGN v2
═══════════════════════════════════════ */
.csb-wrap {
    width: 268px;
    min-height: 100vh;
    background: #0c1117;
    flex-shrink: 0;
    position: sticky;
    top: 0;
    overflow-y: auto;
    overflow-x: hidden;
    display: flex;
    flex-direction: column;
    border-right: 1px solid rgba(255,255,255,0.06);
    scrollbar-width: thin;
    scrollbar-color: rgba(16,185,129,0.3) transparent;
}
.csb-wrap::-webkit-scrollbar { width: 3px; }
.csb-wrap::-webkit-scrollbar-track { background: transparent; }
.csb-wrap::-webkit-scrollbar-thumb { background: rgba(16,185,129,0.4); border-radius: 10px; }

/* Top gradient accent line */
.csb-wrap::before {
    content: '';
    display: block;
    height: 3px;
    background: linear-gradient(90deg, #10b981, #6366f1, #10b981);
    background-size: 200% 100%;
    animation: csb-shimmer 3s linear infinite;
    flex-shrink: 0;
}
@keyframes csb-shimmer {
    0% { background-position: 0% 0%; }
    100% { background-position: 200% 0%; }
}

/* ── Brand header ── */
.csb-brand {
    padding: 20px 18px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.csb-logo-ring {
    width: 46px; height: 46px;
    border-radius: 14px;
    border: 2px solid rgba(16,185,129,0.4);
    padding: 2px;
    flex-shrink: 0;
    overflow: hidden;
    background: rgba(255,255,255,0.05);
    transition: border-color 0.3s ease;
}
.csb-logo-ring:hover { border-color: #10b981; }
.csb-logo-ring img { width: 100%; height: 100%; object-fit: cover; border-radius: 11px; }
.csb-club-name {
    font-size: 0.88rem;
    font-weight: 700;
    color: #fff;
    line-height: 1.2;
    max-width: 148px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.csb-status-dot {
    width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0;
    box-shadow: 0 0 6px currentColor;
}
.csb-status-dot.active { background: #22c55e; color: #22c55e; }
.csb-status-dot.inactive { background: #f59e0b; color: #f59e0b; }

/* ── User greeting strip ── */
.csb-user-strip {
    margin: 14px 14px 8px;
    padding: 12px 14px;
    border-radius: 14px;
    background: linear-gradient(135deg, rgba(16,185,129,0.12) 0%, rgba(99,102,241,0.08) 100%);
    border: 1px solid rgba(16,185,129,0.18);
}
.csb-avatar {
    width: 36px; height: 36px; border-radius: 10px;
    background: linear-gradient(135deg, #10b981, #059669);
    display: flex; align-items: center; justify-content: center;
    font-weight: 800; font-size: 0.9rem; color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(16,185,129,0.35);
}

/* ── Section label ── */
.csb-section-label {
    font-size: 0.6rem;
    font-weight: 800;
    letter-spacing: 1.6px;
    text-transform: uppercase;
    color: rgba(255,255,255,0.28);
    padding: 4px 18px 6px;
    margin-top: 8px;
}

/* ── Nav items ── */
.csb-nav { padding: 0 10px; flex: 1; }

.csb-link {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 12px;
    color: rgba(255,255,255,0.58);
    text-decoration: none;
    font-size: 0.855rem;
    font-weight: 500;
    transition: all 0.18s ease;
    position: relative;
    margin-bottom: 2px;
    overflow: hidden;
}
.csb-link::before {
    content: '';
    position: absolute;
    left: 0; top: 0; bottom: 0;
    width: 3px;
    background: #10b981;
    border-radius: 0 3px 3px 0;
    opacity: 0;
    transition: opacity 0.18s ease;
}
.csb-link-icon {
    width: 32px; height: 32px;
    border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    background: rgba(255,255,255,0.05);
    transition: all 0.18s ease;
    flex-shrink: 0;
}
.csb-link:hover {
    color: #fff;
    background: rgba(255,255,255,0.06);
    transform: translateX(4px);
}
.csb-link:hover .csb-link-icon {
    background: rgba(16,185,129,0.18);
    color: #10b981;
}
.csb-link:hover::before { opacity: 1; }

/* Active state */
.csb-link.csb-active {
    color: #fff;
    background: linear-gradient(135deg, rgba(16,185,129,0.2) 0%, rgba(5,150,105,0.12) 100%);
    font-weight: 600;
}
.csb-link.csb-active::before { opacity: 1; }
.csb-link.csb-active .csb-link-icon {
    background: linear-gradient(135deg, #10b981, #059669);
    color: #fff;
    box-shadow: 0 4px 12px rgba(16,185,129,0.45);
}

/* Create event special link */
.csb-link-create {
    background: linear-gradient(135deg, rgba(99,102,241,0.15) 0%, rgba(16,185,129,0.1) 100%);
    border: 1px solid rgba(99,102,241,0.2);
    color: #a5b4fc !important;
    font-weight: 600;
    margin-bottom: 6px;
}
.csb-link-create:hover {
    background: linear-gradient(135deg, rgba(99,102,241,0.25) 0%, rgba(16,185,129,0.18) 100%);
    border-color: rgba(99,102,241,0.4);
    color: #c7d2fe !important;
    transform: translateX(4px);
}
.csb-link-create .csb-link-icon {
    background: rgba(99,102,241,0.2);
    color: #818cf8;
}

/* Sign out link */
.csb-link-logout {
    color: rgba(252,165,165,0.7) !important;
}
.csb-link-logout:hover {
    color: #fca5a5 !important;
    background: rgba(239,68,68,0.08) !important;
}
.csb-link-logout .csb-link-icon { background: rgba(239,68,68,0.1); color: #f87171; }

/* Divider */
.csb-divider {
    height: 1px;
    background: rgba(255,255,255,0.06);
    margin: 10px 14px;
}

/* Badge counter */
.csb-badge {
    margin-left: auto;
    background: rgba(16,185,129,0.2);
    color: #34d399;
    border: 1px solid rgba(16,185,129,0.25);
    border-radius: 20px;
    font-size: 0.68rem;
    font-weight: 700;
    padding: 1px 8px;
    min-width: 20px;
    text-align: center;
}

/* ── Bottom user card ── */
.csb-footer {
    padding: 14px;
    border-top: 1px solid rgba(255,255,255,0.06);
    margin-top: auto;
}
.csb-user-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    border-radius: 12px;
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.07);
    transition: background 0.18s ease;
}
.csb-user-card:hover { background: rgba(255,255,255,0.07); }

/* ── Mobile header ── */
.csb-mobile-header {
    display: none;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: #0c1117;
    border-bottom: 1px solid rgba(255,255,255,0.08);
    position: sticky;
    top: 0;
    z-index: 1040;
}
@media (max-width: 991.98px) {
    .csb-mobile-header { display: flex; }
    .csb-wrap {
        position: fixed;
        top: 0; left: -100%;
        z-index: 1055;
        height: 100vh;
        transition: left 0.28s cubic-bezier(0.4,0,0.2,1);
    }
    .csb-wrap.show { left: 0; }
    .csb-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.6);
        backdrop-filter: blur(4px);
        z-index: 1050;
        display: none;
        opacity: 0;
        transition: opacity 0.28s ease;
    }
    .csb-backdrop.show { display: block; opacity: 1; }
}
@media (min-width: 992px) {
    .csb-mobile-header { display: none !important; }
    .csb-backdrop { display: none !important; }
}
</style>

<!-- Mobile Top Header Bar -->
<header class="csb-mobile-header">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-sm rounded-2 d-flex align-items-center justify-content-center" id="clubSidebarToggleBtn"
                style="width:36px;height:36px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:#fff;" aria-label="Menu">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="csb-logo-ring" style="width:30px;height:30px;border-radius:9px;">
            <img src="<?= e($club['logo'] ?? '../assets/United Logo.webp') ?>" alt="" onerror="this.src='../assets/United Logo.webp'">
        </div>
        <span class="fw-bold text-white" style="font-size:0.88rem;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($club['short_name'] ?? $club['name'] ?? 'Club Portal') ?></span>
    </div>
    <span style="font-size:0.65rem;font-weight:700;letter-spacing:0.8px;text-transform:uppercase;color:#10b981;border:1px solid rgba(16,185,129,0.3);border-radius:20px;padding:3px 10px;">LEAD</span>
</header>

<!-- Backdrop -->
<div class="csb-backdrop" id="clubSidebarBackdrop"></div>

<!-- Sidebar -->
<aside class="csb-wrap" id="clubSidebarEl">

    <!-- Brand Header -->
    <div class="csb-brand">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <div class="csb-logo-ring">
                    <img src="<?= e($club['logo'] ?? '../assets/United Logo.webp') ?>" alt="<?= e($club['name'] ?? '') ?>" onerror="this.src='../assets/United Logo.webp'">
                </div>
                <div style="min-width:0;">
                    <div class="csb-club-name" title="<?= e($club['name'] ?? '') ?>"><?= e($club['short_name'] ?? $club['name'] ?? 'My Club') ?></div>
                    <div class="d-flex align-items-center gap-1 mt-1">
                        <span class="csb-status-dot <?= $clubStatus === 'active' ? 'active' : 'inactive' ?>"></span>
                        <span style="font-size:0.62rem;color:rgba(255,255,255,0.4);letter-spacing:0.5px;text-transform:uppercase;"><?= ucfirst($clubStatus) ?> Chapter</span>
                    </div>
                </div>
            </div>
            <button type="button" class="d-lg-none" id="clubSidebarCloseBtn" aria-label="Close"
                    style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);color:rgba(255,255,255,0.6);width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:pointer;flex-shrink:0;font-size:0.8rem;">
                ✕
            </button>
        </div>
    </div>

    <!-- User Greeting -->
    <div class="csb-user-strip mx-2 mt-3">
        <div class="d-flex align-items-center gap-2">
            <div class="csb-avatar"><?= $userInitial ?></div>
            <div style="min-width:0;">
                <div style="font-size:0.65rem;color:rgba(255,255,255,0.4);letter-spacing:0.8px;text-transform:uppercase;font-weight:700;">Welcome back 👋</div>
                <div style="font-size:0.87rem;font-weight:700;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:130px;"><?= e($firstName) ?></div>
            </div>
            <div class="ms-auto">
                <span style="font-size:0.58rem;background:rgba(16,185,129,0.15);color:#34d399;border:1px solid rgba(16,185,129,0.25);border-radius:20px;padding:2px 7px;font-weight:700;letter-spacing:0.5px;">LEAD</span>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="csb-nav mt-3">
        <div class="csb-section-label">Main Menu</div>

        <a href="<?= $dashLink ?>" class="csb-link <?= __clubNavActive($currentUri, ['club/dashboard', 'club/dashboard.php']) ?>">
            <span class="csb-link-icon"><i class="bi bi-speedometer2"></i></span>
            Dashboard
        </a>

        <a href="<?= $eventsLink ?>" class="csb-link <?= __clubNavActive($currentUri, ['events.php', 'event-detail.php']) ?>">
            <span class="csb-link-icon"><i class="bi bi-calendar-event"></i></span>
            Manage Events
            <?php if ($totalEvents !== ''): ?>
                <span class="csb-badge"><?= $totalEvents ?></span>
            <?php endif; ?>
        </a>

        <a href="<?= $createLink ?>" class="csb-link csb-link-create <?= __clubNavActive($currentUri, ['create-event.php']) ?>">
            <span class="csb-link-icon"><i class="bi bi-plus-circle-fill"></i></span>
            Create New Event
        </a>

        <a href="<?= $profileLink ?>" class="csb-link <?= __clubNavActive($currentUri, ['profile.php']) ?>">
            <span class="csb-link-icon"><i class="bi bi-person-vcard"></i></span>
            Club Profile & Roster
        </a>

        <a href="<?= $galleryLink ?>" class="csb-link <?= __clubNavActive($currentUri, ['gallery.php']) ?>">
            <span class="csb-link-icon"><i class="bi bi-images"></i></span>
            Photo Gallery
        </a>

        <a href="<?= $recruitLink ?>" class="csb-link <?= __clubNavActive($currentUri, ['recruitment.php']) ?>">
            <span class="csb-link-icon"><i class="bi bi-person-plus"></i></span>
            Recruitment Drive
        </a>

        <div class="csb-divider"></div>
        <div class="csb-section-label">Quick Links</div>

        <a href="<?= $publicLink ?>" target="_blank" class="csb-link">
            <span class="csb-link-icon" style="color:#38bdf8;"><i class="bi bi-globe2"></i></span>
            Public Chapter Page
            <i class="bi bi-arrow-up-right ms-auto" style="font-size:0.7rem;opacity:0.5;"></i>
        </a>

        <a href="<?= $logoutLink ?>" class="csb-link csb-link-logout">
            <span class="csb-link-icon"><i class="bi bi-box-arrow-right"></i></span>
            Sign Out
        </a>
    </nav>

    <!-- Footer User Card -->
    <div class="csb-footer">
        <div class="csb-user-card">
            <div class="csb-avatar" style="width:34px;height:34px;border-radius:9px;font-size:0.82rem;"><?= $userInitial ?></div>
            <div style="min-width:0;flex:1;">
                <div style="font-size:0.8rem;font-weight:700;color:#fff;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($adminName) ?></div>
                <div style="font-size:0.65rem;color:rgba(255,255,255,0.38);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($club['short_name'] ?? 'Club') ?> Admin</div>
            </div>
            <a href="<?= $logoutLink ?>" title="Sign Out" style="color:rgba(252,165,165,0.6);flex-shrink:0;font-size:1rem;text-decoration:none;" onmouseover="this.style.color='#fca5a5'" onmouseout="this.style.color='rgba(252,165,165,0.6)'">
                <i class="bi bi-power"></i>
            </a>
        </div>
    </div>

</aside>

<script>
(function () {
    const toggleBtn = document.getElementById('clubSidebarToggleBtn');
    const closeBtn  = document.getElementById('clubSidebarCloseBtn');
    const sidebar   = document.getElementById('clubSidebarEl');
    const backdrop  = document.getElementById('clubSidebarBackdrop');

    function open()  { sidebar?.classList.add('show');    backdrop?.classList.add('show'); }
    function close() { sidebar?.classList.remove('show'); backdrop?.classList.remove('show'); }

    toggleBtn?.addEventListener('click', open);
    closeBtn?.addEventListener('click',  close);
    backdrop?.addEventListener('click',  close);

    // Close on Escape
    document.addEventListener('keydown', e => { if (e.key === 'Escape') close(); });
})();
</script>
