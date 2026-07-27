<?php
/**
 * Dean Sir / Super Admin Secured Dedicated Login Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$error = $_GET['error'] ?? '';
$success = '';

if (is_logged_in()) {
    $role = get_current_user_role();
    if ($role === 'super_admin') {
        header("Location: /admin/dashboard.php");
        exit;
    } else {
        // If a club admin is already logged in, redirect them to club dashboard
        header("Location: /club/dashboard.php");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid. Please try again.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Please enter Dean Sir credentials.";
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'super_admin' AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['role']      = $user['role'];

                log_audit($db, $user['id'], $user['full_name'], 'SUPER_ADMIN_LOGIN', 'user', $user['id'], "Dean Sir logged into Super Admin Portal");

                header("Location: /admin/dashboard.php");
                exit;
            } else {
                $error = "Invalid Dean Sir credentials or insufficient administrator privileges.";
            }
        }
    }
}

$pageTitle = "Dean Sir Portal Login | ClubHub UIT";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center py-4">
        <div class="col-md-5 col-lg-4">
            <div class="card p-4 p-md-5 border-0 shadow-lg rounded-4 text-center">
                <div class="bg-primary text-white rounded-circle mx-auto p-3 mb-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 68px; height: 68px;">
                    <i class="bi bi-shield-lock-fill fs-1"></i>
                </div>
                
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1-5 fw-bold mb-2 small">SECURED ACCESS</span>
                <h4 class="fw-bold mb-1 text-dark">Dean Sir Portal</h4>
                <p class="text-secondary small mb-4">Head of Student Affairs Login</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 small mb-3 text-start"><i class="bi bi-exclamation-circle-fill me-1"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <form action="/admin/dean-login.php" method="POST" class="text-start">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dean Admin Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-secondary"></i></span>
                            <input type="email" name="email" class="form-control border-start-0" placeholder="admin@uit.edu" value="admin@uit.edu" required autofocus>
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
                        <i class="bi bi-shield-check me-1"></i> Log In to Dean Portal
                    </button>
                </form>

                <div class="pt-3 border-top mt-3 small text-muted">
                    <span>Are you a Club Lead?</span>
                    <a href="/club-login.php" class="fw-bold text-success text-decoration-none ms-1">Go to Club Lead Login &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
