<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_super_admin();

$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 100");
$stmt->execute();
$logs = $stmt->fetchAll();

$pageTitle = "Security Audit Logs | Super Admin";
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
                <a href="/admin/super/index.php" class="admin-nav-link"><i class="bi bi-speedometer2"></i> System Analytics</a>
                <a href="/admin/super/clubs.php" class="admin-nav-link"><i class="bi bi-diagram-3"></i> Manage Clubs</a>
                <a href="/admin/super/users.php" class="admin-nav-link"><i class="bi bi-people"></i> Manage Accounts</a>
                <a href="/admin/super/audit-logs.php" class="admin-nav-link active"><i class="bi bi-journal-text"></i> Security Audit Logs</a>
                <hr class="my-2 border-secondary-subtle">
                <a href="/admin/logout.php" class="admin-nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Security Audit Governance Log</h2>
                    <p class="text-secondary small mb-0">Immutable record of administrative actions, authentication attempts, and profile modifications.</p>
                </div>
            </div>

            <div class="card p-4 ccms-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>Actor / User</th>
                                <th>Action Event</th>
                                <th>Target Type</th>
                                <th>Details</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No audit logs recorded yet.</td></tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td class="text-muted"><?= e(date('M j, Y - g:i:s A', strtotime($log['created_at']))) ?></td>
                                        <td class="fw-semibold"><?= e($log['user_name'] ?: 'System') ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary rounded-pill font-monospace"><?= e($log['action']) ?></span></td>
                                        <td><?= e(ucfirst($log['target_type'] ?? 'N/A')) ?></td>
                                        <td><?= e($log['details'] ?? '-') ?></td>
                                        <td class="font-monospace text-muted"><?= e($log['ip_address'] ?? '127.0.0.1') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
