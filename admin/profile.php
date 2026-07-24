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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } else {
        $tagline     = trim($_POST['tagline'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $mission     = trim($_POST['mission'] ?? '');
        $vision      = trim($_POST['vision'] ?? '');
        $objectives  = trim($_POST['objectives'] ?? '');
        
        $recruitment_open        = isset($_POST['recruitment_open']) ? 1 : 0;
        $recruitment_link        = trim($_POST['recruitment_link'] ?? '');
        $recruitment_deadline    = !empty($_POST['recruitment_deadline']) ? $_POST['recruitment_deadline'] : null;
        $recruitment_eligibility = trim($_POST['recruitment_eligibility'] ?? '');

        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $office   = trim($_POST['office_location'] ?? '');
        $website  = trim($_POST['website'] ?? '');
        $github   = trim($_POST['github'] ?? '');
        $linkedin = trim($_POST['linkedin'] ?? '');
        $instagram= trim($_POST['instagram'] ?? '');

        try {
            $stmtUpd = $db->prepare("
                UPDATE clubs SET
                    tagline = ?, description = ?, mission = ?, vision = ?, objectives = ?,
                    recruitment_open = ?, recruitment_link = ?, recruitment_deadline = ?, recruitment_eligibility = ?,
                    email = ?, phone = ?, office_location = ?, website = ?, github = ?, linkedin = ?, instagram = ?
                WHERE id = ?
            ");
            $stmtUpd->execute([
                $tagline, $description, $mission, $vision, $objectives,
                $recruitment_open, $recruitment_link, $recruitment_deadline, $recruitment_eligibility,
                $email, $phone, $office, $website, $github, $linkedin, $instagram,
                $clubId
            ]);
            $success = "Club profile details updated successfully!";
            // Refresh
            $stmt->execute([$clubId]);
            $club = $stmt->fetch();
        } catch (Exception $e) {
            $error = "Failed to update profile: " . $e->getMessage();
        }
    }
}

$pageTitle = "Edit Club Profile | CCMS";
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
                <a href="/admin/profile.php" class="admin-nav-link active"><i class="bi bi-pencil-square"></i> Edit Profile</a>
                <a href="/admin/events.php" class="admin-nav-link"><i class="bi bi-calendar-event"></i> Manage Events</a>
                <a href="/admin/activities.php" class="admin-nav-link"><i class="bi bi-newspaper"></i> Activity Posts</a>
                <a href="/admin/members.php" class="admin-nav-link"><i class="bi bi-people"></i> Roster & Officers</a>
                <hr class="my-2 border-secondary-subtle">
                <a href="/admin/logout.php" class="admin-nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Edit Club Profile</h2>
                <a href="/club-detail.php?slug=<?= e($club['slug']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">View Live Page</a>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success rounded-3 small mb-3"><i class="bi bi-check-circle-fill me-1"></i> <?= e($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger rounded-3 small mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <form action="/admin/profile.php" method="POST" class="card p-4 ccms-card">
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">

                <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-info-circle me-1"></i> Basic Details</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Tagline / Pitch</label>
                        <input type="text" name="tagline" class="form-control" value="<?= e($club['tagline']) ?>" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-semibold">Club Description</label>
                        <textarea name="description" class="form-control" rows="4" required><?= e($club['description']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Mission Statement</label>
                        <textarea name="mission" class="form-control" rows="3"><?= e($club['mission']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Vision Statement</label>
                        <textarea name="vision" class="form-control" rows="3"><?= e($club['vision']) ?></textarea>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Key Objectives</label>
                        <textarea name="objectives" class="form-control" rows="3"><?= e($club['objectives']) ?></textarea>
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-person-plus me-1"></i> Recruitment Settings</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="form-check form-switch mt-4">
                            <input class="form-check-input" type="checkbox" name="recruitment_open" id="recOpenSwitch" <?= $club['recruitment_open'] ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold" for="recOpenSwitch">Recruitment Open</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Application Form Link (Google Form / Portal)</label>
                        <input type="url" name="recruitment_link" class="form-control" value="<?= e($club['recruitment_link']) ?>" placeholder="https://forms.gle/...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Deadline</label>
                        <input type="date" name="recruitment_deadline" class="form-control" value="<?= e($club['recruitment_deadline']) ?>">
                    </div>
                </div>

                <hr class="my-4">

                <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-link-45deg me-1"></i> Contact & Social Handles</h5>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Club Email</label>
                        <input type="email" name="email" class="form-control" value="<?= e($club['email']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Contact Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= e($club['phone']) ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Office Location</label>
                        <input type="text" name="office_location" class="form-control" value="<?= e($club['office_location']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Website</label>
                        <input type="url" name="website" class="form-control" value="<?= e($club['website']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">GitHub</label>
                        <input type="url" name="github" class="form-control" value="<?= e($club['github']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">LinkedIn</label>
                        <input type="url" name="linkedin" class="form-control" value="<?= e($club['linkedin']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Instagram</label>
                        <input type="url" name="instagram" class="form-control" value="<?= e($club['instagram']) ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold align-self-start">
                    <i class="bi bi-save me-1"></i> Save Profile Changes
                </button>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
