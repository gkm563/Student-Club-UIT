<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: /admin/dean-login.php');
    exit;
}

$db = Database::getConnection();
$message = '';
$error = '';

// Add New Category
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
            $message = "Category '$name' created successfully!";
        } catch (Exception $e) {
            $error = 'Error creating category: ' . $e->getMessage();
        }
    }
}

// Delete Category
if (isset($_GET['delete_cat'])) {
    $catId = intval($_GET['delete_cat']);
    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$catId]);
        header('Location: /admin/super/categories.php?msg=Category+deleted');
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
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management | Dean Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <style>
        body { background: #f8fafc; }
        .admin-sidebar { width: 260px; min-height: 100vh; background: #0b0f19; color: #fff; }
        .admin-nav-link { color: rgba(255,255,255,0.7); padding: 12px 18px; border-radius: 12px; display: flex; align-items: center; gap: 12px; text-decoration: none; font-weight: 500; }
        .admin-nav-link:hover, .admin-nav-link.active { background: #6366f1; color: #fff; }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Sidebar -->
    <div class="admin-sidebar p-3 flex-shrink-0 d-none d-md-block">
        <div class="d-flex align-items-center gap-3 mb-4 p-2">
            <img src="/assets/United Logo.webp" style="height: 38px;">
            <div>
                <span class="fw-bold d-block lh-1">ClubHub</span>
                <span class="small text-white-50" style="font-size: 0.65rem;">DEAN PORTAL</span>
            </div>
        </div>

        <nav class="d-flex flex-column gap-2">
            <a href="/admin/super/index.php" class="admin-nav-link"><i class="bi bi-speedometer2"></i> Overview</a>
            <a href="/admin/super/clubs.php" class="admin-nav-link"><i class="bi bi-trophy"></i> Manage Clubs</a>
            <a href="/admin/super/categories.php" class="admin-nav-link active"><i class="bi bi-tags"></i> Categories</a>
            <a href="/admin/super/logs.php" class="admin-nav-link"><i class="bi bi-journal-text"></i> Audit Logs</a>
            <a href="/admin/super/messages.php" class="admin-nav-link"><i class="bi bi-envelope"></i> Messages</a>
            <a href="/admin/super/users.php" class="admin-nav-link"><i class="bi bi-shield-lock"></i> Dean Profile</a>
            <a href="/admin/logout.php" class="admin-nav-link text-danger mt-4"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">DEAN PORTAL</span>
                <h2 class="fw-bold mb-1">Club Categories Management</h2>
                <p class="text-secondary small mb-0">Define, edit, and organize club classifications across campus.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                <i class="bi bi-plus-lg me-1"></i> Add Category
            </button>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Categories Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Category Name</th>
                            <th>Slug</th>
                            <th>Icon</th>
                            <th>Assigned Clubs</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary-subtle text-primary rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                            <i class="bi <?= htmlspecialchars($cat['icon']) ?>"></i>
                                        </div>
                                        <div>
                                            <strong class="text-dark d-block"><?= htmlspecialchars($cat['name']) ?></strong>
                                            <span class="small text-muted"><?= htmlspecialchars($cat['description'] ?? '') ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><code><?= htmlspecialchars($cat['slug']) ?></code></td>
                                <td><i class="bi <?= htmlspecialchars($cat['icon']) ?> fs-5"></i></td>
                                <td><span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1"><?= $cat['club_count'] ?> Clubs</span></td>
                                <td>
                                    <a href="/admin/super/categories.php?delete_cat=<?= $cat['id'] ?>" onclick="return confirm('Delete this category?');" class="btn btn-sm btn-outline-danger rounded-circle" title="Delete">
                                        <i class="bi bi-trash"></i>
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

<!-- Modal: Add Category -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold modal-title"><i class="bi bi-tag text-primary me-2"></i> Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/super/categories.php" method="POST">
                <input type="hidden" name="action" value="add_category">
                <div class="modal-body space-y-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Category Name *</label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Research & Innovation" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Bootstrap Icon Class</label>
                        <input type="text" name="icon" class="form-control rounded-3" value="bi-collection-fill" placeholder="e.g. bi-lightbulb">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Description</label>
                        <textarea name="description" class="form-control rounded-3" rows="2" placeholder="Brief summary..."></textarea>
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
</body>
</html>
