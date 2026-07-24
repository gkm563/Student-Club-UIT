<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_club_admin();

$db = Database::getConnection();
$clubId = get_assigned_club_id();

if (!$clubId) {
    $club = $db->query("SELECT * FROM clubs ORDER BY created_at ASC LIMIT 1")->fetch();
    $clubId = $club['id'] ?? null;
} else {
    $stmt = $db->prepare("SELECT * FROM clubs WHERE id = ?");
    $stmt->execute([$clubId]);
    $club = $stmt->fetch();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create_act'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $title   = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $tag     = trim($_POST['tag'] ?? 'General');

        if (empty($title) || empty($content)) {
            $error = "Title and content are required.";
        } else {
            try {
                $actId = generate_uuid();
                $stmtIns = $db->prepare("INSERT INTO activities (id, club_id, title, content, tag, status) VALUES (?, ?, ?, ?, ?, 'published')");
                $stmtIns->execute([$actId, $clubId, $title, $content, $tag]);
                $success = "Activity post published to global campus feed!";
                log_audit($db, get_current_user_id(), get_current_user_name(), 'ACTIVITY_CREATE', 'activity', $actId, "Published activity: $title");
            } catch (Exception $e) {
                $error = "Failed to publish post: " . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM activities WHERE id = ? AND club_id = ?");
    $stmtDel->execute([$delId, $clubId]);
    $success = "Post removed.";
}

$stmtAct = $db->prepare("SELECT * FROM activities WHERE club_id = ? ORDER BY created_at DESC");
$stmtAct->execute([$clubId]);
$activities = $stmtAct->fetchAll();

$pageTitle = "Activity Blog Posts | CCMS Admin";
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 px-0 admin-sidebar p-3">
            <div class="px-2 mb-3">
                <span class="small text-muted text-uppercase fw-bold">Club Management</span>
                <h6 class="fw-bold text-primary mb-0 mt-1"><?= e($club['short_name'] ?? 'Club Admin') ?></h6>
            </div>
            <nav class="d-flex flex-column">
                <a href="/admin/dashboard.php" class="admin-nav-link"><i class="bi bi-speedometer2"></i> Dashboard</a>
                <a href="/admin/profile.php" class="admin-nav-link"><i class="bi bi-pencil-square"></i> Edit Profile</a>
                <a href="/admin/events.php" class="admin-nav-link"><i class="bi bi-calendar-event"></i> Manage Events</a>
                <a href="/admin/activities.php" class="admin-nav-link active"><i class="bi bi-newspaper"></i> Activity Posts</a>
                <a href="/admin/members.php" class="admin-nav-link"><i class="bi bi-people"></i> Roster & Officers</a>
                <hr class="my-2 border-secondary-subtle">
                <a href="/admin/logout.php" class="admin-nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Activity Feed Posts</h2>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#createActModal">
                    <i class="bi bi-plus-lg me-1"></i> New Activity Post
                </button>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success rounded-3 small mb-3"><i class="bi bi-check-circle-fill me-1"></i> <?= e($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger rounded-3 small mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <div class="card p-4 ccms-card">
                <div class="d-flex flex-column gap-3">
                    <?php if (empty($activities)): ?>
                        <div class="p-4 text-center text-muted">No activity blog posts yet. Publish one to inform students about recent club achievements or announcements!</div>
                    <?php else: ?>
                        <?php foreach ($activities as $act): ?>
                            <div class="p-3 bg-body-tertiary rounded-3 border d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill small"><?= e($act['tag']) ?></span>
                                        <span class="small text-muted"><?= time_ago($act['created_at']) ?></span>
                                    </div>
                                    <h6 class="fw-bold mb-1"><?= e($act['title']) ?></h6>
                                    <p class="small text-secondary mb-0"><?= e($act['content']) ?></p>
                                </div>
                                <a href="/admin/activities.php?delete=<?= e($act['id']) ?>" onclick="return confirm('Delete this post?');" class="btn btn-sm btn-outline-danger rounded-circle px-2 py-1 ms-3">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create Activity Modal -->
<div class="modal fade" id="createActModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Publish Activity Post</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/activities.php" method="POST">
                <input type="hidden" name="action_create_act" value="1">
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Recruitment 2026 Phase 1 Open!" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tag / Category</label>
                        <input type="text" name="tag" class="form-control" placeholder="e.g. Announcement, Workshop, Achievement" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Content</label>
                        <textarea name="content" class="form-control" rows="4" placeholder="Write update text..." required></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold">Publish Post</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
