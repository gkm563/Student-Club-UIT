<?php
$currentAdminUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Get current logged-in user's name from session
$adminName = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Club Lead';
$firstName = explode(' ', trim($adminName))[0];

// $club is expected to be available from the including page
$clubLogo    = $club['logo']       ?? '../assets/United Logo.webp';
$clubName    = $club['name']       ?? 'My Club';
$clubShort   = $club['short_name'] ?? 'Club';
$clubStatus  = $club['status']     ?? 'active';
?>
<div class="admin-sidebar d-none d-md-flex flex-column" style="width: 260px; min-height: 100vh; background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 60%, #0f172a 100%); flex-shrink: 0; position: sticky; top: 0; overflow-y: auto; box-shadow: 4px 0 24px rgba(0,0,0,0.3);">

    <!-- Top Brand Header -->
    <div class="p-4 border-bottom border-white-10">
        <div class="d-flex align-items-center gap-3 mb-3">
            <img src="../assets/United Logo.webp" alt="ClubHub" style="height: 28px; opacity: 0.9;" onerror="this.src='assets/United Logo.webp'">
            <div>
                <span class="fw-bold text-white d-block lh-1" style="font-size: 0.95rem; letter-spacing: 0.3px;">ClubHub</span>
                <span class="text-white-50" style="font-size: 0.6rem; letter-spacing: 1.5px;">CLUB PORTAL</span>
            </div>
        </div>

        <!-- Club Identity Card -->
        <div class="rounded-3 p-3 d-flex align-items-center gap-3" style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.12);">
            <img src="<?= htmlspecialchars($clubLogo) ?>" alt="<?= htmlspecialchars($clubName) ?>"
                 style="width: 44px; height: 44px; border-radius: 10px; object-fit: cover; border: 2px solid rgba(255,255,255,0.2); flex-shrink: 0;"
                 onerror="this.src='../assets/United Logo.webp'">
            <div class="overflow-hidden">
                <div class="fw-bold text-white lh-sm text-truncate" style="font-size: 0.88rem;" title="<?= htmlspecialchars($clubName) ?>">
                    <?= htmlspecialchars($clubName) ?>
                </div>
                <div class="d-flex align-items-center gap-1 mt-1">
                    <span class="rounded-circle d-inline-block" style="width:7px;height:7px;background:<?= $clubStatus === 'active' ? '#22c55e' : '#f59e0b' ?>;flex-shrink:0;"></span>
                    <span class="text-white-50" style="font-size: 0.65rem; letter-spacing: 0.5px; text-transform: uppercase;"><?= ucfirst($clubStatus) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Greeting -->
    <div class="px-4 pt-4 pb-2">
        <p class="text-white-50 mb-0" style="font-size: 0.72rem; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 600;">Welcome back 👋</p>
        <p class="text-white fw-semibold mb-0" style="font-size: 0.95rem;"><?= htmlspecialchars($firstName) ?></p>
    </div>

    <!-- Navigation -->
    <nav class="px-3 py-2 flex-grow-1">
        <p class="text-white-50 px-2 mb-2 mt-2" style="font-size: 0.6rem; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700;">MAIN MENU</p>

        <a href="dashboard.php" class="admin-nav-link <?= (str_contains($currentAdminUri, 'dashboard.php')) ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="profile.php" class="admin-nav-link <?= (str_contains($currentAdminUri, 'profile.php')) ? 'active' : '' ?>">
            <i class="bi bi-person-vcard"></i> Club Profile & Roster
        </a>
        <a href="events.php" class="admin-nav-link <?= (str_contains($currentAdminUri, 'events.php') || str_contains($currentAdminUri, 'event-detail.php')) ? 'active' : '' ?>">
            <i class="bi bi-calendar-event"></i> Manage Events
        </a>
        <a href="gallery.php" class="admin-nav-link <?= (str_contains($currentAdminUri, 'gallery.php')) ? 'active' : '' ?>">
            <i class="bi bi-images"></i> Photo Gallery
        </a>
        <a href="recruitment.php" class="admin-nav-link <?= (str_contains($currentAdminUri, 'recruitment.php')) ? 'active' : '' ?>">
            <i class="bi bi-person-plus"></i> Recruitment Drive
        </a>

        <div class="border-top border-white-10 my-3"></div>

        <p class="text-white-50 px-2 mb-2" style="font-size: 0.6rem; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700;">QUICK LINKS</p>

        <a href="../index.html" target="_blank" class="admin-nav-link">
            <i class="bi bi-globe2"></i> View Public Site
        </a>
        <a href="logout.php" class="admin-nav-link" style="color: #fca5a5 !important;">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </nav>

    <!-- Bottom User Badge -->
    <div class="p-3 m-3 rounded-3" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.08);">
        <div class="d-flex align-items-center gap-2">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                 style="width: 34px; height: 34px; background: linear-gradient(135deg, #6366f1, #a855f7); font-size: 0.85rem;">
                <?= strtoupper(substr($firstName, 0, 1)) ?>
            </div>
            <div class="overflow-hidden">
                <div class="text-white fw-semibold text-truncate" style="font-size: 0.8rem;"><?= htmlspecialchars($adminName) ?></div>
                <div class="text-white-50" style="font-size: 0.65rem;"><?= htmlspecialchars($clubShort) ?> Admin</div>
            </div>
        </div>
    </div>
</div>
