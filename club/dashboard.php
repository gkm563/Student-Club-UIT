<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login('../club-login.php');

// Redirect Super Admin (Dean Sir) to Dean Dashboard
$userRole = get_current_user_role();
if ($userRole === 'super_admin') {
    header('Location: ../admin/super/index.php');
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
        <title>No Assigned Club | USC UIT</title>
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

// System-Wide 100% Dynamic 12-Criteria Club Profile Setup Health Calculation
$profileHealthData = calculate_club_profile_health($club, $db);
$healthScore = $profileHealthData['score'];
$healthStatus = $profileHealthData['status'];
$healthBadgeClass = $profileHealthData['badge_class'];
$healthBadge = [$healthStatus, $healthBadgeClass];

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
    <title><?= e($club['name']) ?> | Executive Lead Portal | USC UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f8fafc;
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
        }

        /* Hero Banner Glassmorphism */
        .club-hero-card {
            background: linear-gradient(135deg, #064e3b 0%, #047857 45%, #059669 80%, #10b981 100%);
            border-radius: 24px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.12);
            box-shadow: 0 16px 40px rgba(5,150,105,0.22);
        }
        .club-hero-card::after {
            content: '';
            position: absolute;
            top: 0; right: 0; bottom: 0; left: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.18), transparent 60%);
            pointer-events: none;
        }

        /* Stat Card Aesthetics */
        .stat-card-pro {
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: #ffffff;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        .stat-card-pro:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 32px rgba(0,0,0,0.08) !important;
            border-color: rgba(16,185,129,0.3);
        }
        .stat-card-icon-wrapper {
            width: 54px;
            height: 54px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.45rem;
            transition: transform 0.25s ease;
        }
        .stat-card-pro:hover .stat-card-icon-wrapper {
            transform: scale(1.08) rotate(3deg);
        }

        /* Quick Action Shortcut Tile */
        .action-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 18px 14px;
            border-radius: 18px;
            background: #ffffff;
            border: 1.5px solid #e2e8f0;
            text-decoration: none;
            color: #1e293b;
            font-size: 0.84rem;
            font-weight: 700;
            transition: all 0.22s ease;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
        }
        .action-tile:hover {
            border-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #ffffff 100%);
            color: #047857;
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(16,185,129,0.14);
        }
        .action-tile-icon {
            width: 44px;
            height: 44px;
            border-radius: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            margin-bottom: 8px;
            transition: background 0.2s ease;
        }

        /* Task Checkbox List */
        .task-row {
            padding: 12px 14px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            margin-bottom: 8px;
        }
        .task-row:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            transform: translateX(3px);
        }

        /* Activity Feed Timeline */
        .activity-timeline-item {
            position: relative;
            padding-left: 24px;
            border-left: 2px dashed #cbd5e1;
            padding-bottom: 16px;
        }
        .activity-timeline-item:last-child {
            padding-bottom: 0;
            border-left-color: transparent;
        }
        .activity-timeline-dot {
            position: absolute;
            left: -6px;
            top: 2px;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #10b981;
            box-shadow: 0 0 0 3px rgba(16,185,129,0.25);
        }

        .table-custom-hover tbody tr {
            transition: background-color 0.15s ease;
        }
        .table-custom-hover tbody tr:hover {
            background-color: #f1f5f9 !important;
        }
    </style>
</head>
<body>

<div class="d-flex min-vh-100">
    <!-- Universal Club Sidebar -->
    <?php require_once __DIR__ . '/../includes/club_sidebar.php'; ?>

    <!-- Main Content Body -->
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">

        <!-- Recruitment Alert Banner -->
        <?php if (!empty($club['recruitment_open'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4 d-flex align-items-center gap-3 p-3 bg-success bg-opacity-10 text-success" role="alert">
            <div class="p-2 bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; flex-shrink: 0;">
                <i class="bi bi-megaphone-fill fs-5"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold">Student Recruitment Drive is Currently LIVE!</div>
                <div class="small opacity-85">Your chapter is actively receiving student membership applications. Manage settings in <a href="recruitment.php" class="fw-bold text-success text-decoration-underline">Recruitment Setup &rarr;</a></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Executive Hero Banner -->
        <div class="card p-4 p-md-5 club-hero-card mb-4 text-white">
            <div class="row align-items-center g-4">
                <div class="col-lg-8 position-relative z-1">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-white bg-opacity-20 border border-white-10 text-white rounded-pill px-3 py-1-5 fw-bold small text-uppercase" style="backdrop-filter: blur(8px);">
                            <i class="bi bi-patch-check-fill text-warning me-1"></i> Official Chapter Portal
                        </span>
                        <span class="badge bg-black bg-opacity-25 text-white rounded-pill px-3 py-1-5 fw-semibold small">
                            <?= e($club['category_name'] ?? 'Student Club') ?>
                        </span>
                    </div>

                    <h1 class="fw-bold text-white mb-2 display-6">Welcome back, <?= e($firstName) ?>! 👋</h1>
                    <p class="text-white-80 mb-4 fs-6" style="max-width: 620px; line-height: 1.6;">
                        Directing <strong><?= e($club['name']) ?></strong> campus operations, upcoming workshops, media recaps, and annual student leadership.
                    </p>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill" style="background: rgba(0,0,0,0.22); border: 1px solid rgba(255,255,255,0.2); font-size: 0.82rem;">
                            <i class="bi bi-calendar-event text-warning"></i>
                            <span>This Month: <strong><?= $eventsThisMonth ?> events</strong></span>
                            <?php if ($eventsThisMonth > $eventsLastMonth): ?>
                                <span class="badge bg-success-subtle text-success rounded-pill px-1.5 py-0.5" style="font-size:0.68rem;">+<?= $eventsThisMonth - $eventsLastMonth ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex align-items-center gap-2 px-3 py-2 rounded-pill" style="background: rgba(0,0,0,0.22); border: 1px solid rgba(255,255,255,0.2); font-size: 0.82rem;">
                            <i class="bi bi-person-badge text-info"></i>
                            <span>Roster Leaders: <strong><?= $totalLeaders ?> members</strong></span>
                        </div>

                        <a href="../club-detail.html?id=<?= e($club['id']) ?>" target="_blank" class="btn btn-sm btn-outline-light rounded-pill px-3 py-2 font-monospace fw-bold ms-auto d-none d-md-inline-block">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Preview Chapter Page
                        </a>
                    </div>
                </div>

                <div class="col-lg-4 text-lg-end position-relative z-1">
                    <div class="bg-white bg-opacity-10 backdrop-blur rounded-4 p-4 border border-white-10 text-start text-lg-start">
                        <div class="text-white-50 small font-monospace text-uppercase fw-bold mb-1">CHAPTER HEALTH SCORE</div>
                        <div class="d-flex align-items-baseline gap-2 mb-2">
                            <span class="display-5 fw-bold text-white mb-0"><?= $healthScore ?></span>
                            <span class="text-white-50 fs-5">/ 100</span>
                            <span class="badge bg-<?= $healthBadgeClass ?> text-white ms-auto rounded-pill px-3 py-1 fw-bold"><?= $healthStatus ?></span>
                        </div>
                        <div class="progress bg-black bg-opacity-30 rounded-pill" style="height: 8px;">
                            <div class="progress-bar bg-<?= $healthBadgeClass ?>" role="progressbar" style="width: <?= $healthScore ?>%;" aria-valuenow="<?= $healthScore ?>"></div>
                        </div>
                        <span class="d-block text-white-50 small mt-2" style="font-size:0.75rem;">12-Criteria Profile Setup: <?= $profileHealthData['filled_count'] ?>/<?= $profileHealthData['total_fields'] ?> completed. <a href="profile.php" class="text-white text-decoration-underline fw-bold">Update Profile</a></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 Key Performance Metrics Grid -->
        <div class="row g-3 g-md-4 mb-4">
            <!-- Metric 1: Total Events -->
            <div class="col-6 col-xl-3">
                <div class="card stat-card-pro p-3.5 p-md-4 shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-bold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">TOTAL EVENTS</span>
                            <h2 class="fw-bold text-dark mb-0"><?= $totalEvents ?></h2>
                            <span class="text-success small fw-semibold mt-1 d-inline-block"><i class="bi bi-check-circle-fill me-1"></i><?= $totalCompleted ?> Concluded</span>
                        </div>
                        <div class="stat-card-icon-wrapper bg-primary-subtle text-primary">
                            <i class="bi bi-calendar-event-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metric 2: Upcoming Activities -->
            <div class="col-6 col-xl-3">
                <div class="card stat-card-pro p-3.5 p-md-4 shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-bold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">UPCOMING EVENTS</span>
                            <h2 class="fw-bold text-success mb-0"><?= $totalUpcoming ?></h2>
                            <span class="text-secondary small mt-1 d-inline-block">Scheduled live</span>
                        </div>
                        <div class="stat-card-icon-wrapper bg-success-subtle text-success">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metric 3: Core Roster Leaders -->
            <div class="col-6 col-xl-3">
                <div class="card stat-card-pro p-3.5 p-md-4 shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-bold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">CORE LEADERS</span>
                            <h2 class="fw-bold text-info mb-0"><?= $totalLeaders ?></h2>
                            <span class="text-info small fw-semibold mt-1 d-inline-block">Executive Roster</span>
                        </div>
                        <div class="stat-card-icon-wrapper bg-info-subtle text-info">
                            <i class="bi bi-people-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Metric 4: Gallery Photos -->
            <div class="col-6 col-xl-3">
                <div class="card stat-card-pro p-3.5 p-md-4 shadow-xs h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-bold text-uppercase d-block mb-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">MOMENTS & PHOTOS</span>
                            <h2 class="fw-bold text-warning mb-0"><?= $totalGallery ?></h2>
                            <span class="text-warning small fw-semibold mt-1 d-inline-block">Official Gallery</span>
                        </div>
                        <div class="stat-card-icon-wrapper bg-warning-subtle text-warning">
                            <i class="bi bi-images"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Action Shortcuts Grid (5 Action Buttons) -->
        <div class="row g-2 g-md-3 mb-4">
            <div class="col-6 col-sm-4 col-md-2.4 col-lg">
                <a href="create-event.php" class="action-tile">
                    <div class="action-tile-icon bg-success-subtle text-success">
                        <i class="bi bi-calendar-plus-fill"></i>
                    </div>
                    <span>Post Event</span>
                </a>
            </div>
            <div class="col-6 col-sm-4 col-md-2.4 col-lg">
                <a href="profile.php" class="action-tile">
                    <div class="action-tile-icon bg-primary-subtle text-primary">
                        <i class="bi bi-person-plus-fill"></i>
                    </div>
                    <span>Add Leader</span>
                </a>
            </div>
            <div class="col-6 col-sm-4 col-md-2.4 col-lg">
                <a href="gallery.php" class="action-tile">
                    <div class="action-tile-icon bg-warning-subtle text-warning">
                        <i class="bi bi-cloud-arrow-up-fill"></i>
                    </div>
                    <span>Upload Photo</span>
                </a>
            </div>
            <div class="col-6 col-sm-4 col-md-2.4 col-lg">
                <a href="recruitment.php" class="action-tile">
                    <div class="action-tile-icon bg-info-subtle text-info">
                        <i class="bi bi-mortarboard-fill"></i>
                    </div>
                    <span>Recruitment</span>
                </a>
            </div>
            <div class="col-12 col-sm-4 col-md-2.4 col-lg">
                <a href="../club-detail.html?id=<?= e($club['id']) ?>" target="_blank" class="action-tile">
                    <div class="action-tile-icon bg-dark-subtle text-dark">
                        <i class="bi bi-globe"></i>
                    </div>
                    <span>Public Page</span>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left Column: Recent Events Directory Table -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar-range text-primary me-2"></i>Recent Chapter Events</h5>
                            <span class="text-secondary small">Manage workshops, hackathons & coding jams</span>
                        </div>
                        <a href="events.php" class="btn btn-sm btn-primary rounded-pill px-3.5 py-1.5 fw-bold shadow-xs">
                            View All Events <i class="bi bi-arrow-right ms-1"></i>
                        </a>
                    </div>

                    <?php if (empty($eventsList)): ?>
                        <div class="text-center py-5 text-muted bg-light rounded-4 border">
                            <i class="bi bi-calendar-x fs-1 mb-2 d-block text-secondary"></i>
                            <h6 class="fw-bold text-dark">No Events Published Yet</h6>
                            <p class="small text-secondary mb-3">Publish your first workshop or competition to display on your chapter portal.</p>
                            <a href="create-event.php" class="btn btn-sm btn-primary rounded-pill px-4 py-2 fw-bold text-white shadow-xs">
                                <i class="bi bi-plus-lg me-1"></i> Post New Event
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-custom-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr class="small text-secondary">
                                        <th style="border-top-left-radius: 10px;">EVENT TITLE</th>
                                        <th>DATE & TIME</th>
                                        <th>STATUS</th>
                                        <th class="text-end" style="border-top-right-radius: 10px;">ACTIONS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($eventsList as $ev): ?>
                                        <tr>
                                            <td class="fw-bold text-dark">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?= e($ev['banner'] ?: 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=200&auto=format&fit=crop') ?>" 
                                                         class="rounded-3 border shadow-xs" style="width: 48px; height: 38px; object-fit: cover;" alt="">
                                                    <div class="overflow-hidden me-2">
                                                        <a href="event-detail.php?id=<?= e($ev['id']) ?>" class="text-decoration-none text-dark fw-bold text-truncate d-block" style="max-width: 220px;" title="<?= e($ev['title']) ?>">
                                                            <?= e($ev['title']) ?>
                                                        </a>
                                                        <span class="small text-muted d-block" style="font-size: 0.73rem;"><i class="bi bi-geo-alt me-1 text-danger"></i><?= e($ev['venue']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="small text-secondary">
                                                <div class="fw-semibold text-dark"><?= date('d M Y', strtotime($ev['event_date'])) ?></div>
                                                <span class="text-muted" style="font-size:0.73rem;"><?= date('h:i A', strtotime($ev['event_date'])) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($ev['status'] === 'completed'): ?>
                                                    <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-2.5 py-1 small"><i class="bi bi-check2-all me-1"></i>Completed</span>
                                                <?php elseif ($ev['status'] === 'upcoming' || $ev['status'] === 'published'): ?>
                                                    <span class="badge bg-success-subtle text-success border rounded-pill px-2.5 py-1 small"><i class="bi bi-circle-fill text-success me-1" style="font-size:0.5rem;"></i>Active</span>
                                                <?php elseif ($ev['status'] === 'ongoing'): ?>
                                                    <span class="badge bg-warning-subtle text-warning border rounded-pill px-2.5 py-1 small"><i class="bi bi-lightning-fill me-1"></i>Live Now</span>
                                                <?php else: ?>
                                                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small"><?= ucfirst($ev['status']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="../event-detail.html?id=<?= e($ev['id']) ?>" target="_blank" class="btn btn-sm btn-light rounded-circle shadow-xs" title="View Public Page">
                                                    <i class="bi bi-eye text-primary"></i>
                                                </a>
                                                <a href="event-detail.php?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light rounded-circle shadow-xs ms-1" title="Edit Event Details">
                                                    <i class="bi bi-pencil-square text-success"></i>
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

            <!-- Right Column: Pending Tasks & Activity Log -->
            <div class="col-lg-4">

                <!-- Pending Tasks Panel -->
                <?php if (!empty($pendingTasks)): ?>
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-check2-square text-warning me-2"></i>Pending Setup Tasks</h6>
                        <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 small fw-bold"><?= count($pendingTasks) ?> pending</span>
                    </div>
                    <?php foreach ($pendingTasks as $task): ?>
                        <div class="task-row d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                <i class="bi <?= $task[1] ?> text-warning fs-5 flex-shrink-0"></i>
                                <span class="small fw-semibold text-dark text-truncate"><?= $task[0] ?></span>
                            </div>
                            <a href="<?= $task[2] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1 text-nowrap small ms-2">
                                Fix <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Secretariat Location Card -->
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-building-gear text-primary me-2"></i>Chapter Secretariat Info</h6>
                    <div class="d-flex flex-column gap-3 text-secondary small">
                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2.5 bg-primary-subtle text-primary rounded-3">
                                <i class="bi bi-geo-alt-fill fs-5"></i>
                            </div>
                            <div>
                                <strong class="d-block text-dark">Office Location</strong>
                                <?= e($club['office_location'] ?: 'Student Activity Center, UIT') ?>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2.5 bg-success-subtle text-success rounded-3">
                                <i class="bi bi-clock-fill fs-5"></i>
                            </div>
                            <div>
                                <strong class="d-block text-dark">Regular Meetings</strong>
                                <?= e($club['meeting_time'] ?: 'Wednesdays 04:00 PM') ?>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <div class="p-2.5 bg-info-subtle text-info rounded-3">
                                <i class="bi bi-envelope-at-fill fs-5"></i>
                            </div>
                            <div>
                                <strong class="d-block text-dark">Official Chapter Email</strong>
                                <?= e($club['email'] ?: 'club@uit.edu') ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Activity Feed Log -->
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
                    <h6 class="fw-bold text-dark mb-3"><i class="bi bi-activity text-primary me-2"></i>Recent Activity Feed</h6>
                    <?php if (empty($activityFeed)): ?>
                        <div class="text-muted small py-3 text-center">No recent activity logged for your account.</div>
                    <?php else: ?>
                        <div class="d-flex flex-column pt-1">
                            <?php foreach ($activityFeed as $act): ?>
                                <div class="activity-timeline-item">
                                    <div class="activity-timeline-dot"></div>
                                    <div class="fw-bold small text-dark font-monospace" style="font-size: 0.78rem;"><?= e($act['action']) ?></div>
                                    <div class="text-secondary small mt-0.5" style="font-size: 0.74rem;"><?= e(mb_substr($act['details'] ?? '', 0, 60)) ?></div>
                                    <span class="text-muted d-block mt-1" style="font-size: 0.68rem;"><?= date('M j, g:i A', strtotime($act['created_at'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
