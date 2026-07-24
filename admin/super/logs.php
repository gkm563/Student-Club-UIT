<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: /admin/dean-login.php');
    exit;
}

$db = Database::getConnection();

// Fetch Audit Logs
$logs = $db->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs & Security | Dean Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { background: #f8fafc; }
        .admin-sidebar { width: 260px; min-height: 100vh; background: #0b0f19; color: #fff; }
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
            <a href="/admin/super/index.php" class="admin-nav-link"><i class="bi bi-speedometer2"></i> Overview</a>
            <a href="/admin/super/clubs.php" class="admin-nav-link"><i class="bi bi-trophy"></i> Manage Clubs</a>
            <a href="/admin/super/categories.php" class="admin-nav-link"><i class="bi bi-tags"></i> Categories</a>
            <a href="/admin/super/logs.php" class="admin-nav-link active"><i class="bi bi-journal-text"></i> Audit Logs</a>
            <a href="/admin/super/messages.php" class="admin-nav-link"><i class="bi bi-envelope"></i> Messages</a>
            <a href="/admin/super/users.php" class="admin-nav-link"><i class="bi bi-shield-lock"></i> Dean Profile</a>
            <a href="/admin/logout.php" class="admin-nav-link text-danger mt-4"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4 p-md-5">
        <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">SECURITY & AUDIT</span>
            <h2 class="fw-bold mb-1">System Audit & Activity Logs</h2>
            <p class="text-secondary small mb-0">Track real-time security events, administrator logins, and activity changes.</p>
        </div>

        <!-- Audit Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Action</th>
                            <th>User Name</th>
                            <th>Details</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-5 text-muted">
                                    <i class="bi bi-journal-check fs-1 d-block mb-2"></i>
                                    No audit events logged yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1"><?= htmlspecialchars($log['action']) ?></span></td>
                                    <td><strong class="text-dark"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></strong></td>
                                    <td><span class="small text-secondary"><?= htmlspecialchars($log['details'] ?? '') ?></span></td>
                                    <td><span class="small text-muted"><i class="bi bi-clock me-1"></i> <?= date('d M Y, h:i A', strtotime($log['created_at'])) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
