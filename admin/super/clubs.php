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
$newCredentials = null;

// ── 1. Handle Create New Club & Issue Credentials ──────────────────
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
            
            // Insert Club
            $stmt = $db->prepare("
                INSERT INTO clubs (id, name, short_name, slug, category_id, tagline, description, logo, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, '../assets/United Logo.webp', 'active')
            ");
            $stmt->execute([$clubId, $name, $shortName, $slug, $categoryId, $tagline, $description]);

            // Create User Account for Club Leadership
            $userId = 'usr_' . bin2hex(random_bytes(4));
            $passHash = password_hash($adminPassword, PASSWORD_DEFAULT);

            $uStmt = $db->prepare("
                INSERT INTO users (id, email, password_hash, full_name, role, status)
                VALUES (?, ?, ?, ?, 'club_admin', 'active')
            ");
            $uStmt->execute([$userId, $adminEmail, $passHash, $adminName]);

            // Link Club to Admin
            $aStmt = $db->prepare("INSERT INTO club_admins (club_id, user_id) VALUES (?, ?)");
            $aStmt->execute([$clubId, $userId]);

            // Audit log
            $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CLUB_CREATED', "Created Club '$name' and issued credentials to $adminEmail"]);

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

// ── 2. Handle Edit Club Details ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_club') {
    $clubId = $_POST['club_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $shortName = trim($_POST['short_name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 1);
    $tagline = trim($_POST['tagline'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!empty($clubId) && !empty($name) && !empty($shortName)) {
        try {
            $stmt = $db->prepare("
                UPDATE clubs 
                SET name = ?, short_name = ?, category_id = ?, tagline = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $shortName, $categoryId, $tagline, $description, $clubId]);

            // Audit log
            $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CLUB_EDITED', "Updated club details for '$name' ($shortName)"]);

            $message = "Club details for '$name' updated successfully!";
        } catch (Exception $e) {
            $error = 'Error updating club: ' . $e->getMessage();
        }
    }
}

// ── 3. Handle Password Reset for a Club Admin ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $userId = $_POST['user_id'] ?? '';
    $newPass = trim($_POST['new_password'] ?? '');

    if (!empty($userId) && !empty($newPass)) {
        try {
            $passHash = password_hash($newPass, PASSWORD_DEFAULT);
            $rStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $rStmt->execute([$passHash, $userId]);

            // Audit log
            $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CREDENTIAL_RESET', "Reset password for user account ID: $userId"]);

            $message = "Password updated successfully for club leadership account.";
        } catch (Exception $e) {
            $error = "Error resetting password: " . $e->getMessage();
        }
    }
}

// ── 4. Handle Status Toggle or Delete ──────────────────────────────
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
    <title>Manage Campus Clubs | Dean Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, sans-serif; }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">
    <!-- Universal Super Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">
        
        <!-- Header Banner -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">SAC CAMPUS GOVERNANCE</span>
                <h2 class="fw-bold mb-1 text-dark">Manage Campus Clubs & Credentials</h2>
                <p class="text-secondary small mb-0">Create new student chapters, issue leadership credentials, reset passwords, and edit chapter details.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 py-2-5 fw-bold shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#createClubModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Club
            </button>
        </div>

        <!-- Feedback Alert Messages -->
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
                    <div><strong>Login URL:</strong> <a href="../../club-login.php" target="_blank">http://<?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') ?>/UIT/club-login.php</a></div>
                    <div><strong>Admin Email:</strong> <code><?= htmlspecialchars($newCredentials['email']) ?></code></div>
                    <div><strong>Initial Password:</strong> <code><?= htmlspecialchars($newCredentials['password']) ?></code></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($message) && !$newCredentials): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Clubs Filter & Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="clubSearchInput" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Instant search by name, short code, or email...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="clubStatusFilter" class="form-select rounded-pill">
                        <option value="all">Filter by Status: All Chapters</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                    </select>
                </div>
                <div class="col-md-3 text-md-end">
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 fw-bold">
                        Showing: <span id="clubCountBadge"><?= count($registeredClubs) ?></span> Clubs
                    </span>
                </div>
            </div>
        </div>

        <!-- Clubs Roster Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="clubsTable">
                    <thead class="table-light">
                        <tr class="small text-secondary">
                            <th>CLUB & CODE</th>
                            <th>CATEGORY</th>
                            <th>PRESIDENT / LEAD EMAIL</th>
                            <th>STATUS</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registeredClubs as $club): ?>
                            <tr data-name="<?= htmlspecialchars($club['name']) ?>" 
                                data-short="<?= htmlspecialchars($club['short_name']) ?>" 
                                data-category="<?= htmlspecialchars($club['category_name']) ?>" 
                                data-email="<?= htmlspecialchars($club['admin_email'] ?? '') ?>" 
                                data-status="<?= htmlspecialchars($club['status']) ?>">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= htmlspecialchars($club['logo'] ?: '../../assets/United Logo.webp') ?>" class="rounded-3 border shadow-sm" style="width:40px;height:40px;object-fit:cover;" alt="">
                                        <div>
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($club['name']) ?></div>
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size:0.7rem;"><?= htmlspecialchars($club['short_name']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small"><?= htmlspecialchars($club['category_name']) ?></span>
                                </td>
                                <td>
                                    <?php if ($club['admin_email']): ?>
                                        <div class="fw-semibold text-dark small mb-0"><?= htmlspecialchars($club['admin_name'] ?: 'Club Lead') ?></div>
                                        <div class="small font-monospace text-secondary"><?= htmlspecialchars($club['admin_email']) ?></div>
                                    <?php else: ?>
                                        <span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i> Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($club['status'] === 'active'): ?>
                                        <span class="badge bg-success-subtle text-success border rounded-pill px-2.5 py-1 small">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border rounded-pill px-2.5 py-1 small">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <!-- View Public Detail Page -->
                                        <a href="../../club-detail.html?id=<?= $club['id'] ?>" target="_blank" class="btn btn-sm btn-light rounded-circle me-1" title="View Public Page">
                                            <i class="bi bi-eye text-primary"></i>
                                        </a>

                                        <!-- Edit Club Modal Trigger -->
                                        <button type="button" class="btn btn-sm btn-light rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#editClubModal<?= $club['id'] ?>" title="Edit Club Details">
                                            <i class="bi bi-pencil-fill text-dark"></i>
                                        </button>

                                        <!-- Reset Password Modal Trigger -->
                                        <?php if ($club['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-light rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#resetPassModal<?= $club['user_id'] ?>" title="Reset Leader Password">
                                                <i class="bi bi-key-fill text-warning"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Toggle Active / Inactive Status -->
                                        <a href="clubs.php?toggle_status=<?= $club['status'] ?>&club_id=<?= $club['id'] ?>" class="btn btn-sm btn-light rounded-circle me-1" title="Toggle Active / Inactive Status">
                                            <i class="bi bi-power <?= $club['status'] === 'active' ? 'text-success' : 'text-secondary' ?>"></i>
                                        </a>

                                        <!-- Delete Club -->
                                        <a href="clubs.php?delete_club=<?= $club['id'] ?>" onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($club['name']) ?> permanently?');" class="btn btn-sm btn-light text-danger rounded-circle" title="Delete Club">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>

                                    <!-- Edit Club Modal -->
                                    <div class="modal fade text-start" id="editClubModal<?= $club['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Club: <?= htmlspecialchars($club['name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="clubs.php" method="POST">
                                                    <input type="hidden" name="action" value="edit_club">
                                                    <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Club Name *</label>
                                                            <input type="text" name="name" class="form-control rounded-3" value="<?= htmlspecialchars($club['name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Short Code *</label>
                                                            <input type="text" name="short_name" class="form-control rounded-3" value="<?= htmlspecialchars($club['short_name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Category *</label>
                                                            <select name="category_id" class="form-select rounded-3">
                                                                <?php foreach ($categories as $cat): ?>
                                                                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $club['category_id'] ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($cat['name']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Tagline</label>
                                                            <input type="text" name="tagline" class="form-control rounded-3" value="<?= htmlspecialchars($club['tagline']) ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Description</label>
                                                            <textarea name="description" class="form-control rounded-3" rows="3"><?= htmlspecialchars($club['description']) ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reset Password Modal -->
                                    <?php if ($club['user_id']): ?>
                                        <div class="modal fade text-start" id="resetPassModal<?= $club['user_id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-key-fill text-warning me-2"></i> Reset Password: <?= htmlspecialchars($club['admin_email']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="clubs.php" method="POST">
                                                        <input type="hidden" name="action" value="reset_password">
                                                        <input type="hidden" name="user_id" value="<?= $club['user_id'] ?>">
                                                        <div class="modal-body">
                                                            <p class="small text-secondary">Set a new password for <strong><?= htmlspecialchars($club['name']) ?></strong> leadership account.</p>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">New Password *</label>
                                                                <input type="password" name="new_password" class="form-control rounded-3" placeholder="Enter new password" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">Update Password</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add New Club & Issue Credentials Modal -->
<div class="modal fade" id="createClubModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-trophy-fill text-primary me-2"></i> Add New Campus Club & Issue Credentials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="clubs.php" method="POST">
                <input type="hidden" name="action" value="create_club">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Club Full Name *</label>
                            <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. CodeCrafters Developer Club" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Short Code / Acronym *</label>
                            <input type="text" name="short_name" class="form-control rounded-3" placeholder="e.g. CCDC" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category *</label>
                            <select name="category_id" class="form-select rounded-3" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tagline</label>
                            <input type="text" name="tagline" class="form-control rounded-3" placeholder="e.g. Building Next-Gen Web Solutions">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" class="form-control rounded-3" rows="2" placeholder="Brief club mandate..."></textarea>
                        </div>
                        <div class="col-12"><hr class="my-2 text-secondary opacity-25"></div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Club President / Lead Name</label>
                            <input type="text" name="admin_name" class="form-control rounded-3" placeholder="e.g. Rahul Sharma">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Club Admin Email *</label>
                            <input type="email" name="admin_email" class="form-control rounded-3" placeholder="e.g. codecrafters@uit.edu" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Initial Password *</label>
                            <input type="password" name="admin_password" class="form-control rounded-3" placeholder="Set initial password for team" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Create Club & Issue Credentials</button>
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
    const tableBody = document.querySelector('#clubsTable tbody');
    const countBadge = document.getElementById('clubCountBadge');
    
    if (!tableBody) return;
    const rows = Array.from(tableBody.querySelectorAll('tr[data-name]'));

    function filterClubs() {
        const query = (searchInput.value || '').toLowerCase().trim();
        const selectedStatus = statusFilter.value;

        let visibleCount = 0;
        rows.forEach(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const shortName = (row.dataset.short || '').toLowerCase();
            const category = (row.dataset.category || '').toLowerCase();
            const email = (row.dataset.email || '').toLowerCase();
            const status = (row.dataset.status || '').toLowerCase();

            const matchesQuery = !query || name.includes(query) || shortName.includes(query) || category.includes(query) || email.includes(query);
            const matchesStatus = (selectedStatus === 'all') || (status === selectedStatus);

            if (matchesQuery && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (countBadge) countBadge.textContent = visibleCount;
    }

    searchInput?.addEventListener('input', filterClubs);
    statusFilter?.addEventListener('change', filterClubs);
});
</script>
</body>
</html>
