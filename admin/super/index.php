<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_super_admin();

$db = Database::getConnection();

// System KPIs
$totalClubs     = $db->query("SELECT COUNT(*) FROM clubs WHERE deleted_at IS NULL")->fetchColumn();
$totalUsers     = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalEvents    = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalAuditLogs = $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();

// Category Distribution for Chart.js
$catStats = $db->query("
    SELECT cat.name, COUNT(c.id) as count
    FROM categories cat
    LEFT JOIN clubs c ON c.category_id = cat.id AND c.deleted_at IS NULL
    GROUP BY cat.id, cat.name
")->fetchAll();

$catNames = array_column($catStats, 'name');
$catCounts = array_column($catStats, 'count');

// Recent Security Logs
$recentAudit = $db->query("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 5")->fetchAll();

$pageTitle = "Super Admin Console | CCMS";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 px-0 admin-sidebar p-3">
            <div class="px-2 mb-3">
                <span class="small text-danger text-uppercase fw-bold"><i class="bi bi-shield-lock me-1"></i> Super Admin</span>
                <h6 class="fw-bold text-body mb-0 mt-1">Governance Portal</h6>
            </div>
            <nav class="d-flex flex-column">
                <a href="/admin/super/index.php" class="admin-nav-link active"><i class="bi bi-speedometer2"></i> System Analytics</a>
                <a href="/admin/super/clubs.php" class="admin-nav-link"><i class="bi bi-diagram-3"></i> Manage Clubs</a>
                <a href="/admin/super/users.php" class="admin-nav-link"><i class="bi bi-people"></i> Manage Accounts</a>
                <a href="/admin/super/audit-logs.php" class="admin-nav-link"><i class="bi bi-journal-text"></i> Security Audit Logs</a>
                <hr class="my-2 border-secondary-subtle">
                <a href="/admin/logout.php" class="admin-nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
            </nav>
        </div>

        <!-- Main Workspace -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Super Admin Governance Console</h2>
                    <p class="text-secondary small mb-0">System-wide institutional metrics, club onboarding, and audit governance.</p>
                </div>
                <a href="/admin/super/clubs.php" class="btn btn-primary rounded-pill px-4"><i class="bi bi-plus-lg me-1"></i> Onboard New Club</a>
            </div>

            <!-- KPI Cards -->
            <div class="row g-4 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card p-3 ccms-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary-subtle text-primary p-3 rounded-circle"><i class="bi bi-diagram-3-fill fs-3"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?= $totalClubs ?></h3>
                                <span class="small text-muted">Total Clubs</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 ccms-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success-subtle text-success p-3 rounded-circle"><i class="bi bi-person-badge-fill fs-3"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?= $totalUsers ?></h3>
                                <span class="small text-muted">System Accounts</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 ccms-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-info-subtle text-info p-3 rounded-circle"><i class="bi bi-calendar-event fs-3"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?= $totalEvents ?></h3>
                                <span class="small text-muted">Total Events</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card p-3 ccms-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-danger-subtle text-danger p-3 rounded-circle"><i class="bi bi-shield-check fs-3"></i></div>
                            <div>
                                <h3 class="fw-bold mb-0"><?= $totalAuditLogs ?></h3>
                                <span class="small text-muted">Audit Records</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Analytics Visualizations with Chart.js -->
            <div class="row g-4 mb-4">
                <div class="col-lg-6">
                    <div class="card p-4 ccms-card h-100">
                        <h5 class="fw-bold mb-3"><i class="bi bi-pie-chart text-primary me-2"></i> Club Distribution by Category</h5>
                        <div style="height: 280px; position: relative;">
                            <canvas id="categoryChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card p-4 ccms-card h-100">
                        <h5 class="fw-bold mb-3"><i class="bi bi-shield-exclamation text-primary me-2"></i> Recent Security Audit Trail</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle small mb-0">
                                <thead>
                                    <tr>
                                        <th>Actor</th>
                                        <th>Action</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($recentAudit)): ?>
                                        <tr><td colspan="3" class="text-center text-muted">No audit logs recorded yet.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($recentAudit as $log): ?>
                                            <tr>
                                                <td class="fw-semibold"><?= e($log['user_name'] ?: 'System') ?></td>
                                                <td><span class="badge bg-secondary-subtle text-secondary rounded-pill"><?= e($log['action']) ?></span></td>
                                                <td class="text-muted"><?= time_ago($log['created_at']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 text-end">
                            <a href="/admin/super/audit-logs.php" class="small fw-semibold text-primary text-decoration-none">View Full Audit Log <i class="bi bi-arrow-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Engine -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const ctx = document.getElementById('categoryChart').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($catNames) ?>,
            datasets: [{
                data: <?= json_encode($catCounts) ?>,
                backgroundColor: ['#4f46e5', '#06b6d4', '#10b981', '#f59e0b', '#ec4899', '#8b5cf6']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
