<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Redirect Super Admin (Dean Sir) to Super Dashboard
$userRole = get_current_user_role();
if ($userRole === 'super_admin') {
    header('Location: /admin/super/index.php');
    exit;
}

$db = Database::getConnection();

// Fetch assigned club for this user
$stmt = $db->prepare("
    SELECT c.*, cat.name as category_name
    FROM clubs c
    JOIN club_admins ca ON ca.club_id = c.id
    JOIN categories cat ON c.category_id = cat.id
    WHERE ca.user_id = ?
    LIMIT 1
");
$stmt->execute([get_current_user_id()]);
$club = $stmt->fetch();

if (!$club) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>No Assigned Club | ClubHub UIT</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
        <div class="card p-5 border-0 shadow-lg rounded-4 text-center max-w-md">
            <i class="bi bi-shield-exclamation fs-1 text-warning mb-3"></i>
            <h4 class="fw-bold mb-2">No Club Assigned Yet</h4>
            <p class="text-secondary small mb-4">Your account is active, but Dean Sir has not assigned a club to your leadership profile yet.</p>
            <p class="small text-muted mb-4">Please contact <strong>Dean of Student Affairs</strong> (admin@uit.edu) to issue your club access.</p>
            <a href="/admin/logout.php" class="btn btn-outline-danger rounded-pill px-4 fw-bold">Sign Out</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Stats
$leaderCount = $db->prepare("SELECT COUNT(*) FROM leadership WHERE club_id = ?");
$leaderCount->execute([$club['id']]);
$totalLeaders = $leaderCount->fetchColumn();

$eventCount = $db->prepare("SELECT COUNT(*) FROM events WHERE club_id = ?");
$eventCount->execute([$club['id']]);
$totalEvents = $eventCount->fetchColumn();

$upcomingCount = $db->prepare("SELECT COUNT(*) FROM events WHERE club_id = ? AND event_date >= NOW() AND status NOT IN ('draft','hidden','archived','cancelled')");
$upcomingCount->execute([$club['id']]);
$totalUpcoming = $upcomingCount->fetchColumn();

$galleryCount = $db->prepare("SELECT COUNT(*) FROM gallery_items WHERE club_id = ?");
$galleryCount->execute([$club['id']]);
$totalGallery = $galleryCount->fetchColumn();

$recentEvents = $db->prepare("SELECT * FROM events WHERE club_id = ? ORDER BY event_date DESC LIMIT 5");
$recentEvents->execute([$club['id']]);
$eventsList = $recentEvents->fetchAll();

$upcomingEventsList = $db->prepare("SELECT * FROM events WHERE club_id = ? AND event_date >= NOW() AND status NOT IN ('draft','hidden','archived','cancelled') ORDER BY event_date ASC LIMIT 4");
$upcomingEventsList->execute([$club['id']]);
$nextEvents = $upcomingEventsList->fetchAll();

$adminName = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'Club Lead';
$firstName = explode(' ', trim($adminName))[0];
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Dashboard | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f1f5f9; }
        .admin-nav-link {
            color: rgba(255,255,255,0.65);
            padding: 11px 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            margin-bottom: 2px;
        }
        .admin-nav-link i { font-size: 1.1rem; width: 20px; text-align: center; }
        .admin-nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateX(3px); }
        .admin-nav-link.active { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        .border-white-10 { border-color: rgba(255,255,255,0.1) !important; }
    </style>
</head>
<body>

<div class="d-flex" style="min-height: 100vh;">
    <!-- Master Sidebar -->
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-grow-1 d-flex flex-column" style="min-width: 0;">

        <!-- Top Header Bar -->
        <header class="bg-white border-bottom px-4 py-3 d-flex align-items-center justify-content-between sticky-top" style="z-index: 900; box-shadow: 0 1px 8px rgba(0,0,0,0.06);">
            <div>
                <h6 class="fw-bold mb-0 text-dark">Dashboard</h6>
                <p class="text-muted mb-0" style="font-size: 0.75rem;"><?= date('l, d F Y') ?></p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="events.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" style="font-size: 0.85rem;">
                    <i class="bi bi-plus-lg me-1"></i> New Event
                </a>
                <a href="../index.html" target="_blank" class="btn btn-outline-secondary rounded-pill px-3 py-2" style="font-size: 0.85rem;" title="View Public Site">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Live Site
                </a>
            </div>
        </header>

        <!-- Page Body -->
        <div class="p-4 p-md-5 flex-grow-1">

            <!-- Welcome Banner -->
            <div class="card border-0 rounded-4 mb-4 p-4 text-white" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 60%, #7c3aed 100%); box-shadow: 0 8px 24px rgba(99,102,241,0.3);">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h4 class="fw-bold mb-1">Welcome back, <?= htmlspecialchars($firstName) ?>! 👋</h4>
                        <p class="mb-0 text-white-80" style="opacity: 0.85;"><?= htmlspecialchars($club['tagline'] ?? 'Here\'s what\'s happening in your club today.') ?></p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <img src="<?= htmlspecialchars($club['logo'] ?? '/assets/United Logo.webp') ?>" alt="Club Logo"
                             style="width: 56px; height: 56px; border-radius: 14px; object-fit: cover; border: 3px solid rgba(255,255,255,0.3); box-shadow: 0 4px 12px rgba(0,0,0,0.2);"
                             onerror="this.src='/assets/United Logo.webp'">
                        <div>
                            <div class="fw-bold fs-6"><?= htmlspecialchars($club['short_name'] ?? $club['name']) ?></div>
                            <span class="badge bg-white text-dark rounded-pill px-3 py-1 fw-bold shadow-sm" style="font-size: 0.7rem; letter-spacing: 0.5px;"><?= htmlspecialchars($club['category_name'] ?? 'Club') ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4 Clickable Stat Cards -->
            <div class="row g-4 mb-4">
                <!-- Card 1: Core Team Members -> profile.php -->
                <div class="col-6 col-lg-3">
                    <a href="profile.php" class="text-decoration-none d-block h-100">
                        <div class="card border-0 rounded-4 p-4 h-100 shadow-sm transition-all card-hover-lift" style="background: #f0fdf4;">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: #bbf7d0; width: 44px; height: 44px;">
                                    <i class="bi bi-people-fill text-success fs-5"></i>
                                </div>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1 small fw-bold">Team &rarr;</span>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $totalLeaders ?></h3>
                            <p class="text-muted small mb-0 mt-1">Core Team Members</p>
                        </div>
                    </a>
                </div>

                <!-- Card 2: Total Events Published -> events.php -->
                <div class="col-6 col-lg-3">
                    <a href="events.php" class="text-decoration-none d-block h-100">
                        <div class="card border-0 rounded-4 p-4 h-100 shadow-sm transition-all card-hover-lift" style="background: #eff6ff;">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: #bfdbfe; width: 44px; height: 44px;">
                                    <i class="bi bi-calendar-event-fill text-primary fs-5"></i>
                                </div>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2.5 py-1 small fw-bold">Events &rarr;</span>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $totalEvents ?></h3>
                            <p class="text-muted small mb-0 mt-1">Total Events Published</p>
                        </div>
                    </a>
                </div>

                <!-- Card 3: Upcoming Events -> events.php?status=upcoming -->
                <div class="col-6 col-lg-3">
                    <a href="events.php?status=upcoming" class="text-decoration-none d-block h-100">
                        <div class="card border-0 rounded-4 p-4 h-100 shadow-sm transition-all card-hover-lift" style="background: #fefce8;">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: #fef08a; width: 44px; height: 44px;">
                                    <i class="bi bi-clock-fill text-warning fs-5"></i>
                                </div>
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1 small fw-bold">Coming Up &rarr;</span>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $totalUpcoming ?></h3>
                            <p class="text-muted small mb-0 mt-1">Upcoming Events</p>
                        </div>
                    </a>
                </div>

                <!-- Card 4: Gallery Photos -> profile.php -->
                <div class="col-6 col-lg-3">
                    <a href="profile.php" class="text-decoration-none d-block h-100">
                        <div class="card border-0 rounded-4 p-4 h-100 shadow-sm transition-all card-hover-lift" style="background: #fdf4ff;">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <div class="rounded-3 p-2 d-flex align-items-center justify-content-center" style="background: #e9d5ff; width: 44px; height: 44px;">
                                    <i class="bi bi-images text-purple fs-5" style="color: #a855f7 !important;"></i>
                                </div>
                                <span class="badge rounded-pill px-2.5 py-1 small fw-bold" style="background: rgba(168,85,247,0.1); color: #a855f7; border: 1px solid rgba(168,85,247,0.2);">Gallery &rarr;</span>
                            </div>
                            <h3 class="fw-bold mb-0 text-dark"><?= $totalGallery ?></h3>
                            <p class="text-muted small mb-0 mt-1">Gallery Photos</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Two-Column: Events Table + Upcoming Panel -->
            <div class="row g-4">
                <!-- Left: Recent Events Table -->
                <div class="col-lg-8">
                    <div class="card border-0 rounded-4 shadow-sm">
                        <div class="card-header bg-white border-0 rounded-top-4 px-4 pt-4 pb-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Recent Events</h6>
                                <p class="text-muted mb-0 small">All events created by your club</p>
                            </div>
                            <a href="events.php" class="btn btn-sm btn-primary rounded-pill px-3 py-1-5 fw-semibold">
                                <i class="bi bi-plus-lg me-1"></i> Add Event
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($eventsList)): ?>
                                <div class="text-center py-5 px-4 text-muted">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-2 text-primary opacity-50"></i>
                                    <p class="mb-2 fw-semibold">No events created yet</p>
                                    <p class="small mb-3">Publish your first workshop, hackathon, or competition!</p>
                                    <a href="events.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold">Create First Event</a>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead style="background: #f8fafc;">
                                            <tr class="small text-muted">
                                                <th class="ps-4 py-3 fw-semibold">Event</th>
                                                <th class="py-3 fw-semibold">Date</th>
                                                <th class="py-3 fw-semibold">Status</th>
                                                <th class="py-3 fw-semibold text-end pe-4">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($eventsList as $ev): ?>
                                                <tr>
                                                    <td class="ps-4 py-3">
                                                        <div class="d-flex align-items-center gap-3">
                                                            <img src="<?= htmlspecialchars($ev['banner'] ?? '') ?>" alt=""
                                                                 style="width: 42px; height: 42px; border-radius: 10px; object-fit: cover; background: #e2e8f0;"
                                                                 onerror="this.style.display='none'">
                                                            <div>
                                                                <div class="fw-semibold text-dark small"><?= htmlspecialchars($ev['title']) ?></div>
                                                                <div class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($ev['venue']) ?></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="py-3 small text-muted"><?= date('d M Y', strtotime($ev['event_date'])) ?></td>
                                                    <td class="py-3"><?= get_status_badge($ev['status']) ?></td>
                                                    <td class="py-3 text-end pe-4">
                                                        <a href="event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold" style="font-size: 0.78rem;">
                                                            Edit
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right: Upcoming Events + Quick Actions -->
                <div class="col-lg-4 d-flex flex-column gap-4">

                    <!-- Upcoming Events Panel -->
                    <div class="card border-0 rounded-4 shadow-sm">
                        <div class="card-header bg-white border-0 rounded-top-4 px-4 pt-4 pb-2 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">Upcoming Events</h6>
                            <a href="events.php" class="text-primary small fw-semibold text-decoration-none">View All →</a>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2">
                            <?php if (empty($nextEvents)): ?>
                                <div class="text-center py-4 text-muted small">
                                    <i class="bi bi-calendar-check d-block fs-3 mb-2 opacity-50"></i>
                                    No upcoming events scheduled.
                                </div>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-3 mt-2">
                                <?php foreach ($nextEvents as $ne):
                                    $d = new DateTime($ne['event_date']);
                                ?>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="text-center flex-shrink-0 rounded-3 p-2" style="background: #eff6ff; min-width: 46px;">
                                            <div class="fw-bold text-primary lh-1" style="font-size: 1.1rem;"><?= $d->format('d') ?></div>
                                            <div class="text-primary" style="font-size: 0.6rem; letter-spacing: 0.5px;"><?= strtoupper($d->format('M')) ?></div>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="fw-semibold text-dark text-truncate small"><?= htmlspecialchars($ne['title']) ?></div>
                                            <div class="text-muted" style="font-size: 0.72rem;"><i class="bi bi-clock me-1"></i><?= $d->format('h:i A') ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="card border-0 rounded-4 shadow-sm">
                        <div class="card-header bg-white border-0 rounded-top-4 px-4 pt-4 pb-2">
                            <h6 class="fw-bold mb-0 text-dark">Quick Actions</h6>
                        </div>
                        <div class="card-body px-4 pb-4 pt-2">
                            <div class="row g-2 mt-1">
                                <div class="col-6">
                                    <a href="events.php" class="btn btn-light border rounded-3 w-100 py-3 d-flex flex-column align-items-center gap-1 text-decoration-none hover-shadow" style="font-size: 0.75rem;">
                                        <i class="bi bi-calendar-plus fs-5 text-primary"></i>
                                        <span class="fw-semibold text-dark">New Event</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="gallery.php" class="btn btn-light border rounded-3 w-100 py-3 d-flex flex-column align-items-center gap-1 text-decoration-none" style="font-size: 0.75rem;">
                                        <i class="bi bi-image fs-5 text-purple" style="color: #a855f7 !important;"></i>
                                        <span class="fw-semibold text-dark">Add Photo</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="profile.php" class="btn btn-light border rounded-3 w-100 py-3 d-flex flex-column align-items-center gap-1 text-decoration-none" style="font-size: 0.75rem;">
                                        <i class="bi bi-people fs-5 text-success"></i>
                                        <span class="fw-semibold text-dark">Edit Roster</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="recruitment.php" class="btn btn-light border rounded-3 w-100 py-3 d-flex flex-column align-items-center gap-1 text-decoration-none" style="font-size: 0.75rem;">
                                        <i class="bi bi-megaphone fs-5 text-warning"></i>
                                        <span class="fw-semibold text-dark">Recruitment</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- end page body -->
    </div><!-- end main content -->
</div><!-- end d-flex -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
