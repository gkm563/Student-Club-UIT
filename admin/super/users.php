<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: /admin/login.php');
    exit;
}

$db = Database::getConnection();
$message = '';
$error = '';

// Fetch Super Admin User Details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle Dean Profile Update
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
                $uStmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, password_hash = ? WHERE id = ?");
                $uStmt->execute([$fullName, $email, $passHash, $_SESSION['user_id']]);
            } else {
                $uStmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                $uStmt->execute([$fullName, $email, $_SESSION['user_id']]);
            }

            $_SESSION['full_name'] = $fullName;
            $_SESSION['email'] = $email;
            $message = 'Dean profile and credentials updated successfully!';
            
            // Refresh user
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
        } catch (Exception $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Profile & Credentials | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
                body { background: #f1f5f9; }
        .admin-nav-link {
            color: rgba(255,255,255,0.65); padding: 10px 14px; border-radius: 10px;
            display: flex; align-items: center; gap: 11px; text-decoration: none;
            font-weight: 500; font-size: 0.82rem; transition: all 0.2s ease; margin-bottom: 1px;
        }
        .admin-nav-link i { font-size: 1rem; width: 18px; text-align: center; flex-shrink: 0; }
        .admin-nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateX(2px); }
        .admin-nav-link.active { background: linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        .sidebar-section-label { color: rgba(255,255,255,0.35); font-size: 0.6rem; letter-spacing: 1.5px; font-weight: 700; text-transform: uppercase; padding: 0 14px; margin: 14px 0 6px; }
        .border-white-10 { border-color: rgba(255,255,255,0.1) !important; }
        .super-sidebar::-webkit-scrollbar { width: 4px; }
        .super-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

        <nav class="d-flex flex-column gap-2">
            <a href="/admin/super/index.php" class="admin-nav-link"><i class="bi bi-speedometer2"></i> Overview</a>
            <a href="/admin/super/clubs.php" class="admin-nav-link"><i class="bi bi-trophy"></i> Manage Clubs</a>
            <a href="/admin/super/users.php" class="admin-nav-link active"><i class="bi bi-shield-lock"></i> Dean Profile</a>
            <a href="/admin/logout.php" class="admin-nav-link text-danger mt-4"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4 p-md-5">
        <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">SUPER ADMIN</span>
            <h2 class="fw-bold mb-1">Dean Sir Profile & Credentials</h2>
            <p class="text-secondary small mb-0">Update Dean of Student Affairs credentials and administration details.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card p-4 border-0 shadow-sm rounded-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-person-gear text-primary me-2"></i> Update Dean Account</h5>
                    <form action="/admin/super/users.php" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Faculty / Administrator Name</label>
                            <input type="text" name="full_name" class="form-control rounded-3" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-semibold">New Password (leave blank to keep current)</label>
                            <input type="password" name="new_password" class="form-control rounded-3" placeholder="Enter new password">
                        </div>
                        <button type="submit" class="btn btn-primary rounded-pill px-5 py-2 fw-bold shadow-sm">Save Profile Changes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
