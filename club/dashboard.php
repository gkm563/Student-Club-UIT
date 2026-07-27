<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Redirect Super Admin (Dean Sir) to Dean Dashboard (/admin/dashboard.php)
$userRole = get_current_user_role();
if ($userRole === 'super_admin') {
    header('Location: ../admin/dashboard.php');
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
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>No Assigned Club | ClubHub UIT</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="../assets/css/style.css">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center min-vh-100 p-3">
        <div class="card p-4 p-md-5 border-0 shadow-lg rounded-4 text-center max-w-md w-100">
            <i class="bi bi-shield-exclamation fs-1 text-warning mb-3"></i>
            <h4 class="fw-bold mb-2">No Club Assigned Yet</h4>
            <p class="text-secondary small mb-4">Your account is active, but Dean Sir has not assigned a club to your leadership profile yet.</p>
            <p class="small text-muted mb-4">Please contact <strong>Dean of Student Affairs</strong> (admin@uit.edu) to issue your club access.</p>
            <a href="../admin/logout.php" class="btn btn-outline-danger rounded-pill px-4 fw-bold">Sign Out</a>
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

// This month vs last month events
$thisMonthEvents = $db->prepare("SELECT COUNT(*) FROM events WHERE club_id = ? AND MONTH(event_date) = MONTH(NOW()) AND YEAR(event_date) = YEAR(NOW())");
$thisMonthEvents->execute([$club['id']]);
$eventsThisMonth = $thisMonthEvents->fetchColumn();

$lastMonthEvents = $db->prepare("SELECT COUNT(*) FROM events WHERE club_id = ? AND MONTH(event_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH)) AND YEAR(event_date) = YEAR(DATE_SUB(NOW(), INTERVAL 1 MONTH))");
$lastMonthEvents->execute([$club['id']]);
$eventsLastMonth = $lastMonthEvents->fetchColumn();

// Completed events count
$completedCount = $db->prepare("SELECT COUNT(*) FROM events WHERE club_id = ? AND status = 'completed'");
$completedCount->execute([$club['id']]);
$totalCompleted = $completedCount->fetchColumn();

// Club Health Score (0-100)
$healthScore = 0;
if ($eventsThisMonth > 0) $healthScore += 25;
if ($totalGallery >= 3) $healthScore += 25;
if ($totalLeaders >= 3) $healthScore += 25;
if ($totalUpcoming > 0) $healthScore += 25;
if (!empty($club['description'])) $healthScore = min(100, $healthScore + 10);
$healthBadge = $healthScore >= 80 ? ['Excellent', 'success'] : ($healthScore >= 50 ? ['Good', 'warning'] : ['Needs Attention', 'danger']);

// Pending Tasks
$pendingTasks = [];
if (empty($club['cover_image'])) $pendingTasks[] = ['Add a cover image to your club page', 'bi-image', 'profile.php'];
if (empty($club['description'])) $pendingTasks[] = ['Write a club description', 'bi-pencil', 'profile.php'];
if ($totalLeaders == 0) $pendingTasks[] = ['Add leadership team members', 'bi-person-plus', 'profile.php'];
if ($totalGallery == 0) $pendingTasks[] = ['Upload club gallery photos', 'bi-images', 'gallery.php'];
if ($totalUpcoming == 0) $pendingTasks[] = ['Schedule an upcoming event', 'bi-calendar-plus', 'create-event.php'];

// Recent activity feed (last 5 audit log entries by this user)
try {
    $activityStmt = $db->prepare("SELECT action, details, created_at FROM audit_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $activityStmt->execute([$_SESSION['user_id'] ?? '']);
    $activityFeed = $activityStmt->fetchAll();
} catch (Exception $e) {
    $activityFeed = [];
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($club['name']) ?> | Club Lead Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
        }
        .stat-card {
            border: none;
            border-radius: 20px;
            background: #ffffff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.06);
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .quick-action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px 12px;
            border-radius: 16px;
            border: 2px solid #e2e8f0;
            background: #fff;
            text-decoration: none;
            color: #334155;
            transition: all 0.2s ease;
            font-size: 0.82rem;
            font-weight: 600;
        }
        .quick-action-btn:hover {
            border-color: #10b981;
            background: #f0fdf4;
            color: #059669;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(16,185,129,0.12);
        }
        .quick-action-btn i { font-size: 1.5rem; }
        .task-item { padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
        .task-item:last-child { border-bottom: none; }
        .activity-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; flex-shrink: 0; }
        .countdown-badge { font-size: 0.7rem; background: #f0fdf4; color: #065f46; border: 1px solid #bbf7d0; border-radius: 20px; padding: 2px 10px; }
    </style>
</head>
<body>

<div class="d-flex min-vh-100">
    <!-- Universal Club Sidebar -->
    <?php require_once __DIR__ . '/../includes/club_sidebar.php'; ?>

    <!-- Main Content Body -->
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">

        <?php if (!empty($club['recruitment_open'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-3" role="alert">
            <i class="bi bi-megaphone-fill me-2"></i> <strong>Recruitment is OPEN!</strong> Your chapter is currently accepting new members.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Top Executive Welcome Banner -->
        <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4 text-white" style="background: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%);">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <span class="badge bg-white text-success rounded-pill px-3 py-1 fw-bold mb-2 small"><i class="bi bi-shield-check me-1"></i> OFFICIAL CHAPTER PORTAL</span>
                    <h2 class="fw-bold mb-2">Welcome back, <?= e($firstName) ?>! 👋</h2>
                    <p class="text-white-80 mb-0">Managing <strong><?= e($club['name']) ?></strong> campus activities, hackathons, and core student leadership roster.</p>
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <span style="background: rgba(255,255,255,0.22); border: 1px solid rgba(255,255,255,0.45); color: #fff; border-radius: 30px; padding: 5px 14px; font-size: 0.82rem; font-weight: 600; display:inline-flex; align-items:center; gap:6px;">
                            <i class="bi bi-calendar-check"></i> This Month: <?= $eventsThisMonth ?> events
                            <?php if ($eventsThisMonth > $eventsLastMonth): ?>
                                <span>↑</span>
                            <?php elseif ($eventsThisMonth < $eventsLastMonth): ?>
                                <span>↓</span>
                            <?php endif; ?>
                        </span>
                        <span style="background: rgba(255,255,255,0.22); border: 1px solid rgba(255,255,255,0.45); color: #fff; border-radius: 30px; padding: 5px 14px; font-size: 0.82rem; font-weight: 600; display:inline-flex; align-items:center; gap:6px;">
                            <i class="bi bi-bar-chart"></i> Last Month: <?= $eventsLastMonth ?> events
                        </span>
                    </div>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="create-event.php" class="btn btn-light rounded-pill px-4 py-2-5 fw-bold text-success shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Post New Event
                    </a>
                </div>
            </div>
        </div>

        <!-- 4 Quick Stat Cards -->
        <div class="row g-3 g-md-4 mb-4">
            <div class="col-6 col-xl-3">
                <div class="card stat-card p-3 p-md-4 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">Total Events</span>
                            <h3 class="fw-bold text-dark mb-0"><?= $totalEvents ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-calendar-event-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card stat-card p-3 p-md-4 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">Upcoming</span>
                            <h3 class="fw-bold text-success mb-0"><?= $totalUpcoming ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card stat-card p-3 p-md-4 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">Core Leaders</span>
                            <h3 class="fw-bold text-info mb-0"><?= $totalLeaders ?></h3>
                        </div>
                        <div class="stat-icon bg-info-subtle text-info">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-xl-3">
                <div class="card stat-card p-3 p-md-4 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">Moments</span>
                            <h3 class="fw-bold text-warning mb-0"><?= $totalGallery ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-images"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Action Shortcuts Row -->
        <div class="row g-3 mb-4">
            <div class="col-3 col-sm-3">
                <a href="create-event.php" class="quick-action-btn w-100">
                    <i class="bi bi-calendar-plus text-success"></i>
                    <span>Post Event</span>
                </a>
            </div>
            <div class="col-3 col-sm-3">
                <a href="profile.php" class="quick-action-btn w-100">
                    <i class="bi bi-person-plus text-primary"></i>
                    <span>Add Leader</span>
                </a>
            </div>
            <div class="col-3 col-sm-3">
                <a href="gallery.php" class="quick-action-btn w-100">
                    <i class="bi bi-camera text-warning"></i>
                    <span>Upload Photo</span>
                </a>
            </div>
            <div class="col-3 col-sm-3">
                <a href="../club-detail.html?id=<?= e($club['id']) ?>" target="_blank" class="quick-action-btn w-100">
                    <i class="bi bi-box-arrow-up-right text-info"></i>
                    <span>Public Page</span>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Events List Table -->
            <div class="col-lg-8">
                <div class="card p-3 p-md-4 border-0 shadow-sm rounded-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Recent Club Events</h5>
                            <span class="text-secondary small">Latest workshops & tech jams</span>
                        </div>
                        <a href="events.php" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-bold">View All &rarr;</a>
                    </div>

                    <?php if (empty($eventsList)): ?>
                        <div class="text-center py-4 text-muted bg-light rounded-3">
                            <i class="bi bi-calendar-x fs-2 mb-2 d-block text-secondary"></i>
                            No events added yet. Click <strong>Post New Event</strong> to publish your first chapter activity.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-secondary">
                                        <th>EVENT TITLE</th>
                                        <th>DATE</th>
                                        <th>STATUS</th>
                                        <th class="text-end">ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eventsList as $ev): ?>
                                        <tr>
                                            <td class="fw-bold text-dark">
                                                <div class="d-flex align-items-center gap-2">
                                                    <img src="<?= e($ev['banner'] ?: 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=200&auto=format&fit=crop') ?>" class="rounded-2" style="width: 40px; height: 40px; object-fit: cover;" alt="">
                                                    <span class="text-truncate" style="max-width: 200px;"><?= e($ev['title']) ?></span>
                                                </div>
                                            </td>
                                            <td class="small text-secondary"><?= date('d M Y', strtotime($ev['event_date'])) ?></td>
                                            <td>
                                                <?php if ($ev['status'] === 'completed'): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1 small">Concluded</span>
                                                <?php elseif ($ev['status'] === 'published'): ?>
                                                    <span class="badge bg-success-subtle text-success border rounded-pill px-2.5 py-1 small">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning-subtle text-warning border rounded-pill px-2.5 py-1 small"><?= ucfirst($ev['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="../event-detail.html?id=<?= e($ev['id']) ?>" target="_blank" class="btn btn-sm btn-light rounded-circle" title="View Public Event"><i class="bi bi-eye text-primary"></i></a>
                                                <a href="event-detail.php?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light rounded-circle ms-1" title="Manage Event"><i class="bi bi-pencil-square text-success"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar: Club Health + Tasks + Activity -->
            <div class="col-lg-4">

                <!-- Club Health Score Card -->
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-heart-pulse-fill text-danger me-2"></i>Club Health Score</h6>
                        <span class="badge bg-<?= $healthBadge[1] ?>-subtle text-<?= $healthBadge[1] ?> border rounded-pill px-2.5 py-1 small"><?= $healthBadge[0] ?></span>
                    </div>
                    <div class="d-flex align-items-end gap-2 mb-2">
                        <h2 class="fw-bold text-dark mb-0"><?= $healthScore ?></h2>
                        <span class="text-secondary mb-1">/ 100</span>
                    </div>
                    <div class="progress rounded-pill" style="height: 10px;">
                        <div class="progress-bar bg-<?= $healthBadge[1] ?>" role="progressbar" style="width: <?= $healthScore ?>%;" aria-valuenow="<?= $healthScore ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="text-secondary small mt-2 mb-0">Based on events, gallery, leadership & upcoming activities.</p>
                </div>

                <!-- Pending Tasks Checklist -->
                <?php if (!empty($pendingTasks)): ?>
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-check2-circle text-warning me-2"></i>Pending Tasks <span class="badge bg-warning-subtle text-warning ms-1"><?= count($pendingTasks) ?></span></h6>
                    <?php foreach ($pendingTasks as $task): ?>
                    <div class="task-item d-flex align-items-center gap-3">
                        <i class="bi <?= $task[1] ?> text-warning fs-5"></i>
                        <a href="<?= $task[2] ?>" class="small text-dark text-decoration-none flex-grow-1"><?= $task[0] ?></a>
                        <i class="bi bi-arrow-right text-secondary small"></i>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Secretariat Info -->
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
                    <h6 class="fw-bold text-dark mb-3">Chapter Secretariat Info</h6>
                    <ul class="list-unstyled text-secondary small mb-0">
                        <li class="mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-building text-primary fs-5"></i>
                            <div><strong class="d-block text-dark">Office Location</strong><?= e($club['office_location'] ?: 'Student Activity Center, UIT') ?></div>
                        </li>
                        <li class="mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-clock text-success fs-5"></i>
                            <div><strong class="d-block text-dark">Meeting Schedule</strong><?= e($club['meeting_time'] ?: 'Wednesdays 04:00 PM') ?></div>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-envelope text-info fs-5"></i>
                            <div><strong class="d-block text-dark">Official Email</strong><?= e($club['email'] ?: 'club@uit.edu') ?></div>
                        </li>
                    </ul>
                </div>

                <!-- Recent Activity Feed -->
                <?php if (!empty($activityFeed)): ?>
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-lightning-charge-fill text-primary me-2"></i>Recent Activity</h6>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($activityFeed as $act): ?>
                        <div class="d-flex align-items-start gap-2">
                            <div class="activity-dot mt-1"></div>
                            <div>
                                <div class="fw-semibold small text-dark font-monospace" style="font-size: 0.75rem;"><?= e($act['action']) ?></div>
                                <div class="text-secondary" style="font-size: 0.72rem;"><?= e(mb_substr($act['details'] ?? '', 0, 55)) ?><?= strlen($act['details'] ?? '') > 55 ? '...' : '' ?></div>
                                <div class="text-muted mt-1" style="font-size: 0.68rem;"><?= date('M j, g:i A', strtotime($act['created_at'])) ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Countdown timers for upcoming events
    function updateCountdowns() {
        document.querySelectorAll('[data-countdown]').forEach(el => {
            const target = new Date(el.dataset.countdown);
            const now = new Date();
            const diff = target - now;
            if (diff <= 0) { el.textContent = 'Today!'; return; }
            const d = Math.floor(diff / 86400000);
            const h = Math.floor((diff % 86400000) / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            el.textContent = d > 0 ? `${d}d ${h}h` : `${h}h ${m}m`;
        });
    }
    updateCountdowns();
    setInterval(updateCountdowns, 60000);
</script>
</body>
</html>
