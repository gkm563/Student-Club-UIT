<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: /admin/login.php');
    exit;
}

$db = Database::getConnection();

// Quick Stats
$totalClubs = $db->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
$activeClubs = $db->query("SELECT COUNT(*) FROM clubs WHERE status = 'active'")->fetchColumn();
$totalEvents = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalLeaders = $db->query("SELECT COUNT(*) FROM leadership")->fetchColumn();

// Recent registered clubs
$recentClubs = $db->query("
    SELECT c.*, cat.name as category_name 
    FROM clubs c 
    JOIN categories cat ON c.category_id = cat.id 
    ORDER BY c.created_at DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Sir Portal - Overview | ClubHub UIT</title>
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
                <span class="small text-white-50" style="font-size: 0.65rem;">DEAN PORTAL</span>
            </div>
        </div>

        <nav class="d-flex flex-column gap-2">
            <a href="/admin/super/index.php" class="admin-nav-link active"><i class="bi bi-speedometer2"></i> Overview</a>
            <a href="/admin/super/clubs.php" class="admin-nav-link"><i class="bi bi-trophy"></i> Manage Clubs</a>
            <a href="/admin/super/users.php" class="admin-nav-link"><i class="bi bi-shield-lock"></i> Dean Profile</a>
            <a href="/admin/logout.php" class="admin-nav-link text-danger mt-4"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">WELCOME BACK</span>
                <h2 class="fw-bold mb-1"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Dean Sir') ?></h2>
                <p class="text-secondary small mb-0">Overview of all student clubs, annual leadership rosters, and campus events.</p>
            </div>
            <a href="/admin/super/clubs.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Create New Club
            </a>
        </div>

        <!-- 4 Stats Cards -->
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="card p-4 border-0 shadow-sm rounded-4 text-center">
                    <i class="bi bi-trophy fs-1 text-primary mb-2"></i>
                    <h3 class="fw-bold mb-0"><?= $totalClubs ?></h3>
                    <span class="small text-muted">Total Registered Clubs</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-4 border-0 shadow-sm rounded-4 text-center">
                    <i class="bi bi-check-circle fs-1 text-success mb-2"></i>
                    <h3 class="fw-bold mb-0"><?= $activeClubs ?></h3>
                    <span class="small text-muted">Active Campus Clubs</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-4 border-0 shadow-sm rounded-4 text-center">
                    <i class="bi bi-people fs-1 text-info mb-2"></i>
                    <h3 class="fw-bold mb-0"><?= $totalLeaders ?></h3>
                    <span class="small text-muted">Core Team Leaders</span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-4 border-0 shadow-sm rounded-4 text-center">
                    <i class="bi bi-calendar-event fs-1 text-warning mb-2"></i>
                    <h3 class="fw-bold mb-0"><?= $totalEvents ?></h3>
                    <span class="small text-muted">Total Campus Events</span>
                </div>
            </div>
        </div>

        <!-- Recent Registered Clubs -->
        <div class="card border-0 shadow-sm rounded-4 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">Recently Created Clubs</h5>
                <a href="/admin/super/clubs.php" class="text-decoration-none small text-primary fw-semibold">View All &rarr;</a>
            </div>

            <?php if (empty($recentClubs)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-folder-plus fs-1 d-block mb-2"></i>
                    No clubs registered yet. Go to <a href="/admin/super/clubs.php" class="fw-bold text-primary">Manage Clubs</a> to add your first student club!
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Club Name</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentClubs as $rc): ?>
                                <tr>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($rc['name']) ?></td>
                                    <td><span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1"><?= htmlspecialchars($rc['category_name']) ?></span></td>
                                    <td><span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1"><?= ucfirst($rc['status']) ?></span></td>
                                    <td><a href="/admin/super/clubs.php" class="btn btn-sm btn-outline-primary rounded-pill">Manage</a></td>
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
