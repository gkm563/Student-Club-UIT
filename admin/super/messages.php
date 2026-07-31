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
$message = '';
$error = '';

// ── 1. Handle Toggle Read Status ────────────────────────────────────
if (isset($_GET['toggle_read']) && isset($_GET['msg_id'])) {
    $msgId = $_GET['msg_id'];
    $current = intval($_GET['toggle_read']);
    $newStatus = ($current === 1) ? 0 : 1;

    try {
        $stmt = $db->prepare("UPDATE contact_messages SET is_read = ? WHERE id = ?");
        $stmt->execute([$newStatus, $msgId]);
        header('Location: messages.php?msg=Status+updated');
        exit;
    } catch (Exception $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

// ── 2. Handle Delete Message ────────────────────────────────────────
if (isset($_GET['delete_msg'])) {
    $msgId = $_GET['delete_msg'];
    try {
        $stmt = $db->prepare("DELETE FROM contact_messages WHERE id = ?");
        $stmt->execute([$msgId]);

        // Audit Log
        $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'MESSAGE_DELETED', "Deleted contact message ID: $msgId"]);

        header('Location: messages.php?msg=Deleted');
        exit;
    } catch (Exception $e) {
        $error = "Error deleting message: " . $e->getMessage();
    }
}

// Fetch Contact Messages
$messages = $db->query("SELECT * FROM contact_messages ORDER BY created_at DESC")->fetchAll();
$unreadCount = array_reduce($messages, fn($acc, $m) => $acc + ($m['is_read'] == 0 ? 1 : 0), 0);
$readCount = count($messages) - $unreadCount;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages & Helpdesk | Dean Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, sans-serif; }
        .stat-card { border: none; border-radius: 18px; background: #ffffff; }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">
    <!-- Universal Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">
        
        <!-- Header Banner -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">USC UIT HELPDESK & INQUIRIES</span>
                <h2 class="fw-bold mb-1 text-dark">Student & Visitor Messages</h2>
                <p class="text-secondary small mb-0">Review student helpdesk tickets, proposal notes, and general inquiries submitted through the website.</p>
            </div>
            <span class="badge bg-indigo text-white rounded-pill px-3 py-2 fw-bold" style="background:#6366f1;">
                <i class="bi bi-inbox-fill me-1"></i> Total Messages: <?= count($messages) ?>
            </span>
        </div>

        <!-- Alert Feedback -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> Message record updated successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- KPI Metric Cards Grid -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stat-card p-3 p-md-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">TOTAL INQUIRIES</span>
                            <h3 class="fw-bold text-dark mb-0"><?= count($messages) ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-primary-subtle text-primary fs-3"><i class="bi bi-envelope-open-fill"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card p-3 p-md-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">UNREAD MESSAGES</span>
                            <h3 class="fw-bold text-warning mb-0"><?= $unreadCount ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-warning-subtle text-warning fs-3"><i class="bi bi-envelope-exclamation-fill"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card p-3 p-md-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">READ / ARCHIVED</span>
                            <h3 class="fw-bold text-success mb-0"><?= $readCount ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-success-subtle text-success fs-3"><i class="bi bi-check-all"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Instant Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <div class="row g-3 align-items-center">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="msgSearchInput" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Instant search by sender name, email, or subject...">
                    </div>
                </div>
                <div class="col-md-6">
                    <select id="msgStatusFilter" class="form-select rounded-pill">
                        <option value="all">Filter Status: All Messages</option>
                        <option value="unread">Unread Only</option>
                        <option value="read">Read Only</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Messages Table Card -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="messagesTable">
                    <thead class="table-light">
                        <tr class="small text-secondary">
                            <th>STATUS</th>
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
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
                                    No contact messages received yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($messages as $m): 
                                $isUnread = ($m['is_read'] == 0);
                            ?>
                                <tr data-sender="<?= htmlspecialchars($m['name']) ?>"
                                    data-email="<?= htmlspecialchars($m['email']) ?>"
                                    data-subject="<?= htmlspecialchars($m['subject']) ?>"
                                    data-status="<?= $isUnread ? 'unread' : 'read' ?>">
                                    <td>
                                        <a href="messages.php?toggle_read=<?= $m['is_read'] ?>&msg_id=<?= $m['id'] ?>" title="Click to Toggle Read Status">
                                            <?php if ($isUnread): ?>
                                                <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1 small"><i class="bi bi-envelope-fill me-1"></i> Unread</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-secondary border rounded-pill px-2.5 py-1 small"><i class="bi bi-envelope-open me-1"></i> Read</span>
                                            <?php endif; ?>
                                        </a>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block"><?= htmlspecialchars($m['name']) ?></strong>
                                        <span class="small font-monospace text-muted"><?= htmlspecialchars($m['email']) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold text-dark"><?= htmlspecialchars($m['subject']) ?></span>
                                    </td>
                                    <td>
                                        <span class="small text-secondary"><?= htmlspecialchars(substr($m['message'], 0, 65)) ?>...</span>
                                    </td>
                                    <td>
                                        <span class="small text-muted"><?= date('M j, Y - g:i A', strtotime($m['created_at'])) ?></span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <!-- Full View Modal Trigger -->
                                            <button type="button" class="btn btn-sm btn-light rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#viewMsgModal<?= $m['id'] ?>" title="View Full Message">
                                                <i class="bi bi-eye-fill text-primary"></i>
                                            </button>

                                            <!-- Reply via Email -->
                                            <a href="mailto:<?= urlencode($m['email']) ?>?subject=Re:%20<?= urlencode($m['subject']) ?>" class="btn btn-sm btn-light rounded-circle me-1" title="Reply via Email">
                                                <i class="bi bi-reply-fill text-success"></i>
                                            </a>

                                            <!-- Delete -->
                                            <a href="messages.php?delete_msg=<?= $m['id'] ?>" onclick="return confirm('Delete this message permanently?');" class="btn btn-sm btn-light text-danger rounded-circle" title="Delete Message">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>

                                        <!-- Full Message Detail View Modal -->
                                        <div class="modal fade text-start" id="viewMsgModal<?= $m['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-envelope-open-fill text-primary me-2"></i> Message Inquiry Details</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="p-3 bg-light rounded-3 border mb-3">
                                                            <div class="row g-2">
                                                                <div class="col-md-6">
                                                                    <div class="small text-secondary fw-semibold">SENDER NAME</div>
                                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['name']) ?></div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="small text-secondary fw-semibold">SENDER EMAIL</div>
                                                                    <div class="font-monospace text-primary"><?= htmlspecialchars($m['email']) ?></div>
                                                                </div>
                                                                <div class="col-md-6 mt-2">
                                                                    <div class="small text-secondary fw-semibold">SUBMITTED ON</div>
                                                                    <div class="small text-dark"><?= date('F j, Y - g:i:s A', strtotime($m['created_at'])) ?></div>
                                                                </div>
                                                                <div class="col-md-6 mt-2">
                                                                    <div class="small text-secondary fw-semibold">STATUS</div>
                                                                    <span class="badge bg-info text-dark rounded-pill px-2.5 py-0-5 small"><?= $isUnread ? 'Unread Ticket' : 'Reviewed' ?></span>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold text-secondary">SUBJECT</label>
                                                            <div class="fw-bold text-dark border-bottom pb-2"><?= htmlspecialchars($m['subject']) ?></div>
                                                        </div>

                                                        <div>
                                                            <label class="form-label small fw-semibold text-secondary">MESSAGE BODY</label>
                                                            <div class="p-3 bg-white border rounded-3 text-secondary lh-base" style="white-space: pre-wrap; font-size: 0.92rem;"><?= htmlspecialchars($m['message']) ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                                                        <a href="mailto:<?= urlencode($m['email']) ?>?subject=Re:%20<?= urlencode($m['subject']) ?>" class="btn btn-primary rounded-pill px-4 fw-bold text-white">
                                                            <i class="bi bi-reply-fill me-1"></i> Reply via Email
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('msgSearchInput');
    const statusFilter = document.getElementById('msgStatusFilter');
    const tableBody = document.querySelector('#messagesTable tbody');
    
    if (!tableBody) return;
    const rows = Array.from(tableBody.querySelectorAll('tr[data-sender]'));

    function filterMessages() {
        const query = (searchInput.value || '').toLowerCase().trim();
        const selectedStatus = statusFilter.value;

        rows.forEach(row => {
            const sender = (row.dataset.sender || '').toLowerCase();
            const email = (row.dataset.email || '').toLowerCase();
            const subject = (row.dataset.subject || '').toLowerCase();
            const status = (row.dataset.status || '').toLowerCase();

            const matchesQuery = !query || sender.includes(query) || email.includes(query) || subject.includes(query);
            const matchesStatus = (selectedStatus === 'all') || (status === selectedStatus);

            row.style.display = (matchesQuery && matchesStatus) ? '' : 'none';
        });
    }

    searchInput?.addEventListener('input', filterMessages);
    statusFilter?.addEventListener('change', filterMessages);
});
</script>
</body>
</html>
