<?php
/**
 * Dean Sir / Super Admin Secured Dedicated Login Portal (ClubHub UIT)
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

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

                header("Location: /admin/super/index.php");
                exit;
            } else {
                $error = "Invalid Dean Sir credentials or insufficient administrator privileges.";
            }
        }
    }
}

$pageTitle = "Dean Sir Portal Login | ClubHub UIT";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Custom Premium Glass Styling for Dean Login Portal -->
<style>
    body {
        background: #0b0f19 !important;
        color: #f8fafc;
        min-height: 100vh;
        position: relative;
    }
    
    .login-mesh-orb-1 {
        position: fixed;
        top: -100px;
        left: -100px;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(29, 78, 216, 0.3) 0%, rgba(15, 23, 42, 0) 70%);
        z-index: 0;
        pointer-events: none;
        filter: blur(50px);
    }
    .login-mesh-orb-2 {
        position: fixed;
        bottom: -100px;
        right: -100px;
        width: 550px;
        height: 550px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.25) 0%, rgba(15, 23, 42, 0) 70%);
        z-index: 0;
        pointer-events: none;
        filter: blur(50px);
    }

    .glass-login-card {
        background: rgba(15, 23, 42, 0.85) !important;
        backdrop-filter: blur(25px);
        -webkit-backdrop-filter: blur(25px);
        border: 1px solid rgba(255, 255, 255, 0.12) !important;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5) !important;
        border-radius: 28px !important;
        position: relative;
        z-index: 10;
    }

    .glass-input {
        background: rgba(255, 255, 255, 0.06) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #ffffff !important;
        font-size: 0.95rem;
    }

    .glass-input:focus {
        background: rgba(255, 255, 255, 0.1) !important;
        border-color: #2563eb !important;
        box-shadow: 0 0 15px rgba(37, 99, 235, 0.3) !important;
        color: #ffffff !important;
    }

    .glass-input::placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    .input-group-text-dark {
        background: rgba(255, 255, 255, 0.08) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #3b82f6 !important;
    }
</style>

<div class="login-mesh-orb-1"></div>
<div class="login-mesh-orb-2"></div>

<div class="container py-5 my-md-4 position-relative z-2">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-5">
            <div class="card glass-login-card p-4 p-md-5">
                
                <!-- Shield Icon Header -->
                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3 mb-3" style="background: linear-gradient(135deg, rgba(29, 78, 216, 0.25), rgba(2, 132, 199, 0.25)); border: 1px solid rgba(59, 130, 246, 0.4); width: 76px; height: 76px;">
                        <i class="bi bi-shield-lock-fill text-primary" style="font-size: 2rem;"></i>
                    </div>
                    <span class="badge bg-primary bg-opacity-20 text-primary border border-primary border-opacity-30 rounded-pill px-3 py-1-5 fw-bold mb-2 small d-inline-block">
                        🏛️ SUPER ADMIN SECURED PORTAL
                    </span>
                    <h3 class="fw-extrabold text-white mb-1" style="letter-spacing: -0.5px;">Dean Sir Portal</h3>
                    <p class="text-white-50 small mb-0">Head of Student Affairs Administration</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-4 border-0 bg-danger bg-opacity-25 text-white small mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="/admin/dean-login.php" method="POST" id="deanLoginForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">

                    <div class="mb-3.5">
                        <label for="loginEmail" class="form-label small fw-bold text-white-80">Dean Admin Email *</label>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-dark rounded-start-3"><i class="bi bi-person-badge-fill"></i></span>
                            <input type="email" name="email" id="loginEmail" class="form-control glass-input rounded-end-3 py-2.5" value="admin@uit.edu" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label for="loginPassword" class="form-label small fw-bold text-white-80 mb-0">Password *</label>
                            <a href="#" id="togglePasswordBtn" class="small text-primary text-decoration-none" style="font-size: 0.8rem;"><i class="bi bi-eye me-1"></i> Show</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-dark rounded-start-3"><i class="bi bi-key-fill"></i></span>
                            <input type="password" name="password" id="loginPassword" class="form-control glass-input rounded-end-3 py-2.5" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary rounded-pill w-100 py-3 fw-bold text-white shadow-lg d-flex align-items-center justify-content-center gap-2 mb-4" style="background: linear-gradient(135deg, #1d4ed8, #0284c7); border: none;">
                        <span>Log In as Dean Sir</span>
                        <i class="bi bi-arrow-right-short fs-4"></i>
                    </button>
                </form>

                <!-- Club Portal Switch -->
                <div class="text-center mt-4 pt-3 border-top border-white-10">
                    <span class="small text-white-50">Club President or Chapter Lead?</span>
                    <a href="/admin/club-login.php" class="small fw-bold text-success text-decoration-none ms-1">Go to Club Lead Login &rarr;</a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('togglePasswordBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const passInput = document.getElementById('loginPassword');
    if (passInput.type === 'password') {
        passInput.type = 'text';
        this.innerHTML = '<i class="bi bi-eye-slash me-1"></i> Hide';
    } else {
        passInput.type = 'password';
        this.innerHTML = '<i class="bi bi-eye me-1"></i> Show';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
