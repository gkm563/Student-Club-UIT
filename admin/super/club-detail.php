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
$message = '';
$error = '';

$clubId = trim($_GET['id'] ?? '');

if (empty($clubId)) {
    header('Location: clubs.php');
    exit;
}

// ── 1. Handle Status Toggle ──────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $cStmt = $db->prepare("SELECT name, status FROM clubs WHERE id = ?");
    $cStmt->execute([$clubId]);
    $cRow = $cStmt->fetch();
    
    if ($cRow) {
        $newStatus = ($cRow['status'] === 'active') ? 'inactive' : 'active';
        $stmt = $db->prepare("UPDATE clubs SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $clubId]);

        log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CLUB_STATUS_CHANGED', 'club', $clubId, "Set '" . $cRow['name'] . "' status to '$newStatus' from Club Detailed Overview");
        $message = "Club status updated to " . strtoupper($newStatus) . " successfully!";
    }
}

// ── 2. Handle Edit Club Details ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_club') {
    $name = trim($_POST['name'] ?? '');
    $shortName = trim($_POST['short_name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 1);
    $tagline = trim($_POST['tagline'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');

    if (!empty($name) && !empty($shortName)) {
        try {
            $stmt = $db->prepare("
                UPDATE clubs 
                SET name = ?, short_name = ?, category_id = ?, tagline = ?, description = ?, email = ?, website = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $shortName, $categoryId, $tagline, $description, $email, $website, $clubId]);

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CLUB_EDITED', 'club', $clubId, "Updated club details for '$name' ($shortName) from Detailed Overview");
            $message = "Club details for '$name' updated successfully!";
        } catch (Exception $e) {
            $error = 'Error updating club: ' . $e->getMessage();
        }
    }
}

// ── 3. Handle Leadership Password Reset ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $userId = $_POST['user_id'] ?? '';
    $newPass = trim($_POST['new_password'] ?? '');

    if (!empty($userId) && !empty($newPass)) {
        try {
            $passHash = password_hash($newPass, PASSWORD_DEFAULT);
            $rStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $rStmt->execute([$passHash, $userId]);

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CREDENTIAL_RESET', 'user', $userId, "Reset password for club admin user ID $userId from Detailed Overview");
            $message = "Password updated successfully for club leadership account.";
        } catch (Exception $e) {
            $error = "Error resetting password: " . $e->getMessage();
        }
    }
}

// ── Fetch Club Master Details ────────────────────────────────────────
$clubStmt = $db->prepare("
    SELECT c.*, cat.name as category_name, cat.icon as category_icon, u.id as user_id, u.email as admin_email, u.full_name as admin_name
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN club_admins ca ON ca.club_id = c.id
    LEFT JOIN users u ON ca.user_id = u.id
    WHERE c.id = ?
    LIMIT 1
");
$clubStmt->execute([$clubId]);
$club = $clubStmt->fetch();

if (!$club) {
    header('Location: clubs.php');
    exit;
}

// Categories for edit modal
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Leadership Roster
$leadStmt = $db->prepare("SELECT * FROM leadership WHERE club_id = ? ORDER BY order_index ASC, id ASC");
$leadStmt->execute([$clubId]);
$leaders = $leadStmt->fetchAll();

// Club Events Portfolio
$eventStmt = $db->prepare("SELECT * FROM events WHERE club_id = ? ORDER BY event_date DESC");
$eventStmt->execute([$clubId]);
$events = $eventStmt->fetchAll();

// Gallery Photos
$galStmt = $db->prepare("SELECT * FROM gallery_items WHERE club_id = ? ORDER BY created_at DESC LIMIT 12");
$galStmt->execute([$clubId]);
$gallery = $galStmt->fetchAll();

// Specific Audit Trail Logs
$auditStmt = $db->prepare("
    SELECT * FROM audit_logs 
    WHERE details LIKE ? 
    ORDER BY created_at DESC LIMIT 10
");
$auditStmt->execute(['%' . $club['name'] . '%']);
$auditLogs = $auditStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($club['name']) ?> – Executive Detailed Overview | Dean Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #1e293b; }

        .exec-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            padding: 20px 24px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.03);
            transition: all 0.25s ease;
        }

        .club-banner-header {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px 28px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
        }

        .kpi-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .table-custom th {
            font-size: 0.72rem;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 16px;
        }
        .table-custom td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">
    <!-- Universal Super Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Workspace -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">

        <!-- Top Navigation Bar -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="clubs.php" class="btn btn-light border rounded-pill px-3 py-1-5 text-secondary fw-semibold small">
                <i class="bi bi-arrow-left me-1"></i> Back to Campus Roster
            </a>

            <div class="d-flex align-items-center gap-2">
                <!-- Toggle Active / Inactive Status -->
                <a href="club-detail.php?id=<?= $club['id'] ?>&action=toggle_status" class="btn btn-sm <?= $club['status'] === 'active' ? 'btn-outline-danger' : 'btn-success' ?> rounded-pill px-3 py-1-5 fw-bold">
                    <i class="bi bi-power me-1"></i> <?= $club['status'] === 'active' ? 'Set Private / Inactive' : 'Activate Chapter' ?>
                </a>

                <!-- Edit Modal Trigger -->
                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1-5 fw-bold" data-bs-toggle="modal" data-bs-target="#editClubModal">
                    <i class="bi bi-pencil-square me-1"></i> Edit Details
                </button>

                <!-- Reset Credentials Modal Trigger -->
                <?php if ($club['user_id']): ?>
                    <button type="button" class="btn btn-sm btn-outline-warning text-dark rounded-pill px-3 py-1-5 fw-bold" data-bs-toggle="modal" data-bs-target="#resetPassModal">
                        <i class="bi bi-key-fill me-1"></i> Reset Password
                    </button>
                <?php endif; ?>

                <!-- View Public Club Page -->
                <a href="../../club-detail.html?id=<?= $club['id'] ?>" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3 py-1-5 fw-bold text-white shadow-sm">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Public Page
                </a>
            </div>
        </div>

        <!-- Alert Feedback Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- ── Club Executive Master Banner Card ── -->
        <div class="club-banner-header mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                <div class="d-flex align-items-center gap-3.5">
                    <img src="<?= htmlspecialchars($club['logo'] ?: '../../assets/United Logo.webp') ?>" class="rounded-4 border shadow-sm" style="width:72px;height:72px;object-fit:cover;" alt="">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($club['name']) ?></h3>
                            <span class="badge bg-secondary-subtle text-secondary font-monospace fs-6 px-2.5 py-1"><?= htmlspecialchars($club['short_name']) ?></span>
                            <?php if ($club['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 small fw-bold">Active Chapter</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 small fw-bold">Private / Inactive</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-secondary mb-1" style="font-size:0.9rem;"><?= htmlspecialchars($club['tagline'] ?: 'Official Student Chapter of United Institute of Technology') ?></p>
                        <div class="d-flex align-items-center gap-3 text-muted small">
                            <span><i class="bi bi-folder-fill text-primary me-1"></i> Category: <strong><?= htmlspecialchars($club['category_name']) ?></strong></span>
                            <span><i class="bi bi-envelope-fill text-secondary me-1"></i> Email: <code class="text-dark"><?= htmlspecialchars($club['admin_email'] ?: ($club['email'] ?: 'Unassigned')) ?></code></span>
                        </div>
                    </div>
                </div>

                <div class="border-start ps-md-4 py-2 d-none d-md-block" style="min-width:180px;">
                    <div class="text-secondary small font-monospace">CHAPTER CREDENTIALS</div>
                    <div class="fw-bold text-dark mb-1"><?= htmlspecialchars($club['admin_name'] ?: 'President / Lead') ?></div>
                    <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small">Role: Club Administrator</span>
                </div>
            </div>
        </div>

        <!-- ── 4 KPI Stats Deck ── -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="exec-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="kpi-icon-box bg-primary-subtle text-primary"><i class="bi bi-calendar-event-fill"></i></div>
                        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">Total Events</span>
                    </div>
                    <h3 class="fw-bold text-dark mb-0"><?= count($events) ?></h3>
                    <span class="text-secondary small">Campus Activity History</span>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="exec-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="kpi-icon-box bg-success-subtle text-success"><i class="bi bi-people-fill"></i></div>
                        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">Core Officers</span>
                    </div>
                    <h3 class="fw-bold text-dark mb-0"><?= count($leaders) ?></h3>
                    <span class="text-secondary small">Active Leadership Roster</span>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="exec-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="kpi-icon-box bg-warning-subtle text-warning"><i class="bi bi-images"></i></div>
                        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">Gallery Uploads</span>
                    </div>
                    <h3 class="fw-bold text-dark mb-0"><?= count($gallery) ?></h3>
                    <span class="text-secondary small">Activity Photos Recorded</span>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="exec-card">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="kpi-icon-box bg-purple-subtle text-purple" style="background:#f5f3ff; color:#7c3aed;"><i class="bi bi-shield-check"></i></div>
                        <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small">Audit Entries</span>
                    </div>
                    <h3 class="fw-bold text-dark mb-0"><?= count($auditLogs) ?></h3>
                    <span class="text-secondary small">Governance Trail Logs</span>
                </div>
            </div>
        </div>

        <!-- ── Leadership Roster Section ── -->
        <div class="exec-card mb-4 overflow-hidden p-0">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-people text-primary me-2"></i>Executive Officers & Core Student Leadership</h6>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 small fw-bold"><?= count($leaders) ?> Officers</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>OFFICER NAME</th>
                            <th>DESIGNATION / ROLE</th>
                            <th>EMAIL ADDRESS</th>
                            <th>PHONE / CONTACT</th>
                            <th>DEPARTMENT & YEAR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leaders)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">
                                    <i class="bi bi-info-circle d-block fs-3 text-secondary mb-1"></i>
                                    No leadership roster recorded for this chapter yet.
                                </td>
                            </tr>
                        <?php else: foreach ($leaders as $l): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2.5">
                                        <img src="<?= htmlspecialchars($l['avatar'] ?? '../../assets/United Logo.webp') ?>" class="rounded-circle border flex-shrink-0" style="width:36px;height:36px;object-fit:cover;" alt="" onerror="this.src='../../assets/United Logo.webp'">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($l['name']) ?></div>
                                    </div>
                                </td>
                                <td><span class="badge bg-purple-subtle text-purple border border-purple-subtle rounded-pill px-2.5 py-1" style="background:#f5f3ff; color:#7c3aed;"><?= htmlspecialchars($l['role_title'] ?? ucfirst(str_replace('_', ' ', $l['category'] ?? 'Officer'))) ?></span></td>
                                <td class="font-monospace small text-secondary"><?= htmlspecialchars($l['email'] ?: 'N/A') ?></td>
                                <td class="font-monospace small text-secondary"><?= htmlspecialchars($l['phone'] ?: 'N/A') ?></td>
                                <td class="small text-secondary"><?= htmlspecialchars($l['term_year'] ?: '2025-2026') ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Events Portfolio Section ── -->
        <div class="exec-card mb-4 overflow-hidden p-0">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar-event text-success me-2"></i>Campus Events Portfolio</h6>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 small fw-bold"><?= count($events) ?> Events</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>EVENT TITLE</th>
                            <th>SCHEDULED DATE</th>
                            <th>VENUE / LOCATION</th>
                            <th>ATTENDEES / SEATS</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted small">
                                    <i class="bi bi-calendar-x d-block fs-3 text-secondary mb-1"></i>
                                    No events organized by this club yet.
                                </td>
                            </tr>
                        <?php else: foreach ($events as $ev): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($ev['title']) ?></div>
                                    <div class="text-muted small" style="font-size:0.7rem;"><?= htmlspecialchars(substr($ev['description'] ?? '', 0, 50)) ?>...</div>
                                </td>
                                <td class="font-monospace small text-primary"><?= date('d M Y, h:i A', strtotime($ev['event_date'])) ?></td>
                                <td class="small text-secondary"><?= htmlspecialchars($ev['venue'] ?: 'UIT Campus Auditorium') ?></td>
                                <td class="font-monospace small text-dark"><?= (int)($ev['registered_count'] ?? 0) ?> registered</td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small"><?= ucfirst($ev['status']) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ── Audit Trail Security Logs ── -->
        <div class="exec-card p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-shield-check text-indigo me-2" style="color:#6366f1;"></i>Governance & Audit Logs for <?= htmlspecialchars($club['name']) ?></h6>
                <a href="audit-logs.php" class="btn btn-sm btn-light rounded-pill px-3 py-1 text-dark">Full Audit Log</a>
            </div>

            <?php if (empty($auditLogs)): ?>
                <div class="text-center py-3 text-muted small">No specific audit logs recorded for this club yet.</div>
            <?php else: foreach ($auditLogs as $log): ?>
                <div class="py-2 border-bottom border-light d-flex align-items-center justify-content-between">
                    <div>
                        <span class="badge bg-secondary-subtle text-secondary font-monospace me-2" style="font-size:0.68rem;"><?= htmlspecialchars($log['action']) ?></span>
                        <span class="text-dark small"><?= htmlspecialchars($log['details']) ?></span>
                    </div>
                    <span class="text-muted small font-monospace" style="font-size:0.7rem;"><?= date('d M Y H:i', strtotime($log['created_at'])) ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>

    </div>
</div>

<!-- Modal: Edit Club Details -->
<div class="modal fade" id="editClubModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Club: <?= htmlspecialchars($club['name']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="club-detail.php?id=<?= $club['id'] ?>" method="POST">
                <input type="hidden" name="action" value="edit_club">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Club Name *</label>
                        <input type="text" name="name" class="form-control rounded-3" value="<?= htmlspecialchars($club['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Short Code *</label>
                        <input type="text" name="short_name" class="form-control rounded-3" value="<?= htmlspecialchars($club['short_name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category *</label>
                        <select name="category_id" class="form-select rounded-3">
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $club['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Contact Email</label>
                        <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($club['email'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tagline</label>
                        <input type="text" name="tagline" class="form-control rounded-3" value="<?= htmlspecialchars($club['tagline']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control rounded-3" rows="3"><?= htmlspecialchars($club['description']) ?></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reset Leadership Password -->
<?php if ($club['user_id']): ?>
    <div class="modal fade" id="resetPassModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-key-fill text-warning me-2"></i> Reset Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="club-detail.php?id=<?= $club['id'] ?>" method="POST">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" value="<?= $club['user_id'] ?>">
                    <div class="modal-body">
                        <p class="small text-secondary mb-3">Set a new login password for <strong><?= htmlspecialchars($club['name']) ?></strong> leadership account (<code class="text-dark"><?= htmlspecialchars($club['admin_email']) ?></code>).</p>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">New Password *</label>
                            <input type="text" name="new_password" class="form-control rounded-3" placeholder="Enter new password" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>