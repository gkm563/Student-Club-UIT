<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_super_admin();

$db = Database::getConnection();
$success = '';
$error = '';

// Create Club Admin Account
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create_user'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF validation error.";
    } else {
        $email     = trim($_POST['email'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password  = $_POST['password'] ?? '';
        $club_id   = $_POST['club_id'] ?? '';

        if (empty($email) || empty($full_name) || empty($password)) {
            $error = "Name, email, and password are required.";
        } else {
            try {
                $userId = generate_uuid();
                $hash   = password_hash($password, PASSWORD_BCRYPT);
                $stmtIns = $db->prepare("INSERT INTO users (id, email, password_hash, full_name, role, status) VALUES (?, ?, ?, ?, 'club_admin', 'active')");
                $stmtIns->execute([$userId, $email, $hash, $full_name]);

                if (!empty($club_id)) {
                    $stmtBind = $db->prepare("INSERT INTO club_admins (club_id, user_id) VALUES (?, ?)");
                    $stmtBind->execute([$club_id, $userId]);
                }

                $success = "Club Admin account for '$full_name' created successfully!";
                log_audit($db, get_current_user_id(), get_current_user_name(), 'USER_CREATE', 'user', $userId, "Created club admin: $email");
            } catch (Exception $e) {
                $error = "Failed to create user: " . $e->getMessage();
            }
        }
    }
}

// Fetch users with their assigned club names
$users = $db->query("
    SELECT u.*, c.name AS assigned_club_name
    FROM users u
    LEFT JOIN club_admins ca ON u.id = ca.user_id
    LEFT JOIN clubs c ON ca.club_id = c.id
    ORDER BY u.created_at DESC
")->fetchAll();

$clubs = $db->query("SELECT id, name FROM clubs WHERE deleted_at IS NULL ORDER BY name ASC")->fetchAll();

$pageTitle = "User Accounts | Super Admin";
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
                <a href="/admin/super/users.php" class="admin-nav-link active"><i class="bi bi-people"></i> Manage Accounts</a>
                <a href="/admin/super/audit-logs.php" class="admin-nav-link"><i class="bi bi-journal-text"></i> Security Audit Logs</a>
                <hr class="my-2 border-secondary-subtle">
                <a href="/admin/logout.php" class="admin-nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">System User Accounts</h2>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createUserModal">
                    <i class="bi bi-person-plus-fill me-1"></i> Create Club Admin
                </button>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success rounded-3 small mb-3"><i class="bi bi-check-circle-fill me-1"></i> <?= e($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger rounded-3 small mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <div class="card p-4 ccms-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead>
                            <tr>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Assigned Scope</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($u['full_name']) ?></td>
                                    <td><code><?= e($u['email']) ?></code></td>
                                    <td>
                                        <?php if ($u['role'] === 'super_admin'): ?>
                                            <span class="badge bg-danger text-white">Super Admin</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary-subtle text-primary border rounded-pill">Club Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($u['assigned_club_name'] ?: ($u['role'] === 'super_admin' ? 'Global Campus Scope' : 'Unassigned')) ?></td>
                                    <td><span class="badge bg-success-subtle text-success rounded-pill">Active</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create User Modal -->
<div class="modal fade" id="createUserModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Create Club Admin Account</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/super/users.php" method="POST">
                <input type="hidden" name="action_create_user" value="1">
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Name</label>
                        <input type="text" name="full_name" class="form-control" placeholder="e.g. Aarav Sharma" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="e.g. geeksforgeeks@uit.edu" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Temporary Password</label>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Assign to Club</label>
                        <select name="club_id" class="form-select">
                            <option value="">-- Unassigned --</option>
                            <?php foreach ($clubs as $c): ?>
                                <option value="<?= e($c['id']) ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
