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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_member'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Security token invalid.";
    } else {
        $name     = trim($_POST['name'] ?? '');
        $role_title= trim($_POST['role_title'] ?? '');
        $category = $_POST['category'] ?? 'core_member';
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');

        if (empty($name) || empty($role_title)) {
            $error = "Name and role title are required.";
        } else {
            try {
                $leadId = generate_uuid();
                $stmtIns = $db->prepare("INSERT INTO leadership (id, club_id, name, role_title, category, email, phone, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, 5)");
                $stmtIns->execute([$leadId, $clubId, $name, $role_title, $category, $email, $phone]);
                $success = "Officer added to roster successfully!";
                log_audit($db, get_current_user_id(), get_current_user_name(), 'MEMBER_ADD', 'leadership', $leadId, "Added officer: $name ($role_title)");
            } catch (Exception $e) {
                $error = "Failed to add member: " . $e->getMessage();
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM leadership WHERE id = ? AND club_id = ?");
    $stmtDel->execute([$delId, $clubId]);
    $success = "Officer record removed.";
}

$stmtL = $db->prepare("SELECT * FROM leadership WHERE club_id = ? ORDER BY order_index ASC");
$stmtL->execute([$clubId]);
$leaders = $stmtL->fetchAll();

$pageTitle = "Leadership Roster | CCMS Admin";
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
                <a href="/admin/activities.php" class="admin-nav-link"><i class="bi bi-newspaper"></i> Activity Posts</a>
                <a href="/admin/members.php" class="admin-nav-link active"><i class="bi bi-people"></i> Roster & Officers</a>
                <hr class="my-2 border-secondary-subtle">
                <a href="/admin/logout.php" class="admin-nav-link text-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">Leadership & Officers Roster</h2>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class="bi bi-person-plus-fill me-1"></i> Add Officer
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
                                <th>Officer Name</th>
                                <th>Role Title</th>
                                <th>Category</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($leaders)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No officers on roster yet. Click "Add Officer" to populate leadership details.</td></tr>
                            <?php else: ?>
                                <?php foreach ($leaders as $l): ?>
                                    <tr>
                                        <td class="fw-semibold"><?= e($l['name']) ?></td>
                                        <td><span class="badge bg-primary-subtle text-primary border rounded-pill"><?= e($l['role_title']) ?></span></td>
                                        <td><?= e(ucwords(str_replace('_', ' ', $l['category']))) ?></td>
                                        <td><?= e($l['email'] ?: 'N/A') ?></td>
                                        <td>
                                            <a href="/admin/members.php?delete=<?= e($l['id']) ?>" onclick="return confirm('Remove officer from roster?');" class="btn btn-sm btn-outline-danger rounded-circle px-2 py-1">
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
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold">Add Officer to Roster</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/members.php" method="POST">
                <input type="hidden" name="action_add_member" value="1">
                <input type="hidden" name="csrf_token" value="<?= e(get_csrf_token()) ?>">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Officer Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Ananya Singh" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role Title</label>
                        <input type="text" name="role_title" class="form-control" placeholder="e.g. Vice President, Technical Lead" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role Category</label>
                        <select name="category" class="form-select">
                            <option value="faculty_coordinator">Faculty Coordinator</option>
                            <option value="president">President</option>
                            <option value="vice_president">Vice President</option>
                            <option value="secretary">Secretary</option>
                            <option value="treasurer">Treasurer</option>
                            <option value="core_member" selected>Core Member</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="officer@uit.edu">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Phone Number</label>
                        <input type="text" name="phone" class="form-control" placeholder="+91 ...">
                    </div>
                </div>

                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold">Add Officer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
