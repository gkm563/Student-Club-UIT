<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: /admin/dean-login.php');
    exit;
}

$db = Database::getConnection();

// Handle Delete Message
if (isset($_GET['delete_msg'])) {
    $msgId = $_GET['delete_msg'];
    $db->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$msgId]);
    header('Location: /admin/super/messages.php?msg=Deleted');
    exit;
}

// Fetch Contact Messages
$messages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages | Dean Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { background: #f8fafc; }
        .admin-sidebar { width: 260px; min-height: 100vh; background: #0b0f19; color: #fff; }
        .admin-nav-link { color: rgba(255,255,255,0.7); padding: 12px 18px; border-radius: 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; font-weight: 500; }
        .admin-nav-link:hover, .admin-nav-link.active { background: #6366f1; color: #fff; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="admin-sidebar p-3 flex-shrink-0 d-none d-md-block">
        <div class="d-flex align-items-center gap-3 mb-4 p-2">
            <img src="/assets/United Logo.webp" style="height: 38px;">
            <div>
                <span class="fw-bold d-block lh-1">ClubHub</span>
                <span class="small text-white-50" style="font-size: 0.65rem;">DEAN PORTAL</span>
            </div>
        </div>

        <nav class="d-flex flex-column gap-2">
            <a href="/admin/super/index.php" class="admin-nav-link"><i class="bi bi-speedometer2"></i> Overview</a>
            <a href="/admin/super/clubs.php" class="admin-nav-link"><i class="bi bi-trophy"></i> Manage Clubs</a>
            <a href="/admin/super/categories.php" class="admin-nav-link"><i class="bi bi-tags"></i> Categories</a>
            <a href="/admin/super/logs.php" class="admin-nav-link"><i class="bi bi-journal-text"></i> Audit Logs</a>
            <a href="/admin/super/messages.php" class="admin-nav-link active"><i class="bi bi-envelope"></i> Messages</a>
            <a href="/admin/super/users.php" class="admin-nav-link"><i class="bi bi-shield-lock"></i> Dean Profile</a>
            <a href="/admin/logout.php" class="admin-nav-link text-danger mt-4"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4 p-md-5">
        <div class="mb-4">
            <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">INQUIRIES</span>
            <h2 class="fw-bold mb-1">Student & Visitor Messages</h2>
            <p class="text-secondary small mb-0">Review inquiries and contact messages submitted through the website.</p>
        </div>

        <!-- Messages Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Sender</th>
                            <th>Subject</th>
                            <th>Message</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No contact messages received yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $m): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block"><?= htmlspecialchars($m['name']) ?></strong>
                                        <span class="small text-muted"><?= htmlspecialchars($m['email']) ?></span>
                                    </td>
                                    <td><span class="fw-semibold text-dark"><?= htmlspecialchars($m['subject']) ?></span></td>
                                    <td><span class="small text-secondary"><?= htmlspecialchars(substr($m['message'], 0, 70)) ?>...</span></td>
                                    <td><span class="small text-muted"><?= date('d M Y', strtotime($m['created_at'])) ?></span></td>
                                    <td>
                                        <a href="/admin/super/messages.php?delete_msg=<?= $m['id'] ?>" onclick="return confirm('Delete message?');" class="btn btn-sm btn-outline-danger rounded-circle">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
