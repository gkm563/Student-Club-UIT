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
$newCredentials = null;

// Handle Create New Club & Credentials
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_club') {
    $name = trim($_POST['name'] ?? '');
    $shortName = trim($_POST['short_name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 1);
    $tagline = trim($_POST['tagline'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPassword = trim($_POST['admin_password'] ?? '');
    $adminName = trim($_POST['admin_name'] ?? ($name . ' President / Lead'));

    if (empty($name) || empty($shortName) || empty($adminEmail) || empty($adminPassword)) {
        $error = 'Please fill in all required fields (Club Name, Short Code, Admin Email & Password).';
    } else {
        try {
            $clubId = 'clb_' . bin2hex(random_bytes(4));
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $shortName)) . '-' . rand(100, 999);
            
            // 1. Insert Club
            $stmt = $db->prepare("
                INSERT INTO clubs (id, name, short_name, slug, category_id, tagline, description, logo, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, '/assets/United Logo.webp', 'active')
            ");
            $stmt->execute([$clubId, $name, $shortName, $slug, $categoryId, $tagline, $description]);

            // 2. Create User Account for Club Leadership
            $userId = 'usr_' . bin2hex(random_bytes(4));
            $passHash = password_hash($adminPassword, PASSWORD_DEFAULT);

            $uStmt = $db->prepare("
                INSERT INTO users (id, email, password_hash, full_name, role, status)
                VALUES (?, ?, ?, ?, 'club_admin', 'active')
            ");
            $uStmt->execute([$userId, $adminEmail, $passHash, $adminName]);

            // 3. Link Club to Admin
            $aStmt = $db->prepare("INSERT INTO club_admins (club_id, user_id) VALUES (?, ?)");
            $aStmt->execute([$clubId, $userId]);

            $newCredentials = [
                'club_name' => $name,
                'email' => $adminEmail,
                'password' => $adminPassword
            ];
            $message = "Club '$name' created successfully! Credentials issued to team.";
        } catch (Exception $e) {
            $error = 'Error creating club: ' . $e->getMessage();
        }
    }
}

// Handle Password Reset for a Club Admin by Dean Sir
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $userId = $_POST['user_id'] ?? '';
    $newPass = trim($_POST['new_password'] ?? '');

    if (!empty($userId) && !empty($newPass)) {
        try {
            $passHash = password_hash($newPass, PASSWORD_DEFAULT);
            $rStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $rStmt->execute([$passHash, $userId]);
            $message = "Password updated successfully for club leadership account.";
        } catch (Exception $e) {
            $error = "Error resetting password: " . $e->getMessage();
        }
    }
}

// Handle Status Toggle or Delete
if (isset($_GET['toggle_status']) && isset($_GET['club_id'])) {
    $clubId = $_GET['club_id'];
    $currentStatus = $_GET['toggle_status'];
    $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
    
    $stmt = $db->prepare("UPDATE clubs SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $clubId]);
    header('Location: clubs.php?msg=Status+updated');
    exit;
}

if (isset($_GET['delete_club'])) {
    $clubId = $_GET['delete_club'];
    $stmt = $db->prepare("DELETE FROM clubs WHERE id = ?");
    $stmt->execute([$clubId]);
    header('Location: clubs.php?msg=Club+deleted');
    exit;
}

// Fetch all categories
$catStmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll();

// Fetch all registered clubs with admin details
$clubsStmt = $db->query("
    SELECT c.*, cat.name as category_name, u.id as user_id, u.email as admin_email, u.full_name as admin_name
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN club_admins ca ON ca.club_id = c.id
    LEFT JOIN users u ON ca.user_id = u.id
    ORDER BY c.created_at DESC
");
$registeredClubs = $clubsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Portal - Club Management | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
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

    <!-- Main Content -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">SUPER ADMIN / DEAN PORTAL</span>
                <h2 class="fw-bold mb-1">Club Management</h2>
                <p class="text-secondary small mb-0">Create new clubs, issue leadership credentials, and manage campus organizations.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#createClubModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Club
            </button>
        </div>

        <?php if ($newCredentials): ?>
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-success-subtle border-success">
                <div class="d-flex align-items-center gap-3 mb-2">
                    <i class="bi bi-key-fill fs-2 text-success"></i>
                    <div>
                        <h5 class="fw-bold mb-0 text-success">Credentials Issued for <?= htmlspecialchars($newCredentials['club_name']) ?>!</h5>
                        <p class="small text-secondary mb-0">Share these login credentials with the Club President / Core Team.</p>
                    </div>
                </div>
                <div class="bg-white p-3 rounded-3 border mt-2 font-monospace small">
                    <div><strong>Login URL:</strong> <a href="/admin/login.php" target="_blank">http://localhost:8000/admin/login.php</a></div>
                    <div><strong>Admin Email:</strong> <code><?= htmlspecialchars($newCredentials['email']) ?></code></div>
                    <div><strong>Initial Password:</strong> <code><?= htmlspecialchars($newCredentials['password']) ?></code></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($message) && !$newCredentials): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Clubs Search & Sorting Controls -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <div class="row g-3 align-items-center">
                <div class="col-md-6 col-lg-7">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" id="clubSearchInput" class="form-control border-start-0" placeholder="Search clubs by name, short code, category, or email...">
                    </div>
                </div>
                <div class="col-md-3 col-lg-3">
                    <select id="clubStatusFilter" class="form-select">
                        <option value="all">All Club Statuses</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
                <div class="col-md-3 col-lg-2">
                    <select id="clubSortOrder" class="form-select">
                        <option value="name-asc">Name: A &rarr; Z</option>
                        <option value="name-desc">Name: Z &rarr; A</option>
                        <option value="category-asc">Category</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Clubs List Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Registered Campus Clubs (<span id="clubCountBadge"><?= count($registeredClubs) ?></span>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="clubsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Club Name</th>
                            <th>Category</th>
                            <th>Admin Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($registeredClubs)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                    No clubs created yet. Click "Add New Club" to register a club and generate leadership credentials.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($registeredClubs as $c): ?>
                                <tr data-name="<?= e($c['name']) ?>" data-short="<?= e($c['short_name']) ?>" data-category="<?= e($c['category_name']) ?>" data-email="<?= e($c['admin_email'] ?? '') ?>" data-status="<?= e($c['status']) ?>">
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= htmlspecialchars($c['logo']) ?>" class="rounded-3 border" style="width: 40px; height: 40px; object-fit: contain;">
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($c['name']) ?></h6>
                                                <span class="small text-muted"><?= htmlspecialchars($c['short_name']) ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1"><?= htmlspecialchars($c['category_name']) ?></span></td>
                                    <td>
                                        <span class="fw-semibold text-dark"><i class="bi bi-envelope me-1"></i> <?= htmlspecialchars($c['admin_email'] ?? 'Not Assigned') ?></span>
                                    </td>
                                    <td>
                                        <?php if ($c['status'] === 'active'): ?>
                                            <span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill me-1" data-bs-toggle="modal" data-bs-target="#resetPassModal<?= $c['id'] ?>" title="Reset Password">
                                            <i class="bi bi-key"></i> Pass
                                        </button>
                                        <a href="/admin/super/clubs.php?toggle_status=<?= $c['status'] ?>&club_id=<?= $c['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill me-1" title="Toggle Status">
                                            <i class="bi bi-power"></i>
                                        </a>
                                        <a href="/admin/super/clubs.php?delete_club=<?= $c['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Are you sure you want to delete this club?');" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>

                                <!-- Reset Password Modal for Club -->
                                <div class="modal fade" id="resetPassModal<?= $c['id'] ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered modal-sm">
                                        <div class="modal-content rounded-4 border-0 shadow">
                                            <div class="modal-header border-0 pb-0">
                                                <h6 class="fw-bold modal-title">Reset Password</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="/admin/super/clubs.php" method="POST">
                                                <input type="hidden" name="action" value="reset_password">
                                                <input type="hidden" name="user_id" value="<?= $c['user_id'] ?>">
                                                <div class="modal-body">
                                                    <p class="small text-muted mb-2">Set a new password for <strong><?= htmlspecialchars($c['name']) ?></strong> (<?= htmlspecialchars($c['admin_email']) ?>):</p>
                                                    <input type="password" name="new_password" class="form-control rounded-3" placeholder="Enter new password" required>
                                                </div>
                                                <div class="modal-footer border-0 pt-0">
                                                    <button type="button" class="btn btn-light btn-sm rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill fw-bold text-white">Save Password</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create New Club & Assign Credentials -->
<div class="modal fade" id="createClubModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold modal-title"><i class="bi bi-plus-circle text-primary me-2"></i> Add New Club & Issue Credentials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/super/clubs.php" method="POST">
                <input type="hidden" name="action" value="create_club">
                <div class="modal-body space-y-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Club Name *</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. CodeCrafters" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Short Code *</label>
                            <input type="text" name="short_name" class="form-control rounded-3" placeholder="e.g. CODE" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category *</label>
                            <select name="category_id" class="form-select rounded-3" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tagline</label>
                        <input type="text" name="tagline" class="form-control rounded-3" placeholder="e.g. Coding & Development Club">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Initial Description</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Brief club summary..."></textarea>
                    </div>

                    <hr class="my-4">

                    <h6 class="fw-bold text-primary mb-3"><i class="bi bi-key me-1"></i> Leadership Credentials (Issued by Dean Sir)</h6>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Club President / Lead Name</label>
                        <input type="text" name="admin_name" class="form-control rounded-3" placeholder="e.g. Rahul Verma">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Club Admin Email *</label>
                        <input type="email" name="admin_email" class="form-control rounded-3" placeholder="e.g. codecrafters@uit.edu" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Initial Password *</label>
                        <input type="password" name="admin_password" class="form-control rounded-3" placeholder="Set initial password for team" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Create Club & Credentials</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('clubSearchInput');
    const statusFilter = document.getElementById('clubStatusFilter');
    const sortOrder = document.getElementById('clubSortOrder');
    const tableBody = document.querySelector('#clubsTable tbody');
    const countBadge = document.getElementById('clubCountBadge');
    
    if (!tableBody) return;
    const rows = Array.from(tableBody.querySelectorAll('tr[data-name]'));

    function filterAndSortClubs() {
        const query = (searchInput.value || '').toLowerCase().trim();
        const selectedStatus = statusFilter.value;
        const selectedSort = sortOrder.value;

        let visibleRows = rows.filter(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const shortName = (row.dataset.short || '').toLowerCase();
            const category = (row.dataset.category || '').toLowerCase();
            const email = (row.dataset.email || '').toLowerCase();
            const status = (row.dataset.status || '').toLowerCase();

            const matchesQuery = !query || name.includes(query) || shortName.includes(query) || category.includes(query) || email.includes(query);
            const matchesStatus = (selectedStatus === 'all') || (status === selectedStatus);

            return matchesQuery && matchesStatus;
        });

        // Sort visible rows
        visibleRows.sort((a, b) => {
            if (selectedSort === 'name-asc') {
                return (a.dataset.name || '').localeCompare(b.dataset.name || '');
            } else if (selectedSort === 'name-desc') {
                return (b.dataset.name || '').localeCompare(a.dataset.name || '');
            } else if (selectedSort === 'category-asc') {
                return (a.dataset.category || '').localeCompare(b.dataset.category || '');
            }
            return 0;
        });

        // Re-append sorted rows
        tableBody.innerHTML = '';
        if (visibleRows.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No campus clubs matching your search.</td></tr>`;
        } else {
            visibleRows.forEach(row => tableBody.appendChild(row));
        }

        if (countBadge) countBadge.textContent = visibleRows.length;
    }

    searchInput?.addEventListener('input', filterAndSortClubs);
    statusFilter?.addEventListener('change', filterAndSortClubs);
    sortOrder?.addEventListener('change', filterAndSortClubs);
});
</script>
</body>
</html>
