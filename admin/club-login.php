<?php
/**
 * Club Leadership Dedicated Glassmorphism Login Portal (ClubHub UIT)
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
        $error = "Security token invalid. Please refresh and try again.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Please enter your club lead email and password.";
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
                $error = "Invalid credentials. Please verify your email & password or use 1-click quick credentials below.";
            }
        }
    }
}

$pageTitle = "Club Leadership Login Portal | ClubHub UIT";
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Custom Premium Glass Styling for Login Portal -->
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
        background: radial-gradient(circle, rgba(16, 185, 129, 0.25) 0%, rgba(15, 23, 42, 0) 70%);
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
        background: radial-gradient(circle, rgba(14, 165, 233, 0.25) 0%, rgba(15, 23, 42, 0) 70%);
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
        border-color: #10b981 !important;
        box-shadow: 0 0 15px rgba(16, 185, 129, 0.3) !important;
        color: #ffffff !important;
    }

    .glass-input::placeholder {
        color: rgba(255, 255, 255, 0.4) !important;
    }

    .input-group-text-dark {
        background: rgba(255, 255, 255, 0.08) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        color: #10b981 !important;
    }

    .quick-cred-chip {
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.15);
        color: #e2e8f0;
        font-size: 0.78rem;
        padding: 6px 14px;
        border-radius: 50px;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .quick-cred-chip:hover {
        background: rgba(16, 185, 129, 0.25);
        border-color: #10b981;
        color: #ffffff;
        transform: translateY(-2px);
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
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-3 mb-3" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(14, 165, 233, 0.2)); border: 1px solid rgba(16, 185, 129, 0.4); width: 76px; height: 76px;">
                        <i class="bi bi-shield-lock-fill text-success" style="font-size: 2rem;"></i>
                    </div>
                    <span class="badge bg-success bg-opacity-20 text-success border border-success border-opacity-30 rounded-pill px-3 py-1-5 fw-bold mb-2 small d-inline-block">
                        🛡️ SAC GOVERNED PORTAL
                    </span>
                    <h3 class="fw-extrabold text-white mb-1" style="letter-spacing: -0.5px;">Club Lead Portal</h3>
                    <p class="text-white-50 small mb-0">President, Vice-President & Core Chapter Management</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger rounded-4 border-0 bg-danger bg-opacity-25 text-white small mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2 text-danger"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form action="/admin/club-login.php" method="POST" id="clubLoginForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(get_csrf_token()) ?>">

                    <div class="mb-3.5">
                        <label for="loginEmail" class="form-label small fw-bold text-white-80">Club Lead Email *</label>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-dark rounded-start-3"><i class="bi bi-envelope-at-fill"></i></span>
                            <input type="email" name="email" id="loginEmail" class="form-control glass-input rounded-end-3 py-2.5" placeholder="e.g. gfgsc@uit.edu" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label for="loginPassword" class="form-label small fw-bold text-white-80 mb-0">Password *</label>
                            <a href="#" id="togglePasswordBtn" class="small text-success text-decoration-none" style="font-size: 0.8rem;"><i class="bi bi-eye me-1"></i> Show</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text input-group-text-dark rounded-start-3"><i class="bi bi-key-fill"></i></span>
                            <input type="password" name="password" id="loginPassword" class="form-control glass-input rounded-end-3 py-2.5" placeholder="••••••••" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success rounded-pill w-100 py-3 fw-bold text-white shadow-lg d-flex align-items-center justify-content-center gap-2 mb-4" style="background: linear-gradient(135deg, #10b981, #059669); border: none;">
                        <span>Log In to Club Dashboard</span>
                        <i class="bi bi-arrow-right-short fs-4"></i>
                    </button>
                </form>

                <!-- 1-Click Quick Fill Credentials Chips -->
                <div class="pt-3 border-top border-white-10">
                    <span class="d-block small text-white-50 fw-bold text-uppercase mb-2" style="font-size: 0.72rem; letter-spacing: 0.5px;">⚡ 1-Click Quick Demo Login Credentials</span>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="quick-cred-chip" onclick="fillCredentials('gfgsc@uit.edu', 'GfgscPass123!')">
                            🟢 GFG Lead
                        </button>
                        <button type="button" class="quick-cred-chip" onclick="fillCredentials('gdgoc@uit.edu', 'GdgocPass123!')">
                            🔵 GDG Lead
                        </button>
                        <button type="button" class="quick-cred-chip" onclick="fillCredentials('flutterflow@uit.edu', 'FlutterPass123!')">
                            ⚡ FlutterFlow
                        </button>
                        <button type="button" class="quick-cred-chip" onclick="fillCredentials('ecell@uit.edu', 'EcellPass123!')">
                            🚀 E-Cell
                        </button>
                    </div>
                </div>

                <!-- Dean Portal Switch -->
                <div class="text-center mt-4 pt-3 border-top border-white-10">
                    <span class="small text-white-50">Main Campus Administrator?</span>
                    <a href="/admin/dean-login.php" class="small fw-bold text-info text-decoration-none ms-1">Go to Dean Sir Login &rarr;</a>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
function fillCredentials(email, password) {
    document.getElementById('loginEmail').value = email;
    document.getElementById('loginPassword').value = password;
    
    // Highlight button
    const btn = document.querySelector('#clubLoginForm button[type="submit"]');
    if (btn) {
        btn.classList.add('pulse-highlight');
        setTimeout(() => btn.classList.remove('pulse-highlight'), 1000);
    }
}

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
