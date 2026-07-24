<?php
/**
 * Admin Gateway Landing Page (ClubHub UIT)
 * Provides distinct, secured entry points for Dean Sir & Club Leadership
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    $role = get_current_user_role();
    if ($role === 'super_admin') {
        header("Location: /admin/super/index.php");
    } else {
        header("Location: /admin/dashboard.php");
    }
    exit;
}

$pageTitle = "Admin Portals Gateway | ClubHub UIT";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center text-center py-4">
        <div class="col-lg-8">
            <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1-5 fw-bold mb-3 small">SECURED ACCESS GATEWAY</span>
            <h2 class="fw-bold mb-2 text-dark">Welcome to ClubHub Admin Portals</h2>
            <p class="text-secondary mb-5">Please select your administrative role to proceed to your dedicated portal.</p>

            <div class="row g-4 justify-content-center">
                <!-- 1. Dean Sir Portal -->
                <div class="col-md-6">
                    <div class="card p-4 p-md-5 border-0 shadow-lg rounded-4 h-100 ccms-card text-start position-relative">
                        <div class="bg-primary text-white rounded-circle p-3 mb-4 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-shield-lock-fill fs-2"></i>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 small align-self-start mb-2">SUPER ADMIN</span>
                        <h4 class="fw-bold mb-2 text-dark">Dean Sir Portal</h4>
                        <p class="text-secondary small mb-4 flex-grow-1">
                            For Dean of Student Affairs & Head of Student Clubs. Register new clubs, issue initial team credentials, and oversee campus organizations.
                        </p>
                        <a href="/admin/dean-login.php" class="btn btn-primary rounded-pill px-4 py-2-5 fw-bold text-white w-100 shadow-sm text-center">
                            Log In as Dean Sir &rarr;
                        </a>
                    </div>
                </div>

                <!-- 2. Club Lead Portal -->
                <div class="col-md-6">
                    <div class="card p-4 p-md-5 border-0 shadow-lg rounded-4 h-100 ccms-card text-start position-relative">
                        <div class="bg-success-subtle text-success rounded-circle p-3 mb-4 d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="bi bi-person-workspace fs-2"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1 small align-self-start mb-2">CLUB LEADERSHIP</span>
                        <h4 class="fw-bold mb-2 text-dark">Club Lead Portal</h4>
                        <p class="text-secondary small mb-4 flex-grow-1">
                            For Club Presidents, Leads & Core Team Members (e.g. GDGOC UIT). Manage club details, annual leadership tenures, and publish campus events.
                        </p>
                        <a href="/admin/club-login.php" class="btn btn-success rounded-pill px-4 py-2-5 fw-bold text-white w-100 shadow-sm text-center">
                            Log In as Club Lead &rarr;
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
