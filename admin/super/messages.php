<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../dean-login.php');
    exit;
}

$db = Database::getConnection();

// Handle Delete Message
if (isset($_GET['delete_msg'])) {
    $msgId = $_GET['delete_msg'];
    $db->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$msgId]);
    header('Location: messages.php?msg=Deleted');
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
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">HELP DESK & INQUIRIES</span>
                <h2 class="fw-bold mb-1">Student & Visitor Messages</h2>
                <p class="text-secondary small mb-0">Review student inquiries, proposal notes, and helpdesk tickets submitted through the portal.</p>
            </div>
            <span class="badge bg-indigo text-white rounded-pill px-3 py-2 fw-bold" style="background:#6366f1;">
                <i class="bi bi-inbox-fill me-1"></i> Total Messages: <?= count($messages) ?>
            </span>
        </div>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> Message record updated successfully!</div>
        <?php endif; ?>

        <!-- Messages Table Card -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr class="small text-secondary">
                            <th>SENDER</th>
                            <th>SUBJECT</th>
                            <th>MESSAGE PREVIEW</th>
                            <th>DATE & TIME</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    No contact messages received yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $m): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block"><?= htmlspecialchars($m['name']) ?></strong>
                                        <span class="small font-monospace text-muted"><?= htmlspecialchars($m['email']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($m['subject']) ?></span>
                                    </td>
                                    <td>
                                        <span class="small text-secondary"><?= htmlspecialchars(substr($m['message'], 0, 75)) ?>...</span>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?= date('M j, Y - g:i A', strtotime($m['created_at'])) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <a href="mailto:<?= urlencode($m['email']) ?>?subject=Re:%20<?= urlencode($m['subject']) ?>" class="btn btn-sm btn-light rounded-circle me-1" title="Reply via Email">
                                            <i class="bi bi-reply-fill text-primary"></i>
                                        </a>
                                        <a href="messages.php?delete_msg=<?= $m['id'] ?>" onclick="return confirm('Delete this message permanently?');" class="btn btn-sm btn-light text-danger rounded-circle" title="Delete">
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
