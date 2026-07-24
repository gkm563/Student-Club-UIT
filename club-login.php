<?php
/**
 * Club Leadership Dedicated Login Portal (/club-login.php)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$error = $_GET['error'] ?? '';
$success = '';

if (is_logged_in()) {
    $role = get_current_user_role();
    if ($role === 'super_admin') {
        header("Location: /admin/super/index.php");
        exit;
    } else {
        header("Location: /admin/dashboard.php");
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
            $error = "Please enter your club credentials.";
        } else {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'club_admin' AND status = 'active'");
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

                // Fetch assigned club ID
                $stmtClub = $db->prepare("SELECT club_id FROM club_admins WHERE user_id = ?");
                $stmtClub->execute([$user['id']]);
                $_SESSION['assigned_club_id'] = $stmtClub->fetchColumn() ?: null;

                log_audit($db, $user['id'], $user['full_name'], 'CLUB_ADMIN_LOGIN', 'user', $user['id'], "Club admin logged in");

                header("Location: /admin/dashboard.php");
                exit;
            } else {
                $error = "Invalid club email or password. If you are Dean Sir, please use the /admin/login portal.";
            }
        }
    }
}

$pageTitle = "Club Leadership Login | ClubHub UIT";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center py-4">
        <div class="col-md-5 col-lg-4">
            <div class="card p-4 p-md-5 border-0 shadow-lg rounded-4 text-center">
                <div class="bg-success-subtle text-success rounded-circle mx-auto p-3 mb-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 68px; height: 68px;">
                    <i class="bi bi-person-workspace fs-1"></i>
                </div>
                
                <span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1-5 fw-bold mb-2 small">CLUB LEADERSHIP</span>
                <h4 class="fw-bold mb-1 text-dark">Club Lead Portal</h4>
                <p class="text-secondary small mb-4">President & Core Team Login</p>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-3 small mb-3 text-start"><i class="bi bi-exclamation-circle-fill me-1"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <form action="/club-login.php" method="POST" class="text-start">
                    <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Club Lead Email</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-envelope text-secondary"></i></span>
                            <input type="email" name="email" class="form-control border-start-0" placeholder="gdgoc@uit.edu" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-secondary"></i></span>
                            <input type="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success rounded-pill w-100 fw-bold py-2-5 shadow-sm text-white mb-3">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Log In to Club Dashboard
                    </button>
                </form>

                <div class="pt-3 border-top mt-3 small text-muted">
                    <span>Are you Dean Sir (Main Admin)?</span>
                    <a href="/admin/login.php" class="fw-bold text-primary text-decoration-none ms-1">Go to Dean Sir Login &rarr;</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
