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

if (isset($_GET['msg'])) {
    $success = htmlspecialchars($_GET['msg']);
}

// ----------------------------------------------------
// 1. Handle Single Add Photo
// ----------------------------------------------------
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

// ----------------------------------------------------
// 2. Handle Single Edit Photo
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_edit_photo'])) {
    $photoId  = trim($_POST['photo_id'] ?? '');
    $caption  = trim($_POST['caption'] ?? '');
    $mediaUrl = trim($_POST['media_url'] ?? '');

    // Check if user uploaded a replacement file
    if (!empty($_FILES['photo_file']['name'])) {
        $uploadedPhoto = upload_image_file($_FILES['photo_file'], 'gallery', $mediaUrl);
        if (!empty($uploadedPhoto)) {
            $mediaUrl = $uploadedPhoto;
        }
    }

    if (empty($photoId)) {
        $error = "Invalid photo ID specified.";
    } else {
        try {
            if (!empty($mediaUrl)) {
                $eStmt = $db->prepare("UPDATE gallery_items SET caption = ?, media_url = ? WHERE id = ? AND club_id = ?");
                $eStmt->execute([$caption, $mediaUrl, $photoId, $club['id']]);
            } else {
                $eStmt = $db->prepare("UPDATE gallery_items SET caption = ? WHERE id = ? AND club_id = ?");
                $eStmt->execute([$caption, $photoId, $club['id']]);
            }
            $success = "Gallery photo updated successfully!";
        } catch (Exception $e) {
            $error = "Failed to update photo: " . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// 3. Handle Single Delete Photo
// ----------------------------------------------------
if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    $dStmt = $db->prepare("DELETE FROM gallery_items WHERE id = ? AND club_id = ?");
    $dStmt->execute([$delId, $club['id']]);
    header('Location: gallery.php?msg=Photo+deleted+successfully');
    exit;
}

// ----------------------------------------------------
// 4. Handle Bulk Actions (Bulk Delete / Bulk Update Caption)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_bulk'])) {
    $bulkAction = $_POST['bulk_action'] ?? '';
    $selectedIds = $_POST['selected_photos'] ?? [];

    if (empty($selectedIds) || !is_array($selectedIds)) {
        $error = "Please select at least one photo for bulk operation.";
    } else {
        if ($bulkAction === 'delete') {
            try {
                $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                $params = array_merge($selectedIds, [$club['id']]);
                $bStmt = $db->prepare("DELETE FROM gallery_items WHERE id IN ($placeholders) AND club_id = ?");
                $bStmt->execute($params);
                $success = count($selectedIds) . " photos deleted successfully!";
            } catch (Exception $e) {
                $error = "Bulk delete failed: " . $e->getMessage();
            }
        } elseif ($bulkAction === 'update_caption') {
            $bulkCaption = trim($_POST['bulk_caption'] ?? '');
            if (empty($bulkCaption)) {
                $error = "Please provide a caption text for bulk update.";
            } else {
                try {
                    $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
                    $params = array_merge([$bulkCaption], $selectedIds, [$club['id']]);
                    $bStmt = $db->prepare("UPDATE gallery_items SET caption = ? WHERE id IN ($placeholders) AND club_id = ?");
                    $bStmt->execute($params);
                    $success = count($selectedIds) . " photos updated with new caption!";
                } catch (Exception $e) {
                    $error = "Bulk update failed: " . $e->getMessage();
                }
            }
        }
    }
}

// Fetch Gallery Items for this club
$galStmt = $db->prepare("SELECT * FROM gallery_items WHERE club_id = ? ORDER BY created_at DESC");
$galStmt->execute([$club['id']]);
$galleryItems = $galStmt->fetchAll();
$totalCount = count($galleryItems);
$latestDate = !empty($galleryItems) ? date('d M Y', strtotime($galleryItems[0]['created_at'])) : 'N/A';
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
        .gallery-card {
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            border: 1px solid rgba(0,0,0,0.06);
        }
        .gallery-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.12) !important;
        }
        .gallery-checkbox-overlay {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 10;
        }
        .gallery-checkbox-overlay .form-check-input {
            width: 20px;
            height: 20px;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        }
        .gallery-action-overlay {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
            display: flex;
            gap: 6px;
            opacity: 0.85;
            transition: opacity 0.2s ease;
        }
        .gallery-card:hover .gallery-action-overlay {
            opacity: 1;
        }
        .sticky-bulk-bar {
            position: sticky;
            top: 70px;
            z-index: 1020;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            display: none;
        }
        .sticky-bulk-bar.active {
            display: flex;
        }
        .info-callout-banner {
            background: linear-gradient(135deg, #eff6ff 0%, #e0e7ff 100%);
            border-left: 5px solid #6366f1;
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Master Sidebar Include -->
    <?php require_once __DIR__ . '/../includes/club_sidebar.php'; ?>

    <!-- Main Content Body -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5">

        <!-- Top Header & Actions -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">
                    <i class="bi bi-patch-check-fill me-1"></i><?= htmlspecialchars($club['name']) ?>
                </span>
                <h2 class="fw-bold mb-1 mt-2">Official Club Photo Gallery</h2>
                <p class="text-secondary small mb-0">Manage chapter branding photos, orientation highlights, awards, and group moments.</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary rounded-pill px-4 py-2-5 fw-bold shadow-sm text-white d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#addPhotoModal">
                    <i class="bi bi-cloud-arrow-up-fill fs-5"></i>
                    <span>Upload New Photo</span>
                </button>
            </div>
        </div>

        <!-- Info Callout Banner (Guidance for Event-Specific Photos vs General Club Gallery) -->
        <div class="card border-0 rounded-4 p-4 mb-4 info-callout-banner shadow-sm">
            <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center justify-content-between gap-3">
                <div class="d-flex gap-3 align-items-start">
                    <div class="p-3 bg-primary text-white rounded-3 flex-shrink-0 d-none d-sm-block">
                        <i class="bi bi-info-circle-fill fs-4"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold text-dark mb-1">📸 Official Chapter Gallery vs. Event Recap Photos</h6>
                        <p class="text-secondary small mb-0" style="line-height: 1.5;">
                            Ye section aapke <strong><?= htmlspecialchars($club['name']) ?></strong> chapter ki overall general photos, awards, leadership moments aur Orientation highlights ke liye hai.
                            <br class="d-none d-md-block">
                            Agar aap kisi <strong>Specific Event</strong> (jaise <em>Hackathon 2026</em> ya <em>Workshop</em>) ki photos submit karna chahte hain, to 
                            <a href="events.php" class="fw-bold text-primary text-decoration-underline">Manage Events Page</a> par jayein aur respective event ka <strong>"Edit Event & Gallery"</strong> open karein.
                        </p>
                    </div>
                </div>
                <a href="events.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-2 fw-semibold text-nowrap flex-shrink-0">
                    <i class="bi bi-calendar-event me-1"></i> Go to Manage Events
                </a>
            </div>
        </div>

        <!-- KPI Metrics Bar -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-primary-subtle text-primary rounded-3">
                            <i class="bi bi-images fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Total Gallery Photos</span>
                            <h4 class="fw-bold mb-0 text-dark"><?= $totalCount ?></h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="p-3 bg-success-subtle text-success rounded-3">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                        <div>
                            <span class="text-muted small d-block">Last Uploaded</span>
                            <h5 class="fw-bold mb-0 text-dark"><?= $latestDate ?></h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white d-flex flex-row align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small d-block">Select Options</span>
                        <span class="fw-semibold text-dark small">Batch operations enabled</span>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="selectAllBtn" onclick="toggleSelectAll()">
                        <i class="bi bi-check2-all me-1"></i> Select All
                    </button>
                </div>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Sticky Bulk Operations Floating Bar -->
        <form action="gallery.php" method="POST" id="bulkForm">
            <input type="hidden" name="action_bulk" value="1">
            <input type="hidden" name="bulk_action" id="bulkActionInput" value="">
            <input type="hidden" name="bulk_caption" id="bulkCaptionInput" value="">

            <div class="card rounded-4 p-3 mb-4 sticky-bulk-bar align-items-center justify-content-between" id="bulkActionBar">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-primary text-white rounded-pill px-3 py-2 fs-6" id="selectedCountBadge">0 Selected</span>
                    <span class="small text-white-50 d-none d-md-inline">Select photos to apply batch updates or delete.</span>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light rounded-pill px-3 fw-bold" onclick="promptBulkCaption()">
                        <i class="bi bi-pencil-square me-1 text-primary"></i> Bulk Edit Caption
                    </button>
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold" onclick="confirmBulkDelete()">
                        <i class="bi bi-trash-fill me-1"></i> Bulk Delete Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light rounded-circle" onclick="clearBulkSelection()" title="Clear Selection">
                        ✕
                    </button>
                </div>
            </div>

            <!-- Gallery Photos Grid -->
            <div class="row g-4">
                <?php if (empty($galleryItems)): ?>
                    <div class="col-12 text-center py-5 text-muted bg-white rounded-4 shadow-sm border p-5">
                        <div class="mb-3">
                            <span class="p-4 bg-primary-subtle text-primary rounded-circle d-inline-block">
                                <i class="bi bi-images fs-1"></i>
                            </span>
                        </div>
                        <h4 class="fw-bold mb-2 text-dark">No Official Gallery Photos Uploaded Yet</h4>
                        <p class="small text-secondary mb-4" style="max-width: 500px; margin: 0 auto;">
                            Upload high-resolution photos of your club executive team, orientation sessions, hackathons, and celebrations to showcase on your public chapter portal.
                        </p>
                        <button type="button" class="btn btn-primary rounded-pill px-4 py-2-5 fw-bold text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#addPhotoModal">
                            <i class="bi bi-plus-lg me-1"></i> Upload First Chapter Photo
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($galleryItems as $item): ?>
                        <div class="col-sm-6 col-md-4 col-lg-3">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 position-relative gallery-card bg-white">
                                <!-- Checkbox Selection -->
                                <div class="gallery-checkbox-overlay">
                                    <input type="checkbox" name="selected_photos[]" value="<?= e($item['id']) ?>" class="form-check-input photo-select-chk" onchange="updateBulkBarState()">
                                </div>

                                <!-- Action Buttons Overlay -->
                                <div class="gallery-action-overlay">
                                    <button type="button" class="btn btn-sm btn-light rounded-circle shadow-sm" 
                                            onclick="openEditModal('<?= e($item['id']) ?>', '<?= e($item['caption']) ?>', '<?= e($item['media_url']) ?>')" title="Edit Caption / Photo">
                                        <i class="bi bi-pencil-fill text-primary"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light rounded-circle shadow-sm" 
                                            onclick="openLightbox('<?= e($item['media_url']) ?>', '<?= e($item['caption']) ?>')" title="Preview Photo">
                                        <i class="bi bi-arrows-angle-expand text-dark"></i>
                                    </button>
                                    <a href="gallery.php?delete=<?= e($item['id']) ?>" 
                                       onclick="return confirm('Are you sure you want to remove this photo from the official gallery?');" 
                                       class="btn btn-sm btn-light rounded-circle shadow-sm text-danger" title="Delete Photo">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </div>

                                <!-- Image View -->
                                <div style="height: 190px; overflow: hidden; background: #0f172a;">
                                    <img src="<?= e($item['media_url']) ?>" 
                                         class="w-100 h-100 object-fit-cover" 
                                         alt="<?= e($item['caption'] ?: 'Club Photo') ?>"
                                         loading="lazy"
                                         onerror="this.src='https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=600&auto=format&fit=crop'">
                                </div>

                                <!-- Caption Footer -->
                                <div class="p-3 bg-white d-flex justify-content-between align-items-center border-top">
                                    <div class="overflow-hidden me-2">
                                        <span class="d-block small text-dark fw-semibold text-truncate" title="<?= e($item['caption'] ?: 'Chapter Photo') ?>">
                                            <?= e($item['caption'] ?: 'Chapter Photo') ?>
                                        </span>
                                        <span class="small text-muted d-block" style="font-size: 0.7rem;">
                                            <?= date('M j, Y', strtotime($item['created_at'])) ?>
                                        </span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1 text-nowrap small" 
                                            onclick="openEditModal('<?= e($item['id']) ?>', '<?= e($item['caption']) ?>', '<?= e($item['media_url']) ?>')">
                                        <i class="bi bi-pencil me-1"></i> Edit
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </form>

    </div>
</div>

<!-- Modal: Add Gallery Photo -->
<div class="modal fade" id="addPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold modal-title"><i class="bi bi-cloud-arrow-up text-primary me-2"></i> Upload Chapter Gallery Photo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="gallery.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action_add_photo" value="1">
                <div class="modal-body space-y-3 p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><i class="bi bi-image text-primary me-1"></i> Select Image File (From Computer) *</label>
                        <input type="file" name="photo_file" class="form-control rounded-3" accept="image/*" required>
                        <span class="form-text text-muted small">Supports JPG, PNG, WEBP images. Recommended ratio 16:9 or 4:3.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><i class="bi bi-link-45deg me-1"></i> Or Provide Image URL (Fallback)</label>
                        <input type="url" name="media_url" class="form-control rounded-3" placeholder="https://images.unsplash.com/photo-...">
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Photo Caption / Description</label>
                        <input type="text" name="caption" class="form-control rounded-3" placeholder="e.g. Executive Committee Orientation 2026">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 p-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Upload Photo</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Photo Caption / Image -->
<div class="modal fade" id="editPhotoModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold modal-title"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Photo Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="gallery.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action_edit_photo" value="1">
                <input type="hidden" name="photo_id" id="editPhotoId">
                <div class="modal-body space-y-3 p-4">
                    <div class="text-center mb-3">
                        <img id="editPhotoPreview" src="" class="rounded-3 shadow-xs border" style="max-height: 140px; object-fit: cover;">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Photo Caption</label>
                        <input type="text" name="caption" id="editPhotoCaption" class="form-control rounded-3" placeholder="e.g. Annual Hackathon Group Photo">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Replace File (Optional)</label>
                        <input type="file" name="photo_file" class="form-control rounded-3" accept="image/*">
                        <span class="form-text text-muted small">Leave empty if you only want to update the caption.</span>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Image URL</label>
                        <input type="text" name="media_url" id="editPhotoUrl" class="form-control rounded-3">
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 p-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Lightbox Modal -->
<div id="fullLightbox" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.93);z-index:9999;align-items:center;justify-content:center;flex-direction:column;gap:12px;" onclick="closeLightbox()">
    <img id="lightboxImg" src="" style="max-width:90vw;max-height:80vh;border-radius:12px;object-fit:contain;box-shadow:0 20px 60px rgba(0,0,0,0.6);" alt="">
    <div id="lightboxCaption" style="color:#fff;font-size:1rem;font-weight:600;text-align:center;max-width:600px;padding:0 20px;"></div>
    <button onclick="closeLightbox()" style="position:absolute;top:20px;right:24px;background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);color:#fff;border-radius:50%;width:42px;height:42px;font-size:1.2rem;cursor:pointer;display:flex;align-items:center;justify-content:center;">✕</button>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Bulk selection management
function updateBulkBarState() {
    const checked = document.querySelectorAll('.photo-select-chk:checked');
    const bulkBar = document.getElementById('bulkActionBar');
    const badge = document.getElementById('selectedCountBadge');

    if (checked.length > 0) {
        bulkBar.classList.add('active');
        badge.textContent = checked.length + ' Selected';
    } else {
        bulkBar.classList.remove('active');
    }
}

function toggleSelectAll() {
    const chks = document.querySelectorAll('.photo-select-chk');
    const allChecked = Array.from(chks).every(c => c.checked);
    chks.forEach(c => c.checked = !allChecked);
    updateBulkBarState();
    
    const btn = document.getElementById('selectAllBtn');
    btn.innerHTML = !allChecked ? '<i class="bi bi-dash-square me-1"></i> Deselect All' : '<i class="bi bi-check2-all me-1"></i> Select All';
}

function clearBulkSelection() {
    document.querySelectorAll('.photo-select-chk').forEach(c => c.checked = false);
    updateBulkBarState();
}

function confirmBulkDelete() {
    const checked = document.querySelectorAll('.photo-select-chk:checked');
    if (checked.length === 0) return;
    if (confirm(`Are you sure you want to delete ${checked.length} selected photos? This cannot be undone.`)) {
        document.getElementById('bulkActionInput').value = 'delete';
        document.getElementById('bulkForm').submit();
    }
}

function promptBulkCaption() {
    const checked = document.querySelectorAll('.photo-select-chk:checked');
    if (checked.length === 0) return;
    const newCaption = prompt(`Enter new caption to apply to ${checked.length} selected photos:`);
    if (newCaption !== null && newCaption.trim() !== '') {
        document.getElementById('bulkActionInput').value = 'update_caption';
        document.getElementById('bulkCaptionInput').value = newCaption.trim();
        document.getElementById('bulkForm').submit();
    }
}

// Edit Modal Pre-fill
function openEditModal(id, caption, url) {
    document.getElementById('editPhotoId').value = id;
    document.getElementById('editPhotoCaption').value = caption;
    document.getElementById('editPhotoUrl').value = url;
    document.getElementById('editPhotoPreview').src = url;
    
    const editModal = new bootstrap.Modal(document.getElementById('editPhotoModal'));
    editModal.show();
}

// Lightbox
function openLightbox(url, caption) {
    document.getElementById('lightboxImg').src = url;
    document.getElementById('lightboxCaption').textContent = caption || '';
    document.getElementById('fullLightbox').style.display = 'flex';
}

function closeLightbox() {
    document.getElementById('fullLightbox').style.display = 'none';
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeLightbox();
});
</script>
</body>
</html>
