<?php
/**
 * Dean Sir / Super Admin Secured Dedicated Login Portal
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$assetPrefix = '../';
$error = $_GET['error'] ?? '';
$success = '';

if (is_logged_in()) {
    $role = get_current_user_role();
    if ($role === 'super_admin') {
        header("Location: dashboard.php");
        exit;
    } else {
        // If a club admin is already logged in, redirect them to club dashboard
        header("Location: ../club/dashboard.php");
        exit;
    }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $captchaInput = $_POST['captcha_code'] ?? '';
    $email        = trim($_POST['email'] ?? '');
    $password     = $_POST['password'] ?? '';

    $rateLimitError = check_login_rate_limit($email);

    if ($rateLimitError) {
        $error = $rateLimitError;
    } elseif (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid. Please refresh the page and try again.";
    } elseif (!verify_captcha_code($captchaInput)) {
        $error = "Incorrect verification code (CAPTCHA). Please enter the code shown in the image.";
    } else {
        if (empty($email) || empty($password)) {
            $error = "Please enter Dean Sir email address and password.";
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'super_admin' AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                reset_login_rate_limit($email);

                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email']     = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['role']      = $user['role'];

                log_audit($db, $user['id'], $user['full_name'], 'SUPER_ADMIN_LOGIN', 'user', $user['id'], "Dean Sir logged into Super Admin Portal");

                header("Location: dashboard.php");
                exit;
            } else {
                record_failed_login_attempt($email);
                $error = "Invalid email address or password. Please verify your Dean Sir credentials and try again.";
            }
        }
    }
}

$pageTitle = "Dean Sir Portal Login | ClubHub UIT";
require_once __DIR__ . '/../includes/header.php';
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

                <form action="dean-login.php" method="POST" class="text-start">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Dean Admin Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-secondary"></i></span>
                            <input type="email" name="email" class="form-control border-start-0" placeholder="admin@uit.edu" value="admin@uit.edu" required autofocus>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-secondary"></i></span>
                            <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                        </div>
                    </div>

                    <!-- Secured Verification Code (CAPTCHA) -->
                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Security Verification (CAPTCHA)</label>
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <img id="captchaImg" src="<?= $assetPrefix ?>api/captcha.php" alt="Verification Code" class="rounded border shadow-sm" style="height: 44px; width: 150px; object-fit: cover;">
                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-circle p-2" onclick="document.getElementById('captchaImg').src='<?= $assetPrefix ?>api/captcha.php?action=refresh&t=' + new Date().getTime();" title="Refresh CAPTCHA">
                                <i class="bi bi-arrow-clockwise fs-6"></i>
                            </button>
                        </div>
                        <input type="text" name="captcha_code" class="form-control text-uppercase font-monospace fw-bold" placeholder="ENTER CODE" maxlength="6" autocomplete="off" required>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold py-2-5 shadow-sm text-white mb-3">
                        <i class="bi bi-shield-check me-1"></i> Log In to Dean Portal
                    </button>
                </form>

                <div class="pt-3 border-top mt-3 small text-muted">
                    <span>Are you a Club Lead?</span>
                    <a href="<?= $assetPrefix ?>club-login.php" class="fw-bold text-success text-decoration-none ms-1">Go to Club Lead Login &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
