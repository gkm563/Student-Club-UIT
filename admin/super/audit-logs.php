<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../dean-login.php');
    exit;
}

$db = Database::getConnection();

// ── 1. Handle Clear Old Logs (delete logs older than 30 days) ────────
if (isset($_GET['clear_old_logs']) && $_GET['clear_old_logs'] === '1') {
    try {
        $cleared = $db->exec("DELETE FROM audit_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");

        // Log the clearing action itself
        $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'LOGS_CLEARED', "Cleared $cleared audit log entries older than 30 days."]);

        header('Location: audit-logs.php?msg=Cleared+' . $cleared . '+old+log+entries');
        exit;
    } catch (Exception $e) {
        $clearError = "Error clearing logs: " . $e->getMessage();
    }
}

// ── 2. Pagination Setup ───────────────────────────────────────────────
$perPage = 25;
$page = max(1, intval($_GET['p'] ?? 1));
$offset = ($page - 1) * $perPage;

// ── 3. Filter Setup ───────────────────────────────────────────────────
$actionFilter = trim($_GET['action_filter'] ?? '');

// ── 4. Fetch KPI Metrics ──────────────────────────────────────────────
$totalLogs = $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
$todayLogs = $db->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$uniqueActors = $db->query("SELECT COUNT(DISTINCT user_name) FROM audit_logs WHERE user_name IS NOT NULL AND user_name != ''")->fetchColumn();

// ── 5. Distinct Actions for Filter Dropdown ───────────────────────────
$actionTypes = $db->query("SELECT DISTINCT action FROM audit_logs WHERE action IS NOT NULL ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);

// ── 6. Fetch Paginated Logs with Optional Filter ──────────────────────
if (!empty($actionFilter)) {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM audit_logs WHERE action = ?");
    $countStmt->execute([$actionFilter]);
    $filteredTotal = $countStmt->fetchColumn();

    $logsStmt = $db->prepare("SELECT * FROM audit_logs WHERE action = ? ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $logsStmt->execute([$actionFilter]);
} else {
    $filteredTotal = $totalLogs;
    $logsStmt = $db->prepare("SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
    $logsStmt->execute();
}
$logs = $logsStmt->fetchAll();
$totalPages = max(1, ceil($filteredTotal / $perPage));

// ── 7. Activity Chart: last 7 days log counts ─────────────────────────
$chartRows = $db->query("
    SELECT DATE(created_at) as log_date, COUNT(*) as cnt
    FROM audit_logs
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY log_date ASC
")->fetchAll();

$chartMax = 1;
$chartData = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartData[$d] = 0;
}
foreach ($chartRows as $row) {
    $chartData[$row['log_date']] = intval($row['cnt']);
    if (intval($row['cnt']) > $chartMax) $chartMax = intval($row['cnt']);
}

// Action → badge color map
function actionBadgeClass(string $action): string {
    $map = [
        'CLUB_EDITED' => 'bg-primary-subtle text-primary',
        'CATEGORY_EDITED' => 'bg-info-subtle text-info',
        'USER_STATUS_TOGGLED' => 'bg-warning-subtle text-warning',
        'USER_PASSWORD_RESET' => 'bg-orange-subtle text-orange',
        'MESSAGE_DELETED' => 'bg-danger-subtle text-danger',
        'CREDENTIAL_RESET' => 'bg-purple-subtle text-purple',
        'DEAN_PROFILE_UPDATED' => 'bg-indigo-subtle text-indigo',
        'PROPOSAL_APPROVED' => 'bg-success-subtle text-success',
        'PROPOSAL_REJECTED' => 'bg-danger-subtle text-danger',
        'LOGS_CLEARED' => 'bg-dark bg-opacity-10 text-dark',
        'EVENT_CREATED' => 'bg-success-subtle text-success',
        'DEAN_ADVISORY_BROADCAST' => 'bg-info-subtle text-info',
        'CLUB_STATUS_CHANGED' => 'bg-warning-subtle text-warning',
    ];
    return $map[$action] ?? 'bg-secondary-subtle text-secondary';
}
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
        body { background: #f8fafc; font-family: 'Inter', system-ui, sans-serif; }
        .stat-card { border: none; border-radius: 18px; background: #ffffff; }
        .chart-bar { background: linear-gradient(to top, #6366f1, #818cf8); border-radius: 6px 6px 0 0; min-width: 28px; transition: height 0.6s ease; }
        .chart-bar-wrap { display: flex; flex-direction: column; align-items: center; justify-content: flex-end; }
        @media print {
            .no-print { display: none !important; }
            .card { box-shadow: none !important; }
        }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">
    <!-- Universal Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">

        <!-- Header Banner -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge bg-danger-subtle text-danger border rounded-pill px-3 py-1 fw-bold small">SECURITY AUDIT</span>
                <h2 class="fw-bold mb-1 text-dark">Administrative Audit Logs</h2>
                <p class="text-secondary small mb-0">Immutable record of all administrative actions, credential changes, and audit events.</p>
            </div>
            <div class="d-flex gap-2 no-print">
                <div class="dropdown">
                    <button class="btn btn-outline-secondary rounded-pill px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-1"></i> Export Data
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end rounded-4 shadow border-0 p-2">
                        <li><a class="dropdown-item rounded-3 small py-2 fw-medium" href="#" onclick="ClubHubExporter.exportCSV('auditLogsTable', 'Audit-Logs'); return false;"><i class="bi bi-filetype-csv text-success me-2 fs-6"></i> Export CSV (.csv)</a></li>
                        <li><a class="dropdown-item rounded-3 small py-2 fw-medium" href="#" onclick="ClubHubExporter.exportExcel('auditLogsTable', 'Audit-Logs'); return false;"><i class="bi bi-file-earmark-excel text-success me-2 fs-6"></i> Export Excel (.xls)</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item rounded-3 small py-2 fw-medium" href="#" onclick="ClubHubExporter.exportPDF('auditLogsTable', 'Administrative Security Audit Logs Report'); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2 fs-6"></i> Print / Save PDF Report</a></li>
                    </ul>
                </div>
                <button class="btn btn-danger rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                    <i class="bi bi-trash me-1"></i> Clear Old Logs
                </button>
            </div>
        </div>

        <!-- Alert Feedback -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_GET['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($clearError)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($clearError) ?></div>
        <?php endif; ?>

        <!-- KPI Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stat-card p-3 p-md-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">TOTAL ACTIONS LOGGED</span>
                            <h3 class="fw-bold text-dark mb-0"><?= number_format($totalLogs) ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-primary-subtle text-primary fs-3"><i class="bi bi-shield-fill-check"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card p-3 p-md-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">ACTIONS TODAY</span>
                            <h3 class="fw-bold text-warning mb-0"><?= $todayLogs ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-warning-subtle text-warning fs-3"><i class="bi bi-activity"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card p-3 p-md-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">UNIQUE ACTORS</span>
                            <h3 class="fw-bold text-success mb-0"><?= $uniqueActors ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-success-subtle text-success fs-3"><i class="bi bi-people-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity Bar Chart (last 7 days) -->
        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-fill text-primary me-2"></i> Activity: Last 7 Days</h6>
            <div class="d-flex align-items-end gap-2" style="height: 80px;">
                <?php foreach ($chartData as $day => $count): 
                    $pct = $chartMax > 0 ? round(($count / $chartMax) * 80) : 2;
                    $pct = max($pct, 3);
                ?>
                <div class="chart-bar-wrap flex-grow-1 text-center" title="<?= date('M j', strtotime($day)) ?>: <?= $count ?> actions">
                    <div class="chart-bar w-100" style="height: <?= $pct ?>px;"></div>
                    <div class="text-muted small mt-1" style="font-size: 0.65rem;"><?= date('M j', strtotime($day)) ?></div>
                    <div class="fw-bold" style="font-size: 0.7rem;"><?= $count ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Search & Filter Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white no-print">
            <div class="row g-3 align-items-center">
                <div class="col-md-7">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="logSearchInput" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Search by actor name, action, or details...">
                    </div>
                </div>
                <div class="col-md-5">
                    <form method="GET">
                        <select name="action_filter" class="form-select rounded-pill" onchange="this.form.submit()">
                            <option value="">All Action Types</option>
                            <?php foreach ($actionTypes as $at): ?>
                                <option value="<?= htmlspecialchars($at) ?>" <?= ($actionFilter === $at) ? 'selected' : '' ?>><?= htmlspecialchars($at) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
        </div>

        <!-- Audit Logs Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0" id="auditLogsTable">
                    <thead class="table-light">
                        <tr class="small text-secondary">
                            <th style="width: 160px;">TIMESTAMP</th>
                            <th>ACTOR</th>
                            <th>ACTION EVENT</th>
                            <th>DETAILS</th>
                            <th class="text-end no-print">VIEW</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-shield-slash fs-1 d-block mb-2 text-secondary"></i>
                                    No audit log entries found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td class="text-muted font-monospace" style="font-size: 0.78rem; white-space: nowrap;">
                                        <?= date('M j, Y', strtotime($log['created_at'])) ?><br>
                                        <span class="text-secondary"><?= date('g:i:s A', strtotime($log['created_at'])) ?></span>
                                    </td>
                                    <td class="fw-semibold text-dark"><?= e($log['user_name'] ?: 'System') ?></td>
                                    <td>
                                        <span class="badge rounded-pill font-monospace px-2.5 py-1 small <?= actionBadgeClass($log['action'] ?? '') ?>">
                                            <?= e($log['action'] ?? 'N/A') ?>
                                        </span>
                                    </td>
                                    <td class="text-secondary" style="max-width: 300px; font-size: 0.82rem;">
                                        <?= e(mb_substr($log['details'] ?? '-', 0, 80)) ?><?= strlen($log['details'] ?? '') > 80 ? '…' : '' ?>
                                    </td>
                                    <td class="text-end no-print">
                                        <button type="button" class="btn btn-sm btn-light rounded-circle" data-bs-toggle="modal" data-bs-target="#logModal<?= htmlspecialchars($log['id']) ?>" title="View Full Log">
                                            <i class="bi bi-eye-fill text-primary"></i>
                                        </button>

                                        <!-- Log Detail Modal -->
                                        <div class="modal fade text-start" id="logModal<?= htmlspecialchars($log['id']) ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-shield-lock text-primary me-2"></i> Audit Log Detail</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <table class="table table-borderless table-sm small">
                                                            <tr><th class="text-secondary" style="width: 120px;">Log ID</th><td class="font-monospace"><?= e($log['id']) ?></td></tr>
                                                            <tr><th class="text-secondary">Timestamp</th><td><?= e(date('F j, Y - g:i:s A', strtotime($log['created_at']))) ?></td></tr>
                                                            <tr><th class="text-secondary">Actor</th><td class="fw-semibold"><?= e($log['user_name'] ?: 'System') ?></td></tr>
                                                            <tr><th class="text-secondary">User ID</th><td class="font-monospace"><?= e($log['user_id'] ?? 'N/A') ?></td></tr>
                                                            <tr><th class="text-secondary">Action</th><td><span class="badge rounded-pill font-monospace <?= actionBadgeClass($log['action'] ?? '') ?>"><?= e($log['action'] ?? 'N/A') ?></span></td></tr>
                                                            <tr><th class="text-secondary">Details</th><td class="text-secondary lh-base"><?= e($log['details'] ?? '-') ?></td></tr>
                                                        </table>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Footer -->
            <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-between align-items-center p-3 border-top no-print">
                <span class="text-muted small">Showing page <?= $page ?> of <?= $totalPages ?> (<?= number_format($filteredTotal) ?> total entries)</span>
                <nav>
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-pill me-1" href="?p=<?= $page - 1 ?>&action_filter=<?= urlencode($actionFilter) ?>">← Prev</a>
                        </li>
                        <?php for ($pg = max(1, $page - 2); $pg <= min($totalPages, $page + 2); $pg++): ?>
                            <li class="page-item <?= ($pg == $page) ? 'active' : '' ?>">
                                <a class="page-link rounded-pill me-1" href="?p=<?= $pg ?>&action_filter=<?= urlencode($actionFilter) ?>"><?= $pg ?></a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link rounded-pill" href="?p=<?= $page + 1 ?>&action_filter=<?= urlencode($actionFilter) ?>">Next →</a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Clear Old Logs Confirmation Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i> Clear Old Audit Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning rounded-3 border-0">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    This will permanently delete all audit log entries older than <strong>30 days</strong>. This action cannot be undone.
                </div>
                <p class="text-secondary small">Recent logs (within 30 days) will be preserved for compliance and review.</p>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <a href="audit-logs.php?clear_old_logs=1" class="btn btn-danger rounded-pill px-4 fw-bold">
                    <i class="bi bi-trash me-1"></i> Delete Old Logs
                </a>
            </div>
        </div>
    </div>
</div>

<script src="../../assets/js/export_utility.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Instant Search Filter
const logSearch = document.getElementById('logSearchInput');
const logRows = document.querySelectorAll('#auditLogsTable tbody tr');

if (logSearch) {
    logSearch.addEventListener('input', function() {
        const q = this.value.toLowerCase().trim();
        logRows.forEach(row => {
            row.style.display = !q || row.innerText.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}

// CSV Export
function exportTableCSV() {
    const rows = document.querySelectorAll('#auditLogsTable tr');
    let csv = [];
    rows.forEach(row => {
        const cols = row.querySelectorAll('th, td');
        const rowData = Array.from(cols).map(c => '"' + c.innerText.replace(/"/g, '""').replace(/\n/g, ' ') + '"');
        // Skip last column (View button)
        rowData.pop();
        csv.push(rowData.join(','));
    });
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a'); a.href = url; a.download = 'audit-logs-' + new Date().toISOString().slice(0,10) + '.csv'; a.click();
    URL.revokeObjectURL(url);
}
</script>
</body>
</html>
