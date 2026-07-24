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

$upcomingEvents = $db->prepare("SELECT * FROM events WHERE club_id = ? ORDER BY event_date ASC LIMIT 5");
$upcomingEvents->execute([$club['id']]);
$eventsList = $upcomingEvents->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Dashboard | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { background: #f8fafc; }
        .admin-sidebar { width: 260px; min-height: 100vh; background: #0b0f19; color: #fff; }
        .admin-nav-link { color: rgba(255,255,255,0.7); padding: 12px 18px; border-radius: 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; font-weight: 500; }
        .admin-nav-link:hover, .admin-nav-link.active { background: #6366f1; color: #fff; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="admin-sidebar p-3 flex-shrink-0 d-none d-md-block">
        <div class="d-flex align-items-center gap-3 mb-4 p-2">
            <img src="/assets/United Logo.webp" style="height: 38px;">
            <div>
                <span class="fw-bold d-block lh-1">ClubHub</span>
                <span class="small text-white-50" style="font-size: 0.65rem;">CLUB PORTAL</span>
            </div>
        </div>

        <nav class="d-flex flex-column gap-2">
            <a href="/admin/dashboard.php" class="admin-nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
            <a href="/admin/profile.php" class="admin-nav-link"><i class="bi bi-gear"></i> Club & Roster Setup</a>
            <a href="/admin/events.php" class="admin-nav-link"><i class="bi bi-calendar-event"></i> Manage Events</a>
            <a href="/admin/gallery.php" class="admin-nav-link"><i class="bi bi-images"></i> Photo Gallery</a>
            <a href="/admin/recruitment.php" class="admin-nav-link"><i class="bi bi-person-plus"></i> Recruitment Drive</a>
            <a href="/admin/logout.php" class="admin-nav-link text-danger mt-4"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small"><?= htmlspecialchars($club['category_name']) ?></span>
                <h2 class="fw-bold mb-1"><?= htmlspecialchars($club['name']) ?></h2>
                <p class="text-secondary small mb-0"><?= htmlspecialchars($club['tagline'] ?? 'Student Club Dashboard') ?></p>
            </div>
            <div class="d-flex gap-2">
                <a href="/admin/profile.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                    <i class="bi bi-pencil-square me-1"></i> Edit Club Roster
                </a>
                <a href="/club-detail.html?id=<?= $club['id'] ?>" target="_blank" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Live Page
                </a>
            </div>
        </div>

        <!-- 3 Stat Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm rounded-4 text-center">
                    <i class="bi bi-people fs-1 text-primary mb-2"></i>
                    <h3 class="fw-bold mb-0"><?= $totalLeaders ?></h3>
                    <span class="small text-muted">Core Team Leaders Listed</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm rounded-4 text-center">
                    <i class="bi bi-calendar-event fs-1 text-success mb-2"></i>
                    <h3 class="fw-bold mb-0"><?= $totalEvents ?></h3>
                    <span class="small text-muted">Total Events Published</span>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card p-4 border-0 shadow-sm rounded-4 text-center">
                    <i class="bi bi-megaphone fs-1 text-warning mb-2"></i>
                    <h3 class="fw-bold mb-0"><?= $club['recruitment_open'] ? 'OPEN' : 'CLOSED' ?></h3>
                    <span class="small text-muted">Recruitment Status</span>
                </div>
            </div>
        </div>

        <!-- Events List -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Upcoming Events</h5>
                <a href="/admin/events.php" class="btn btn-sm btn-primary rounded-pill px-3"><i class="bi bi-plus-lg me-1"></i> Add Event</a>
            </div>

            <?php if (empty($eventsList)): ?>
                <div class="text-center py-5 text-muted bg-light rounded-4">
                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                    No upcoming events created yet. Click "Add Event" to publish a workshop or competition!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Event Title</th>
                                <th>Venue</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($eventsList as $ev): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($ev['title']) ?></td>
                                    <td><?= htmlspecialchars($ev['venue']) ?></td>
                                    <td><?= date('d M Y, h:i A', strtotime($ev['event_date'])) ?></td>
                                    <td><span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1"><?= ucfirst($ev['status']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
