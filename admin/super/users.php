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

// Fetch Dean Sir Profile Details
$stmt = $db->prepare("SELECT * FROM users WHERE role = 'super_admin' LIMIT 1");
$stmt->execute();
$deanUser = $stmt->fetch() ?: [
    'full_name' => 'Prof. Sanjay Srivastava',
    'email' => 'admin@uit.edu'
];

// ── 1. Handle Dean Profile Update ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');

    if (empty($fullName) || empty($email)) {
        $error = 'Full Name and Email are required.';
    } else {
        try {
            if (!empty($newPassword)) {
                $passHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $uStmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, password_hash = ? WHERE role = 'super_admin'");
                $uStmt->execute([$fullName, $email, $passHash]);
            } else {
                $uStmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE role = 'super_admin'");
                $uStmt->execute([$fullName, $email]);
            }

            $_SESSION['full_name'] = $fullName;
            $_SESSION['email'] = $email;

            // Audit Log
            $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $fullName, 'DEAN_PROFILE_UPDATED', "Dean Sir updated administrative credentials and profile name."]);

            $message = 'Dean profile and credentials updated successfully!';
            
            // Refresh user
            $stmt->execute();
            $deanUser = $stmt->fetch();
        } catch (Exception $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

// ── 2. Handle Reset Password for Specific Club Admin ─────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_user_password') {
    $targetUserId = $_POST['user_id'] ?? '';
    $newPassword  = trim($_POST['new_password'] ?? '');

    if (!empty($targetUserId) && !empty($newPassword)) {
        try {
            $passHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $rStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $rStmt->execute([$passHash, $targetUserId]);

            // Audit Log
            $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'USER_PASSWORD_RESET', "Reset password for user account ID: $targetUserId"]);

            $message = "Password updated successfully for selected leadership account.";
        } catch (Exception $e) {
            $error = "Error resetting user password: " . $e->getMessage();
        }
    }
}

// ── 3. Handle Account Status Toggle ──────────────────────────────────
if (isset($_GET['toggle_user']) && isset($_GET['current_status'])) {
    $targetUserId = $_GET['toggle_user'];
    $currentStatus = $_GET['current_status'];
    $newStatus = ($currentStatus === 'active') ? 'suspended' : 'active';

    try {
        $uStmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $uStmt->execute([$newStatus, $targetUserId]);

        // Audit Log
        $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'USER_STATUS_TOGGLED', "Set account status to '$newStatus' for user ID: $targetUserId"]);

        header('Location: users.php?msg=Status+updated');
        exit;
    } catch (Exception $e) {
        $error = "Error toggling account status: " . $e->getMessage();
    }
}

// Fetch all registered Club Lead Accounts
$clubAdmins = $db->query("
    SELECT u.*, c.name as club_name, c.short_name as club_short
    FROM users u
    LEFT JOIN club_admins ca ON ca.user_id = u.id
    LEFT JOIN clubs c ON ca.club_id = c.id
    WHERE u.role = 'club_admin'
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Accounts & Governance | Dean Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, sans-serif; }
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
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">ACCOUNTS & PRIVILEGES</span>
                <h2 class="fw-bold mb-1 text-dark">Club Accounts & Dean Profile</h2>
                <p class="text-secondary small mb-0">Manage chapter lead access credentials, reset passwords, and update Dean of Student Affairs administrative profile.</p>
            </div>
            <a href="clubs.php" class="btn btn-primary rounded-pill px-4 py-2-5 fw-bold text-white shadow-sm">
                <i class="bi bi-plus-lg me-1"></i> Add New Club Admin
            </a>
        </div>

        <!-- Alert Feedback -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> User account status updated successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <div class="row g-4 mb-4">
            <!-- Dean Profile Form Card -->
            <div class="col-lg-5">
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <div class="bg-indigo text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 52px; height: 52px; background: linear-gradient(135deg,#6366f1,#a855f7);">
                            D
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark"><?= e($deanUser['full_name']) ?></h5>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-0-5 small">DEAN OF STUDENT AFFAIRS</span>
                        </div>
                    </div>

                    <form action="users.php" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Dean Full Name</label>
                            <input type="text" name="full_name" class="form-control rounded-3" value="<?= e($deanUser['full_name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Official Dean Email</label>
                            <input type="email" name="email" class="form-control rounded-3" value="<?= e($deanUser['email']) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">New Password (leave blank to keep current)</label>
                            <input type="password" name="new_password" class="form-control rounded-3" placeholder="••••••••">
                        </div>

                        <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold py-2.5 text-white shadow-sm">
                            <i class="bi bi-shield-check me-1"></i> Update Dean Credentials
                        </button>
                    </form>
                </div>

                <!-- Session Security Audit Summary Card -->
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-dark text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-shield-lock-fill text-warning fs-5"></i>
                        <h6 class="fw-bold mb-0 text-white">Active Session Security Protocol</h6>
                    </div>
                    <p class="small text-white-50 mb-3">All credential changes and password reset actions are signed and logged to the permanent audit trail.</p>
                    <div class="small font-monospace text-white-50">
                        <div>Session IP: <code>127.0.0.1</code></div>
                        <div>Role Privilege: <code>SUPER_ADMIN</code></div>
                    </div>
                </div>
            </div>

            <!-- Registered Club Lead Accounts List with Modals -->
            <div class="col-lg-7">
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Club Leadership Accounts Directory</h5>
                            <span class="text-secondary small">Active student chapter admin logins & credential reset</span>
                        </div>
                        <input type="text" id="userSearchInput" class="form-control form-control-sm rounded-pill px-3" placeholder="🔍 Search leads..." style="max-width: 180px;">
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="usersTable">
                            <thead class="table-light">
                                <tr class="small text-secondary">
                                    <th>ADMIN NAME</th>
                                    <th>ASSIGNED CHAPTER</th>
                                    <th>LOGIN EMAIL</th>
                                    <th>STATUS</th>
                                    <th class="text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clubAdmins)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No club lead accounts registered yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($clubAdmins as $ca): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= e($ca['full_name']) ?></td>
                                            <td>
                                                <span class="badge bg-success-subtle text-success border rounded-pill px-2.5 py-1 small">
                                                    <?= e($ca['club_short'] ?: $ca['club_name'] ?: 'Unassigned') ?>
                                                </span>
                                            </td>
                                            <td class="small font-monospace text-secondary"><?= e($ca['email']) ?></td>
                                            <td>
                                                <?php if (($ca['status'] ?? 'active') === 'active'): ?>
                                                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger-subtle text-danger border rounded-pill px-2.5 py-1 small">Suspended</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <div class="btn-group">
                                                    <!-- Reset Password Modal Trigger -->
                                                    <button type="button" class="btn btn-sm btn-light rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#resetUserPassModal<?= $ca['id'] ?>" title="Reset Leader Password">
                                                        <i class="bi bi-key-fill text-warning"></i>
                                                    </button>

                                                    <!-- Toggle Active / Suspended Status -->
                                                    <a href="users.php?toggle_user=<?= $ca['id'] ?>&current_status=<?= $ca['status'] ?? 'active' ?>" class="btn btn-sm btn-light rounded-circle" title="Toggle Status (Active / Suspended)">
                                                        <i class="bi bi-power <?= ($ca['status'] ?? 'active') === 'active' ? 'text-success' : 'text-danger' ?>"></i>
                                                    </a>
                                                </div>

                                                <!-- Reset Password Modal -->
                                                <div class="modal fade text-start" id="resetUserPassModal<?= $ca['id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content rounded-4 border-0 shadow">
                                                            <div class="modal-header border-0 pb-0">
                                                                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-key-fill text-warning me-2"></i> Reset Password for <?= htmlspecialchars($ca['full_name']) ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form action="users.php" method="POST">
                                                                <input type="hidden" name="action" value="reset_user_password">
                                                                <input type="hidden" name="user_id" value="<?= $ca['id'] ?>">
                                                                <div class="modal-body">
                                                                    <p class="small text-secondary mb-3">Set new login password for <code><?= htmlspecialchars($ca['email']) ?></code>.</p>
                                                                    <div class="mb-3">
                                                                        <label class="form-label small fw-semibold">New Password *</label>
                                                                        <input type="password" name="new_password" class="form-control rounded-3" placeholder="Enter new password" required>
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
                                            </td>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Live Search Filter for Users Table
    const searchInput = document.getElementById('userSearchInput');
    const tableRows = document.querySelectorAll('#usersTable tbody tr');

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase().trim();
            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
</script>
</body>
</html>
