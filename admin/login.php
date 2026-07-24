<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$error = $_GET['error'] ?? '';
$success = '';

if (is_logged_in()) {
    $role = get_current_user_role();
    if ($role === 'super_admin') {
        header("Location: /admin/super/index.php");
    } else {
        header("Location: /admin/dashboard.php");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid. Please try again.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Please fill in all credentials.";
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                // Regenerate Session ID
                session_regenerate_id(true);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['role']      = $user['role'];

                // If club admin, fetch assigned club ID
                if ($user['role'] === 'club_admin') {
                    $stmtClub = $db->prepare("SELECT club_id FROM club_admins WHERE user_id = ?");
                    $stmtClub->execute([$user['id']]);
                    $_SESSION['assigned_club_id'] = $stmtClub->fetchColumn() ?: null;
                }

                log_audit($db, $user['id'], $user['full_name'], 'USER_LOGIN', 'user', $user['id'], "Logged in successfully as " . $user['role']);

                if ($user['role'] === 'super_admin') {
                    header("Location: /admin/super/index.php");
                } else {
                    header("Location: /admin/dashboard.php");
                }
                exit;
            } else {
                $error = "Invalid email or password. Please check your credentials.";
            }
        }
    }
}

$pageTitle = "Unified Admin Login | ClubHub UIT";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center py-4">
        <div class="col-md-6 col-lg-5">
            <div class="card p-4 p-md-5 border-0 shadow-lg rounded-4">
                <div class="text-center mb-4">
                    <div class="bg-primary-subtle text-primary rounded-circle mx-auto p-3 mb-2 d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h4 class="fw-bold mb-1 text-dark">ClubHub Admin Portal</h4>
                    <p class="text-secondary small mb-0">Role-Based Login System</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 small mb-3"><i class="bi bi-exclamation-circle-fill me-1"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <form action="/admin/login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-secondary"></i></span>
                            <input type="email" name="email" class="form-control border-start-0" placeholder="admin@uit.edu or gdgoc@uit.edu" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-secondary"></i></span>
                            <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold py-2-5 shadow-sm text-white mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Portal
                    </button>
                </form>

                <!-- Credentials Info Helper for Both Portals -->
                <div class="p-3 bg-body-tertiary rounded-4 border text-start small space-y-2">
                    <strong class="d-block text-dark fw-bold border-bottom pb-2 mb-2"><i class="bi bi-person-badge text-primary me-1"></i> Available Portals:</strong>
                    
                    <div class="mb-2">
                        <span class="badge bg-primary-subtle text-primary border rounded-pill px-2 py-0-5 mb-1">1. Main Dean Sir Portal</span>
                        <div class="text-muted"><strong>Email:</strong> <code>admin@uit.edu</code> | <strong>Pass:</strong> <code>AdminPassword123!</code></div>
                        <div class="text-secondary" style="font-size: 0.72rem;">&rarr; Creates new clubs, issues team logins, resets credentials.</div>
                    </div>

                    <div>
                        <span class="badge bg-success-subtle text-success border rounded-pill px-2 py-0-5 mb-1">2. Club Lead Portal (e.g. GDGOC UIT)</span>
                        <div class="text-muted"><strong>Email:</strong> <code>gdgoc@uit.edu</code> | <strong>Pass:</strong> <code>GdgocUIT2026!</code></div>
                        <div class="text-secondary" style="font-size: 0.72rem;">&rarr; Updates club details, annual leadership tenures, and events.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
