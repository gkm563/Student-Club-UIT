<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login();

// Redirect Super Admin (Dean Sir) to Dean Dashboard (/admin/dashboard.php)
$userRole = get_current_user_role();
if ($userRole === 'super_admin') {
    header('Location: /admin/dashboard.php');
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
    <title>Club Lead Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', system-ui, sans-serif; }
        .admin-nav-link {
            color: rgba(255,255,255,0.65); padding: 11px 16px; border-radius: 10px;
            display: flex; align-items: center; gap: 12px; text-decoration: none;
            font-weight: 500; font-size: 0.88rem; transition: all 0.2s ease; margin-bottom: 2px;
        }
        .admin-nav-link i { font-size: 1.1rem; width: 20px; text-align: center; }
        .admin-nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateX(2px); }
        .admin-nav-link.active { background: linear-gradient(135deg, #10b981, #059669); color: #fff; box-shadow: 0 4px 12px rgba(16,185,129,0.3); }
        .stat-card { border: none; border-radius: 16px; transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; }
    </style>
</head>
<body>

<div class="d-flex min-vh-100">
    <!-- Sidebar -->
    <div class="bg-dark text-white p-3 p-md-4 d-flex flex-direction-column justify-content-between flex-shrink-0 shadow-lg" style="width: 260px; background: linear-gradient(180deg, #0f172a 0%, #064e3b 100%) !important;">
        <div>
            <!-- Club Brand Header -->
            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom border-white-10">
                <img src="<?= e($club['logo'] ?: '../assets/United Logo.webp') ?>" class="rounded-3 bg-white p-1" style="width: 44px; height: 44px; object-fit: cover;" alt="<?= e($club['name']) ?>">
                <div>
                    <h6 class="fw-bold mb-0 text-white text-truncate" style="max-width: 150px;"><?= e($club['short_name'] ?: $club['name']) ?></h6>
                    <span class="badge bg-success-subtle text-success border rounded-pill px-2 py-0-5 small" style="font-size: 0.65rem;">CLUB LEAD PORTAL</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="nav flex-column gap-1">
                <a href="/club/dashboard.php" class="admin-nav-link active">
                    <i class="bi bi-grid-fill"></i> Dashboard Overview
                </a>
                <a href="/admin/events.php" class="admin-nav-link">
                    <i class="bi bi-calendar-event"></i> Manage Events
                    <span class="badge bg-success rounded-pill ms-auto small"><?= $totalEvents ?></span>
                </a>
                <a href="/admin/create-event.php" class="admin-nav-link">
                    <i class="bi bi-plus-circle"></i> Create New Event
                </a>
                <a href="/admin/recruitment.php" class="admin-nav-link">
                    <i class="bi bi-person-badge"></i> Core Leadership
                </a>
                <a href="/admin/gallery.php" class="admin-nav-link">
                    <i class="bi bi-images"></i> Club Photo Gallery
                </a>
                <a href="/club-detail.html?id=<?= e($club['id']) ?>" target="_blank" class="admin-nav-link text-info">
                    <i class="bi bi-box-arrow-up-right"></i> Public Chapter Page
                </a>
            </nav>
        </div>

        <!-- Footer User Profile Deck -->
        <div class="pt-3 border-top border-white-10">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; font-size: 0.9rem;">
                    <?= strtoupper(substr($firstName, 0, 1)) ?>
                </div>
                <div class="text-truncate">
                    <span class="d-block fw-semibold text-white small text-truncate" style="max-width: 140px;"><?= e($adminName) ?></span>
                    <span class="small text-white-50 d-block" style="font-size: 0.72rem;"><?= e($_SESSION['email']) ?></span>
                </div>
            </div>
            <a href="/admin/logout.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold mt-1">
                <i class="bi bi-box-arrow-right me-1"></i> Sign Out
            </a>
        </div>
    </div>

    <!-- Main Content Body -->
    <div class="flex-grow-1 p-4 p-md-5 overflow-y-auto">
        <!-- Top Executive Welcome Banner -->
        <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4 text-white" style="background: linear-gradient(135deg, #059669 0%, #10b981 50%, #047857 100%);">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <span class="badge bg-white text-success rounded-pill px-3 py-1 fw-bold mb-2 small"><i class="bi bi-shield-check me-1"></i> OFFICIAL CHAPTER PORTAL</span>
                    <h2 class="fw-bold mb-2">Welcome back, <?= e($firstName) ?>! 👋</h2>
                    <p class="text-white-80 mb-0">Managing <strong><?= e($club['name']) ?></strong> official campus activities, hackathons, and member recruitments.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="/admin/create-event.php" class="btn btn-light rounded-pill px-4 py-2-5 fw-bold text-success shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Post New Event
                    </a>
                </div>
            </div>
        </div>

        <!-- 4 Quick Stat Cards -->
        <div class="row g-4 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card p-4 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">Total Chapter Events</span>
                            <h3 class="fw-bold text-dark mb-0"><?= $totalEvents ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-calendar-event-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card p-4 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">Upcoming Events</span>
                            <h3 class="fw-bold text-success mb-0"><?= $totalUpcoming ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card p-4 bg-white shadow-sm">
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
            <div class="col-sm-6 col-xl-3">
                <div class="card stat-card p-4 bg-white shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">Gallery Moments</span>
                            <h3 class="fw-bold text-warning mb-0"><?= $totalGallery ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-images"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Recent Events List Table -->
            <div class="col-lg-8">
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Recent Club Events</h5>
                            <span class="text-secondary small">Latest workshops and competitions published</span>
                        </div>
                        <a href="/admin/events.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">View All &rarr;</a>
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
                                                    <span><?= e($ev['title']) ?></span>
                                                </div>
                                            </td>
                                            <td class="small text-secondary"><?= date('d M Y, h:i A', strtotime($ev['event_date'])) ?></td>
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
                                                <a href="/event-detail.html?id=<?= e($ev['id']) ?>" target="_blank" class="btn btn-sm btn-light rounded-circle" title="View Public Event"><i class="bi bi-eye text-primary"></i></a>
                                                <a href="/admin/event-detail.php?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-light rounded-circle ms-1" title="Manage Event"><i class="bi bi-pencil-square text-success"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar Info & Quick Actions -->
            <div class="col-lg-4">
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
                    <h5 class="fw-bold text-dark mb-3">Chapter Secretariat Info</h5>
                    <ul class="list-unstyled space-y-3 text-secondary small mb-0">
                        <li class="mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-building text-primary fs-5"></i>
                            <div>
                                <strong class="d-block text-dark">Office Location</strong>
                                <span><?= e($club['office_location'] ?: 'Student Activity Center, UIT') ?></span>
                            </div>
                        </li>
                        <li class="mb-3 d-flex align-items-center gap-2">
                            <i class="bi bi-clock text-success fs-5"></i>
                            <div>
                                <strong class="d-block text-dark">Meeting Schedule</strong>
                                <span><?= e($club['meeting_time'] ?: 'Wednesdays 04:00 PM') ?></span>
                            </div>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i class="bi bi-envelope text-info fs-5"></i>
                            <div>
                                <strong class="d-block text-dark">Official Email</strong>
                                <span><?= e($club['email'] ?: 'club@uit.edu') ?></span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
