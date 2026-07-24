<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$error = $_GET['error'] ?? '';
$success = '';

if (is_logged_in()) {
    if (get_current_user_role() === 'super_admin') {
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
                $_SESSION['user_role'] = $user['role'];

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
                $error = "Invalid email or password.";
            }
        }
    }
}

$pageTitle = "Admin Portal Login | CCMS";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card p-4 ccms-card shadow-lg">
                <div class="text-center mb-4">
                    <div class="bg-primary-subtle text-primary rounded-circle mx-auto p-3 mb-2" style="width: 60px; height: 60px;">
                        <i class="bi bi-shield-lock-fill fs-2"></i>
                    </div>
                    <h4 class="fw-bold mb-1">Admin Portal Login</h4>
                    <p class="text-secondary small mb-0">Sign in as Club Admin or Super Admin</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 small mb-3"><i class="bi bi-exclamation-circle-fill me-1"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <form action="/admin/login.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent"><i class="bi bi-envelope"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="admin@uit.edu" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-transparent"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold py-2 shadow-sm">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
                    </button>
                </form>

                <div class="mt-4 p-3 bg-body-tertiary rounded-3 border text-start small">
                    <strong class="d-block mb-1 text-primary"><i class="bi bi-info-circle me-1"></i> Demo Credentials:</strong>
                    <div class="mb-1"><strong>Super Admin:</strong> <code>admin@uit.edu</code> / <code>AdminPassword123!</code></div>
                    <div><strong>Club Admin:</strong> <code>geeksforgeeks@uit.edu</code> / <code>ClubPassword123!</code></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
