<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login('/admin/club-login.php');

$userRole = get_current_user_role();
if ($userRole === 'super_admin') {
    header('Location: /admin/super/index.php');
    exit;
}

$db = Database::getConnection();

// Fetch assigned club for this user
$stmt = $db->prepare("
    SELECT c.*, cat.name as category_name
    FROM clubs c
    JOIN club_admins ca ON ca.club_id = c.id
    JOIN categories cat ON c.category_id = cat.id
    WHERE ca.user_id = ?
    LIMIT 1
");
$stmt->execute([get_current_user_id()]);
$club = $stmt->fetch();

if (!$club) {
    echo "No club assigned to your account. Please contact Dean Sir (admin@uit.edu).";
    exit;
}

$message = '';
$error = '';

// Update Recruitment Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_recruitment'])) {
    $recOpen = isset($_POST['recruitment_open']) ? 1 : 0;
    $recLink = trim($_POST['recruitment_link'] ?? '/contact.html');
    $recDeadline = !empty($_POST['recruitment_deadline']) ? $_POST['recruitment_deadline'] : null;
    $recEligibility = trim($_POST['recruitment_eligibility'] ?? '');

    try {
        $uStmt = $db->prepare("
            UPDATE clubs SET 
                recruitment_open = ?, recruitment_link = ?, recruitment_deadline = ?, recruitment_eligibility = ?
            WHERE id = ?
        ");
        $uStmt->execute([$recOpen, $recLink, $recDeadline, $recEligibility, $club['id']]);
        $message = "Recruitment settings updated successfully!";
        // Refresh club data
        $stmt->execute([get_current_user_id()]);
        $club = $stmt->fetch();
    } catch (Exception $e) {
        $error = "Failed to update recruitment: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recruitment & Achievements | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f8fafc; }
        .admin-sidebar { width: 260px; min-height: 100vh; background: #0b0f19; color: #fff; }
        .admin-nav-link {
            color: rgba(255,255,255,0.65);
            padding: 11px 16px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            margin-bottom: 2px;
        }
        .admin-nav-link i { font-size: 1.1rem; width: 20px; text-align: center; }
        .admin-nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateX(3px); }
        .admin-nav-link.active { background: linear-gradient(135deg, #6366f1, #4f46e5); color: #fff; box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        .border-white-10 { border-color: rgba(255,255,255,0.1) !important; }
        .admin-nav-link:hover, .admin-nav-link.active { background: #6366f1; color: #fff; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Master Sidebar -->
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small"><?= htmlspecialchars($club['name']) ?></span>
                <h2 class="fw-bold mb-1">Student Recruitment Drive Settings</h2>
                <p class="text-secondary small mb-0">Configure recruitment status, application links, and eligibility criteria.</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 max-w-xl">
            <h5 class="fw-bold mb-3"><i class="bi bi-person-plus text-primary me-2"></i> Recruitment Drive Form</h5>
            <form action="/admin/recruitment.php" method="POST">
                <input type="hidden" name="action_recruitment" value="1">
                
                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="recruitment_open" id="recOpenSwitch" <?= $club['recruitment_open'] ? 'checked' : '' ?>>
                    <label class="form-check-label fw-bold text-dark" for="recOpenSwitch">Recruitment Open & Accepting Applications</label>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Application Form / Registration Link</label>
                    <input type="text" name="recruitment_link" class="form-control rounded-3" value="<?= htmlspecialchars($club['recruitment_link'] ?? '/contact.html') ?>" placeholder="https://forms.google.com/...">
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Recruitment Application Deadline</label>
                    <input type="date" name="recruitment_deadline" class="form-control rounded-3" value="<?= htmlspecialchars($club['recruitment_deadline'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold">Eligibility & Prerequisites</label>
                    <textarea name="recruitment_eligibility" class="form-control rounded-3" rows="3" placeholder="e.g. Open for all engineering & tech students from 1st to 4th year..."><?= htmlspecialchars($club['recruitment_eligibility'] ?? '') ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary rounded-pill px-5 py-2-5 fw-bold text-white shadow-sm">Save Recruitment Settings</button>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
