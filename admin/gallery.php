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

$success = '';
$error = '';

// Handle Add Gallery Photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_photo'])) {
    $mediaUrl = trim($_POST['media_url'] ?? '');
    $caption  = trim($_POST['caption'] ?? '');

    // Process file upload if provided
    $uploadedPhoto = upload_image_file($_FILES['photo_file'] ?? null, 'gallery', $mediaUrl);
    $finalUrl = $uploadedPhoto ?: $mediaUrl;

    if (empty($finalUrl)) {
        $error = "Please upload an image file or provide an image URL.";
    } else {
        try {
            $galId = 'gal_' . bin2hex(random_bytes(4));
            $gStmt = $db->prepare("INSERT INTO gallery_items (id, club_id, media_url, caption) VALUES (?, ?, ?, ?)");
            $gStmt->execute([$galId, $club['id'], $finalUrl, $caption]);
            $success = "Photo added to club gallery successfully!";
        } catch (Exception $e) {
            $error = "Failed to add photo: " . $e->getMessage();
        }
    }
}

// Handle Delete Photo
if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    $dStmt = $db->prepare("DELETE FROM gallery_items WHERE id = ? AND club_id = ?");
    $dStmt->execute([$delId, $club['id']]);
    header('Location: gallery.php?msg=Photo+deleted');
    exit;
}

// Fetch Gallery Items
$galStmt = $db->prepare("SELECT * FROM gallery_items WHERE club_id = ? ORDER BY created_at DESC");
$galStmt->execute([$club['id']]);
$galleryItems = $galStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Photo Gallery | ClubHub UIT</title>
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
    <?php require_once __DIR__ . '/../includes/club_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small"><?= htmlspecialchars($club['name']) ?></span>
                <h2 class="fw-bold mb-1">Club Photo Gallery</h2>
                <p class="text-secondary small mb-0">Upload event photos, workshop moments, and achievements.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#addPhotoModal">
                <i class="bi bi-plus-lg me-1"></i> Upload Photo
            </button>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Gallery Photos Grid -->
        <div class="row g-4">
            <?php if (empty($galleryItems)): ?>
                <div class="col-12 text-center py-5 text-muted bg-white rounded-4 shadow-sm border p-5">
                    <i class="bi bi-images fs-1 text-primary d-block mb-3"></i>
                    <h5 class="fw-bold mb-2">No Gallery Photos Uploaded</h5>
                    <p class="small text-secondary mb-4">Upload photos from your recent club hackathons, sessions, or celebrations.</p>
                    <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold text-white" data-bs-toggle="modal" data-bs-target="#addPhotoModal">
                        <i class="bi bi-plus-lg me-1"></i> Upload First Photo
                    </button>
                </div>
            <?php else: ?>
                <?php foreach ($galleryItems as $item): ?>
                    <div class="col-sm-6 col-md-4 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 position-relative ccms-card">
                            <img src="<?= htmlspecialchars($item['media_url']) ?>" class="img-fluid" style="height: 180px; width: 100%; object-fit: cover;">
                            <div class="p-3 bg-white d-flex justify-content-between align-items-center">
                                <span class="small text-dark fw-medium text-truncate"><?= htmlspecialchars($item['caption'] ?: 'Event Photo') ?></span>
                                <a href="/admin/gallery.php?delete=<?= $item['id'] ?>" onclick="return confirm('Remove photo from gallery?');" class="btn btn-sm btn-outline-danger rounded-circle p-1" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal: Add Gallery Photo -->
<div class="modal fade" id="addPhotoModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold modal-title"><i class="bi bi-image text-primary me-2"></i> Add Gallery Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/gallery.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action_add_photo" value="1">
                <div class="modal-body space-y-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><i class="bi bi-upload text-primary me-1"></i> Upload Image File (From PC) *</label>
                        <input type="file" name="photo_file" class="form-control rounded-3" accept="image/*" required>
                        <span class="form-text text-muted small">Select a PNG, JPG, or WEBP photo from your computer.</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Photo Caption / Event Name</label>
                        <input type="text" name="caption" class="form-control rounded-3" placeholder="e.g. Google Cloud Study Jam Winners">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Add Photo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
