<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../dean-login.php');
    exit;
}

$db = Database::getConnection();

$stmt = $db->prepare("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 100");
$stmt->execute();
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Audit Logs | Dean Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="badge bg-danger-subtle text-danger border rounded-pill px-3 py-1 fw-bold small">SECURITY AUDIT</span>
                <h2 class="fw-bold mb-1">Administrative Audit Logs</h2>
                <p class="text-secondary small mb-0">Immutable record of administrative actions, authentication attempts, and profile modifications.</p>
            </div>
        </div>

        <div class="card p-3 p-md-4 border-0 shadow-sm rounded-4 bg-white">
            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0">
                    <thead class="table-light">
                        <tr class="small text-secondary">
                            <th>TIMESTAMP</th>
                            <th>ACTOR / USER</th>
                            <th>ACTION EVENT</th>
                            <th>TARGET TYPE</th>
                            <th>DETAILS</th>
                            <th>IP ADDRESS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No security audit logs recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="text-muted"><?= e(date('M j, Y - g:i:s A', strtotime($log['created_at']))) ?></td>
                                    <td class="fw-semibold text-dark"><?= e($log['user_name'] ?: 'System') ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
