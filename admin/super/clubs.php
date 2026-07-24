<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

require_super_admin();

$db = Database::getConnection();
$success = '';
$error = '';

// Onboard New Club
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_onboard_club'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token error.";
    } else {
        $name        = trim($_POST['name'] ?? '');
        $short_name  = trim($_POST['short_name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $tagline     = trim($_POST['tagline'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $founded_year= (int)($_POST['founded_year'] ?? 2026);

        if (empty($name) || empty($short_name) || $category_id <= 0) {
            $error = "Club name, short name, and category are required.";
        } else {
            try {
                $clubId = generate_uuid();
                $slug   = slugify($name);
                $stmtIns = $db->prepare("INSERT INTO clubs (id, name, short_name, slug, category_id, tagline, description, founded_year, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmtIns->execute([$clubId, $name, $short_name, $slug, $category_id, $tagline, $description, $founded_year]);
                $success = "Club '$name' onboarded successfully!";
                log_audit($db, get_current_user_id(), get_current_user_name(), 'CLUB_ONBOARD', 'club', $clubId, "Onboarded club: $name");
            } catch (Exception $e) {
                $error = "Failed to onboard club: " . $e->getMessage();
            }
        }
    }
}

// Soft Delete / Restore
if (isset($_GET['toggle_delete'])) {
    $cId = $_GET['toggle_delete'];
    $stmtC = $db->prepare("SELECT deleted_at FROM clubs WHERE id = ?");
    $stmtC->execute([$cId]);
    $curr = $stmtC->fetchColumn();

    if ($curr) {
        $db->prepare("UPDATE clubs SET deleted_at = NULL WHERE id = ?")->execute([$cId]);
        $success = "Club restored.";
    } else {
        $db->prepare("UPDATE clubs SET deleted_at = NOW() WHERE id = ?")->execute([$cId]);
        $success = "Club soft-deleted (hidden from public directory).";
    }
}

// Fetch all clubs
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();
$clubs = $db->query("
    SELECT c.*, cat.name AS category_name
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    ORDER BY c.created_at DESC
")->fetchAll();

$pageTitle = "Manage Clubs | Super Admin";
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <div class="col-md-3 col-lg-2 px-0 admin-sidebar p-3">
            <div class="px-2 mb-3">
                <span class="small text-danger text-uppercase fw-bold"><i class="bi bi-shield-lock me-1"></i> Super Admin</span>
                <h6 class="fw-bold text-body mb-0 mt-1">Governance Portal</h6>
            </div>
            <nav class="d-flex flex-column">
                <a href="/admin/super/index.php" class="admin-nav-link"><i class="bi bi-speedometer2"></i> System Analytics</a>
                <a href="/admin/super/clubs.php" class="admin-nav-link active"><i class="bi bi-diagram-3"></i> Manage Clubs</a>
                <a href="/admin/super/users.php" class="admin-nav-link"><i class="bi bi-people"></i> Manage Accounts</a>
                <a href="/admin/super/audit-logs.php" class="admin-nav-link"><i class="bi bi-journal-text"></i> Security Audit Logs</a>
                <hr class="my-2 border-secondary-subtle">
                <a href="/admin/logout.php" class="admin-nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Club Governance & Onboarding</h2>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#onboardModal">
                    <i class="bi bi-plus-lg me-1"></i> Onboard New Club
                </button>
            </div>

            <?php if (!empty($success)): ?>
                <div class="alert alert-success rounded-3 small mb-3"><i class="bi bi-check-circle-fill me-1"></i> <?= e($success) ?></div>
            <?php endif; ?>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger rounded-3 small mb-3"><i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($error) ?></div>
            <?php endif; ?>

            <div class="card p-4 ccms-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle small mb-0">
                        <thead>
                            <tr>
                                <th>Club Name</th>
                                <th>Category</th>
                                <th>Founded</th>
                                <th>Status</th>
                                <th>Visibility</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($clubs as $c): ?>
                                <tr class="<?= $c['deleted_at'] ? 'table-secondary opacity-75' : '' ?>">
                                    <td class="fw-semibold">
                                        <a href="/club-detail.php?slug=<?= e($c['slug']) ?>" target="_blank" class="text-decoration-none text-body">
                                            <?= e($c['name']) ?>
                                        </a>
                                        <small class="d-block text-muted"><?= e($c['tagline']) ?></small>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary border rounded-pill"><?= e($c['category_name']) ?></span></td>
                                    <td><?= e($c['founded_year']) ?></td>
                                    <td><?= get_status_badge($c['status']) ?></td>
                                    <td>
                                        <?php if ($c['deleted_at']): ?>
                                            <span class="badge bg-danger text-white">Hidden / Soft-Deleted</span>
                                        <?php else: ?>
                                            <span class="badge bg-success text-white">Live Public</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="/admin/super/clubs.php?toggle_delete=<?= e($c['id']) ?>" class="btn btn-sm <?= $c['deleted_at'] ? 'btn-outline-success' : 'btn-outline-warning' ?> rounded-pill px-3 py-1">
                                            <?= $c['deleted_at'] ? 'Restore' : 'Soft Delete' ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Onboard Club Modal -->
<div class="modal fade" id="onboardModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Onboard New Campus Club</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/super/clubs.php" method="POST">
                <input type="hidden" name="action_onboard_club" value="1">
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Full Club Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. UIT Artificial Intelligence Society" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Short Name / Abbreviation</label>
                        <input type="text" name="short_name" class="form-control" placeholder="e.g. AI Society" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Select Domain --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat['id']) ?>"><?= e($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tagline</label>
                        <input type="text" name="tagline" class="form-control" placeholder="One-line mission pitch...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Founded Year</label>
                        <input type="number" name="founded_year" class="form-control" value="2026">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Detailed organization intro..."></textarea>
                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold">Create & Onboard</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
