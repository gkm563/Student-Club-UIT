<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_club_admin();

$role = get_current_user_role();
if ($role === 'super_admin') {
    header("Location: /admin/super/index.php");
    exit;
}

$db = Database::getConnection();
$clubId = get_assigned_club_id();

$club = null;
if ($clubId) {
    $stmt = $db->prepare("SELECT c.*, cat.name AS category_name FROM clubs c JOIN categories cat ON c.category_id = cat.id WHERE c.id = ?");
    $stmt->execute([$clubId]);
    $club = $stmt->fetch();
}

if (!$club) {
    // If no club bound, fetch first club as fallback for demo
    $club = $db->query("SELECT c.*, cat.name AS category_name FROM clubs c JOIN categories cat ON c.category_id = cat.id ORDER BY c.created_at ASC LIMIT 1")->fetch();
    $clubId = $club['id'] ?? null;
}

// Fetch Metrics for this club
$eventsCount     = $db->query("SELECT COUNT(*) FROM events WHERE club_id = '{$clubId}'")->fetchColumn();
$actCount        = $db->query("SELECT COUNT(*) FROM activities WHERE club_id = '{$clubId}'")->fetchColumn();
$leadersCount    = $db->query("SELECT COUNT(*) FROM leadership WHERE club_id = '{$clubId}'")->fetchColumn();
$achievementsCount = $db->query("SELECT COUNT(*) FROM achievements WHERE club_id = '{$clubId}'")->fetchColumn();

// Fetch Recent Events
$recentEvents = $db->query("SELECT * FROM events WHERE club_id = '{$clubId}' ORDER BY event_date DESC LIMIT 5")->fetchAll();

$pageTitle = "Club Admin Dashboard | CCMS";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 px-0 admin-sidebar p-3">
            <div class="px-2 mb-3">
                <span class="small text-muted text-uppercase fw-bold">Club Management</span>
                <h6 class="fw-bold text-primary mb-0 mt-1"><?= e($club['short_name'] ?? 'Club Admin') ?></h6>
            </div>
            <nav class="d-flex flex-column">
                <a href="/admin/dashboard.php" class="admin-nav-link active"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="/admin/profile.php" class="admin-nav-link"><i class="bi bi-pencil-square"></i> Edit Profile</a>
                <a href="/admin/events.php" class="admin-nav-link"><i class="bi bi-calendar-event"></i> Manage Events</a>
                <a href="/admin/activities.php" class="admin-nav-link"><i class="bi bi-newspaper"></i> Activity Posts</a>
                <a href="/admin/members.php" class="admin-nav-link"><i class="bi bi-people"></i> Roster & Officers</a>
                <hr class="my-2 border-secondary-subtle">
                <a href="/admin/logout.php" class="admin-nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
            </nav>
        </div>

        <!-- Main Workspace -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Dashboard Overview</h2>
                    <p class="text-secondary small mb-0">Managing: <strong><?= e($club['name']) ?></strong> (<?= e($club['category_name']) ?>)</p>
                </div>
                <a href="/club-detail.php?slug=<?= e($club['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                    <i class="bi bi-eye me-1"></i> Preview Live Profile
                </a>
            </div>

            <!-- Metrics Row -->
            <div class="row g-4 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card p-3 ccms-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary-subtle text-primary p-3 rounded-circle"><i class="bi bi-calendar-event fs-3"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?= $eventsCount ?></h3>
                                <span class="small text-muted">Events</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 ccms-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success-subtle text-success p-3 rounded-circle"><i class="bi bi-newspaper fs-3"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?= $actCount ?></h3>
                                <span class="small text-muted">Activities</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 ccms-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-info-subtle text-info p-3 rounded-circle"><i class="bi bi-people fs-3"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?= $leadersCount ?></h3>
                                <span class="small text-muted">Roster Members</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 ccms-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-warning-subtle text-warning p-3 rounded-circle"><i class="bi bi-trophy fs-3"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?= $achievementsCount ?></h3>
                                <span class="small text-muted">Achievements</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action & Recent Events -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card p-4 ccms-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0"><i class="bi bi-calendar-check me-2 text-primary"></i> Scheduled Events</h5>
                            <a href="/admin/events.php" class="btn btn-sm btn-primary rounded-pill"><i class="bi bi-plus-lg me-1"></i> Add Event</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle small mb-0">
                                <thead>
                                    <tr>
                                        <th>Event Title</th>
                                        <th>Date</th>
                                        <th>Venue</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentEvents)): ?>
                                        <tr><td colspan="4" class="text-center text-muted py-3">No events recorded.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentEvents as $ev): ?>
                                            <tr>
                                                <td class="fw-semibold"><?= e($ev['title']) ?></td>
                                                <td><?= e(date('M j, Y', strtotime($ev['event_date']))) ?></td>
                                                <td><?= e($ev['venue']) ?></td>
                                                <td><?= get_status_badge($ev['status']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card p-4 ccms-card">
                        <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge text-primary me-2"></i> Quick Actions</h5>
                        <div class="d-flex flex-column gap-2">
                            <a href="/admin/profile.php" class="btn btn-outline-primary text-start rounded-3 p-3">
                                <i class="bi bi-pencil-square me-2"></i> Update Profile Details
                            </a>
                            <a href="/admin/events.php" class="btn btn-outline-primary text-start rounded-3 p-3">
                                <i class="bi bi-calendar-plus me-2"></i> Post New Event
                            </a>
                            <a href="/admin/activities.php" class="btn btn-outline-primary text-start rounded-3 p-3">
                                <i class="bi bi-file-earmark-post me-2"></i> Create Activity Blog Post
                            </a>
                            <a href="/admin/members.php" class="btn btn-outline-primary text-start rounded-3 p-3">
                                <i class="bi bi-person-plus me-2"></i> Manage Officer Roster
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
