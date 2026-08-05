<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Auth Check for Super Admin & College Authorities (Dean Sir)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['super_admin', 'dean', 'college_authority'])) {
    header('Location: ../dean-login.php');
    exit;
}

$db = Database::getConnection();
$message = '';
$error = '';

// ── 1. Add New Category ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_category') {
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-collection-fill');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = 'Category name is required.';
    } else {
        try {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $stmt = $db->prepare("INSERT INTO categories (name, slug, icon, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $icon, $description]);

            // Audit log
            $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CATEGORY_CREATED', "Created new category '$name' ($slug)"]);

            $message = "Category '$name' created successfully!";
        } catch (Exception $e) {
            $error = 'Error creating category: ' . $e->getMessage();
        }
    }
}

// ── 2. Edit Existing Category ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_category') {
    $catId = intval($_POST['category_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $icon = trim($_POST['icon'] ?? 'bi-collection-fill');
    $description = trim($_POST['description'] ?? '');

    if ($catId > 0 && !empty($name)) {
        try {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $stmt = $db->prepare("UPDATE categories SET name = ?, slug = ?, icon = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $slug, $icon, $description, $catId]);

            // Audit log
            $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CATEGORY_EDITED', "Updated category ID $catId to '$name'"]);

            $message = "Category '$name' updated successfully!";
        } catch (Exception $e) {
            $error = 'Error updating category: ' . $e->getMessage();
        }
    }
}

// ── 3. Delete Category ──────────────────────────────────────────────
if (isset($_GET['delete_cat'])) {
    $catId = intval($_GET['delete_cat']);
    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$catId]);

        // Audit log
        $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CATEGORY_DELETED', "Deleted category ID $catId"]);

        header('Location: categories.php?msg=Category+deleted');
        exit;
    } catch (Exception $e) {
        $error = 'Error deleting category: ' . $e->getMessage();
    }
}

// Fetch all categories with club count
$categories = $db->query("
    SELECT cat.*, COUNT(c.id) as club_count 
    FROM categories cat 
    LEFT JOIN clubs c ON c.category_id = cat.id 
    GROUP BY cat.id 
    ORDER BY cat.name ASC
")->fetchAll();

$totalClubsAssigned = array_sum(array_column($categories, 'club_count'));
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management | Dean Portal | ClubHub UIT</title>
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
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">CAMPUS CLASSIFICATIONS</span>
                <h2 class="fw-bold mb-1 text-dark">Club Categories Governance</h2>
                <p class="text-secondary small mb-0">Define, edit, and categorize institutional student chapters across technical and cultural domains.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 py-2-5 fw-bold shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Category
            </button>
        </div>

        <!-- Alert Feedback -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> Category action completed successfully!<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- KPI Cards Grid -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stat-card p-3 p-md-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">TOTAL CATEGORIES</span>
                            <h3 class="fw-bold text-dark mb-0"><?= count($categories) ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-primary-subtle text-primary fs-3"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card p-3 p-md-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">ASSIGNED CHAPTERS</span>
                            <h3 class="fw-bold text-success mb-0"><?= $totalClubsAssigned ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-success-subtle text-success fs-3"><i class="bi bi-trophy-fill"></i></div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card stat-card p-3 p-md-4 shadow-sm">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">MOST POPULAR DOMAIN</span>
                            <h3 class="fw-bold text-info mb-0"><?= e($categories[0]['name'] ?? 'Technical') ?></h3>
                        </div>
                        <div class="rounded-3 p-3 bg-info-subtle text-info fs-3"><i class="bi bi-star-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Bar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                <input type="text" id="catSearchInput" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Instant search categories by name or slug...">
            </div>
        </div>

        <!-- Categories Table Card -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="categoriesTable">
                    <thead class="table-light">
                        <tr class="small text-secondary">
                            <th>ICON & CATEGORY NAME</th>
                            <th>SLUG</th>
                            <th>DESCRIPTION</th>
                            <th>CLUBS COUNT</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-3 p-2 bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width:38px;height:38px;font-size:1.2rem;">
                                            <i class="bi <?= htmlspecialchars($cat['icon'] ?: 'bi-collection-fill') ?>"></i>
                                        </div>
                                        <span class="fw-bold text-dark"><?= htmlspecialchars($cat['name']) ?></span>
                                    </div>
                                </td>
                                <td><span class="badge bg-secondary-subtle text-secondary font-monospace"><?= htmlspecialchars($cat['slug']) ?></span></td>
                                <td><span class="small text-secondary"><?= htmlspecialchars($cat['description'] ?: 'No description set') ?></span></td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1 font-monospace fw-bold">
                                        <?= $cat['club_count'] ?> Clubs
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <!-- Edit Category Trigger -->
                                        <button type="button" class="btn btn-sm btn-light rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#editCategoryModal<?= $cat['id'] ?>" title="Edit Category">
                                            <i class="bi bi-pencil-fill text-dark"></i>
                                        </button>

                                        <!-- Delete Category -->
                                        <a href="categories.php?delete_cat=<?= $cat['id'] ?>" onclick="return confirm('Delete category <?= htmlspecialchars($cat['name']) ?>?');" class="btn btn-sm btn-light text-danger rounded-circle" title="Delete Category">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>

                                    <!-- Edit Category Modal -->
                                    <div class="modal fade text-start" id="editCategoryModal<?= $cat['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Category: <?= htmlspecialchars($cat['name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="categories.php" method="POST">
                                                    <input type="hidden" name="action" value="edit_category">
                                                    <input type="hidden" name="category_id" value="<?= $cat['id'] ?>">
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Category Name *</label>
                                                            <input type="text" name="name" class="form-control rounded-3" value="<?= htmlspecialchars($cat['name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Bootstrap Icon Class *</label>
                                                            <input type="text" name="icon" class="form-control rounded-3 font-monospace" value="<?= htmlspecialchars($cat['icon']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Description</label>
                                                            <textarea name="description" class="form-control rounded-3" rows="3"><?= htmlspecialchars($cat['description']) ?></textarea>
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
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-grid-3x3-gap-fill text-primary me-2"></i> Add New Club Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="categories.php" method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category Name *</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Technical & Innovation" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Bootstrap Icon Class</label>
                        <input type="text" name="icon" class="form-control rounded-3 font-monospace" value="bi-code-slash" placeholder="e.g. bi-code-slash">
                        <span class="text-secondary small d-block mt-1">Available icons: <code>bi-code-slash</code>, <code>bi-palette-fill</code>, <code>bi-trophy-fill</code>, <code>bi-people-fill</code>, <code>bi-lightbulb-fill</code></span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Brief category description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Live Search Filter for Categories Table
    const searchInput = document.getElementById('catSearchInput');
    const tableRows = document.querySelectorAll('#categoriesTable tbody tr');

    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase().trim();
            tableRows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }
</script>
</body>
</html>
