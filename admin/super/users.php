<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../dean-login.php');
    exit;
}

$db = Database::getConnection();
$message = '';
$error = '';

// Fetch Dean Sir Profile Details
$stmt = $db->prepare("SELECT * FROM users WHERE role = 'super_admin' LIMIT 1");
$stmt->execute();
$deanUser = $stmt->fetch() ?: [
    'full_name' => 'Prof. Sanjay Srivastava',
    'email' => 'admin@uit.edu'
];

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
                $uStmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, password_hash = ? WHERE role = 'super_admin'");
                $uStmt->execute([$fullName, $email, $passHash]);
            } else {
                $uStmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE role = 'super_admin'");
                $uStmt->execute([$fullName, $email]);
            }

            $_SESSION['full_name'] = $fullName;
            $_SESSION['email'] = $email;
            $message = 'Dean profile and credentials updated successfully!';
            
            // Refresh user
            $stmt->execute();
            $deanUser = $stmt->fetch();
        } catch (Exception $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

// Fetch all registered Club Lead Accounts
$clubAdmins = $db->query("
    SELECT u.*, c.name as club_name, c.short_name as club_short
    FROM users u
    LEFT JOIN club_admins ca ON ca.user_id = u.id
    LEFT JOIN clubs c ON ca.club_id = c.id
    WHERE u.role = 'club_admin'
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Accounts & Governance | Dean Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f1f5f9; font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">
        <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">ACCOUNTS & PRIVILEGES</span>
            <h2 class="fw-bold mb-1">Club Accounts & Dean Profile</h2>
            <p class="text-secondary small mb-0">Manage chapter lead access credentials and Dean of Student Affairs administrative profile.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Dean Profile Card -->
            <div class="col-lg-5">
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
                    <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                        <div class="bg-indigo text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 52px; height: 52px; background: linear-gradient(135deg,#6366f1,#a855f7);">
                            D
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0 text-dark"><?= e($deanUser['full_name']) ?></h5>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2.5 py-0-5 small">DEAN OF STUDENT AFFAIRS</span>
                        </div>
                    </div>

                    <form action="users.php" method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Dean Full Name</label>
                            <input type="text" name="full_name" class="form-control" value="<?= e($deanUser['full_name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Official Dean Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($deanUser['email']) ?>" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">New Password (leave blank to keep current)</label>
                            <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                        </div>

                        <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold py-2.5 text-white shadow-sm">
                            <i class="bi bi-shield-check me-1"></i> Update Dean Credentials
                        </button>
                    </form>
                </div>
            </div>

            <!-- Registered Club Lead Accounts List -->
            <div class="col-lg-7">
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Club Leadership Accounts</h5>
                            <span class="text-secondary small">Active student chapter admin logins</span>
                        </div>
                        <a href="clubs.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">+ Add Club Admin</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-secondary">
                                    <th>ADMIN NAME</th>
                                    <th>CHAPTER</th>
                                    <th>LOGIN EMAIL</th>
                                    <th>STATUS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clubAdmins)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-3 text-muted">No club lead accounts registered.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($clubAdmins as $ca): ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= e($ca['full_name']) ?></td>
                                            <td><span class="badge bg-success-subtle text-success border rounded-pill px-2.5 py-1 small"><?= e($ca['club_short'] ?: $ca['club_name'] ?: 'Unassigned') ?></span></td>
                                            <td class="small font-monospace text-secondary"><?= e($ca['email']) ?></td>
                                            <td><span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small">Active</span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
