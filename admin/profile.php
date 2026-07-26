<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Auth Check for Club Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'club_admin') {
    header('Location: /admin/login.php');
    exit;
}

$db = Database::getConnection();
$message = '';
$error = '';

// Fetch assigned club for this user
$stmt = $db->prepare("
    SELECT c.* 
    FROM clubs c
    JOIN club_admins ca ON ca.club_id = c.id
    WHERE ca.user_id = ?
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$club = $stmt->fetch();

if (!$club) {
    echo "No club assigned to your account. Please contact Dean Sir (admin@uit.edu).";
    exit;
}

// 1. Handle Club General Details Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_club') {
    $tagline = trim($_POST['tagline'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $mission = trim($_POST['mission'] ?? '');
    $vision = trim($_POST['vision'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $officeLocation = trim($_POST['office_location'] ?? '');
    $meetingTime = trim($_POST['meeting_time'] ?? '');
    $meetingLocation = trim($_POST['meeting_location'] ?? '');
    $instagram = trim($_POST['instagram'] ?? '');
    $linkedin = trim($_POST['linkedin'] ?? '');
    $github = trim($_POST['github'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $recruitmentOpen = isset($_POST['recruitment_open']) ? 1 : 0;

    // Logo & Cover Uploads
    $logoUrl = trim($_POST['logo'] ?? '');
    $uploadedLogo = upload_image_file($_FILES['logo_file'] ?? null, 'clubs', $club['logo'] ?? $logoUrl);
    $logo = $uploadedLogo ?: (!empty($logoUrl) ? $logoUrl : $club['logo']);

    $coverUrl = trim($_POST['cover_image'] ?? '');
    $uploadedCover = upload_image_file($_FILES['cover_file'] ?? null, 'clubs', $club['cover_image'] ?? $coverUrl);
    $coverImage = $uploadedCover ?: (!empty($coverUrl) ? $coverUrl : $club['cover_image']);

    try {
        $uStmt = $db->prepare("
            UPDATE clubs SET 
                tagline = ?, description = ?, mission = ?, vision = ?,
                email = ?, phone = ?, office_location = ?, meeting_time = ?, meeting_location = ?,
                instagram = ?, linkedin = ?, github = ?, website = ?, recruitment_open = ?,
                logo = ?, cover_image = ?
            WHERE id = ?
        ");
        $uStmt->execute([
            $tagline, $description, $mission, $vision,
            $email, $phone, $officeLocation, $meetingTime, $meetingLocation,
            $instagram, $linkedin, $github, $website, $recruitmentOpen,
            $logo, $coverImage,
            $club['id']
        ]);

        $message = 'Club profile, logo, and cover details updated successfully!';
        // Refresh club data
        $stmt->execute([$_SESSION['user_id']]);
        $club = $stmt->fetch();
    } catch (Exception $e) {
        $error = 'Error updating club: ' . $e->getMessage();
    }
}

// 2. Handle Annual Roster Leadership Member Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_leader') {
    $name = trim($_POST['leader_name'] ?? '');
    $roleTitle = trim($_POST['role_title'] ?? '');
    $categorySelect = $_POST['category'] ?? 'core_member';
    $customCategory = trim($_POST['custom_category'] ?? '');
    
    // If 'other' was selected, use the manual custom category name
    $category = ($categorySelect === 'other' && !empty($customCategory)) ? slugify($customCategory) : $categorySelect;
    
    $termYear = trim($_POST['term_year'] ?? '2025-2026');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $avatarUrl = trim($_POST['avatar'] ?? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=400&auto=format&fit=crop');

    // Process uploaded avatar image file from PC
    $uploadedAvatar = upload_image_file($_FILES['avatar_file'] ?? null, 'roster', $avatarUrl);
    $avatar = !empty($uploadedAvatar) ? $uploadedAvatar : $avatarUrl;

    if (!empty($name) && !empty($roleTitle)) {
        try {
            $leadId = 'ldr_' . bin2hex(random_bytes(4));
            $lStmt = $db->prepare("
                INSERT INTO leadership (id, club_id, name, role_title, category, term_year, email, phone, avatar)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $lStmt->execute([$leadId, $club['id'], $name, $roleTitle, $category, $termYear, $email, $phone, $avatar]);
            $message = "Leadership member '$name' ($roleTitle) added successfully!";
        } catch (Exception $e) {
            $error = 'Error adding leadership member: ' . $e->getMessage();
        }
    }
}

// 3. Handle Annual Roster Leadership Delete
if (isset($_GET['delete_leader'])) {
    $leadId = $_GET['delete_leader'];
    $dStmt = $db->prepare("DELETE FROM leadership WHERE id = ? AND club_id = ?");
    $dStmt->execute([$leadId, $club['id']]);
    header('Location: profile.php?msg=Leader+removed');
    exit;
}

// 4. Handle Add Official Club Gallery Photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_club_photo') {
    $mediaUrl = trim($_POST['media_url'] ?? '');
    $caption  = trim($_POST['caption'] ?? '');

    $uploadedPhoto = upload_image_file($_FILES['photo_file'] ?? null, 'gallery', $mediaUrl);
    $finalUrl = $uploadedPhoto ?: $mediaUrl;

    if (empty($finalUrl)) {
        $error = "Please upload an image file or provide an image URL.";
    } else {
        try {
            $galId = 'gal_' . bin2hex(random_bytes(4));
            $gStmt = $db->prepare("INSERT INTO gallery_items (id, club_id, media_url, caption) VALUES (?, ?, ?, ?)");
            $gStmt->execute([$galId, $club['id'], $finalUrl, $caption]);
            $message = "Photo added to official club gallery successfully!";
        } catch (Exception $e) {
            $error = "Failed to add photo: " . $e->getMessage();
        }
    }
}

// 5. Handle Delete Official Club Gallery Photo
if (isset($_GET['delete_photo'])) {
    $photoId = $_GET['delete_photo'];
    $dpStmt = $db->prepare("DELETE FROM gallery_items WHERE id = ? AND club_id = ? AND event_id IS NULL");
    $dpStmt->execute([$photoId, $club['id']]);
    header('Location: profile.php?msg=Photo+deleted');
    exit;
}

// Fetch current annual leadership roster
$leadStmt = $db->prepare("SELECT * FROM leadership WHERE club_id = ? ORDER BY order_index ASC, id ASC");
$leadStmt->execute([$club['id']]);
$roster = $leadStmt->fetchAll();

// Fetch official club gallery photos
$clubGalStmt = $db->prepare("SELECT * FROM gallery_items WHERE club_id = ? AND event_id IS NULL ORDER BY created_at DESC");
$clubGalStmt->execute([$club['id']]);
$clubPhotos = $clubGalStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Leadership & Setup | ClubHub UIT</title>
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
    <?php require_once __DIR__ . '/../includes/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-grow-1 p-4 p-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">CLUB MANAGEMENT</span>
                <h2 class="fw-bold mb-1"><?= htmlspecialchars($club['name']) ?> Setup</h2>
                <p class="text-secondary small mb-0">Update club details, mission, contact links, and annual leadership team (President & Core Members).</p>
            </div>
            <a href="../club-detail.html?id=<?= $club['id'] ?>" target="_blank" class="btn btn-outline-primary rounded-pill px-4 py-2 fw-semibold">
                <i class="bi bi-eye me-1"></i> Preview Live Club Page
            </a>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Left: Club Details Form -->
            <div class="col-lg-7">
                <div class="card p-4 border-0 shadow-sm rounded-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-info-circle text-primary me-2"></i> General Club Details</h5>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="update_club">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Tagline</label>
                            <input type="text" name="tagline" class="form-control rounded-3" value="<?= htmlspecialchars($club['tagline'] ?? '') ?>" placeholder="e.g. Coding & Tech Innovation Club">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" class="form-control rounded-3" rows="3"><?= htmlspecialchars($club['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Mission Statement</label>
                                <textarea name="mission" class="form-control rounded-3" rows="3"><?= htmlspecialchars($club['mission'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Vision Statement</label>
                                <textarea name="vision" class="form-control rounded-3" rows="3"><?= htmlspecialchars($club['vision'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Contact Email</label>
                                <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($club['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Contact Phone</label>
                                <input type="text" name="phone" class="form-control rounded-3" value="<?= htmlspecialchars($club['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Meeting Time</label>
                                <input type="text" name="meeting_time" class="form-control rounded-3" value="<?= htmlspecialchars($club['meeting_time'] ?? '') ?>" placeholder="e.g. Wednesdays 04:00 PM">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Meeting Location</label>
                                <input type="text" name="meeting_location" class="form-control rounded-3" value="<?= htmlspecialchars($club['meeting_location'] ?? '') ?>" placeholder="e.g. Seminar Hall, UIT">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Instagram URL</label>
                                <input type="url" name="instagram" class="form-control rounded-3" value="<?= htmlspecialchars($club['instagram'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">LinkedIn URL</label>
                                <input type="url" name="linkedin" class="form-control rounded-3" value="<?= htmlspecialchars($club['linkedin'] ?? '') ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">GitHub URL</label>
                                <input type="url" name="github" class="form-control rounded-3" value="<?= htmlspecialchars($club['github'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Club Branding Assets (Logo & Cover Image) -->
                        <div class="p-3.5 bg-light rounded-4 border mb-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-palette text-primary me-2"></i> Club Branding Assets (Logo & Cover Banner)</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Upload Club Logo (PC)</label>
                                    <input type="file" name="logo_file" class="form-control form-control-sm rounded-3" accept="image/*">
                                    <div class="mt-1 small text-muted">Current Logo: <a href="<?= htmlspecialchars($club['logo'] ?? '#') ?>" target="_blank" class="fw-bold text-primary">View Logo</a></div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-dark">Upload Cover Banner (PC)</label>
                                    <input type="file" name="cover_file" class="form-control form-control-sm rounded-3" accept="image/*">
                                    <div class="mt-1 small text-muted">Current Cover: <a href="<?= htmlspecialchars($club['cover_image'] ?? '#') ?>" target="_blank" class="fw-bold text-primary">View Cover</a></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="recruitment_open" id="recruitmentOpen" <?= ($club['recruitment_open']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold text-dark" for="recruitmentOpen">Recruitment Currently Open</label>
                        </div>

                        <button type="submit" class="btn btn-primary rounded-pill px-5 py-2-5 fw-bold shadow-sm">Save Club Details & Branding</button>
                    </form>
                </div>

                <!-- Official Club Photo Gallery Manager Card -->
                <div class="card p-4 border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-images text-primary me-2"></i> Official Club Portfolio Gallery</h5>
                    <p class="text-secondary small mb-3">Upload important campus moments & team photos displayed on your Club Detail Page.</p>

                    <form action="" method="POST" enctype="multipart/form-data" class="mb-4">
                        <input type="hidden" name="action" value="add_club_photo">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Upload Photo File</label>
                                <input type="file" name="photo_file" class="form-control form-control-sm rounded-3" accept="image/*">
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small fw-semibold">Caption / Title</label>
                                <input type="text" name="caption" class="form-control form-control-sm rounded-3" placeholder="e.g. Annual Orientation / Team Photo">
                            </div>
                            <div class="col-md-2 mt-md-4">
                                <button type="submit" class="btn btn-sm btn-primary rounded-pill w-100 py-2 fw-bold text-white shadow-xs">
                                    <i class="bi bi-cloud-arrow-up me-1"></i> Upload
                                </button>
                            </div>
                        </div>
                    </form>

                    <h6 class="fw-bold small text-muted border-bottom pb-2 mb-3">Uploaded Portfolio Photos (<?= count($clubPhotos) ?>)</h6>
                    <?php if (empty($clubPhotos)): ?>
                        <div class="text-center py-3 text-muted small bg-light rounded-3">No official photos uploaded for this club yet.</div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($clubPhotos as $photo): ?>
                                <div class="col-6 col-md-4">
                                    <div class="rounded-3 overflow-hidden border position-relative" style="height: 110px;">
                                        <img src="<?= htmlspecialchars($photo['media_url']) ?>" class="w-100 h-100 object-fit-cover">
                                        <a href="profile.php?delete_photo=<?= urlencode($photo['id']) ?>" onclick="return confirm('Delete this photo from official club gallery?');" class="btn btn-sm btn-danger rounded-circle position-absolute top-0 end-0 m-1.5 p-1 d-flex align-items-center justify-content-center" style="width: 26px; height: 26px; font-size: 0.75rem;" title="Delete Photo">
                                            <i class="bi bi-x-lg"></i>
                                        </a>
                                    </div>
                                    <span class="small text-muted d-block text-truncate mt-1" style="font-size: 0.75rem;"><?= htmlspecialchars($photo['caption'] ?: 'Club Photo') ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Annual Leadership Roster Management -->
            <div class="col-lg-5">
                <div class="card p-4 border-0 shadow-sm rounded-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0"><i class="bi bi-people text-primary me-2"></i> Annual Leadership Roster</h5>
                        <button class="btn btn-sm btn-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addLeaderModal">
                            <i class="bi bi-plus-lg me-1"></i> Add Member
                        </button>
                    </div>
                    <p class="text-secondary small mb-3">Manage annual core team members (President, Vice President, Secretaries). Update every year as leadership changes.</p>

                    <div class="d-flex flex-column gap-3">
                        <?php if (empty($roster)): ?>
                            <div class="text-center py-4 text-muted bg-light rounded-4">
                                <i class="bi bi-person-x fs-2 d-block mb-1"></i>
                                No leaders added yet for this academic term. Click "Add Member" to set up your team.
                            </div>
                        <?php else: ?>
                            <?php foreach ($roster as $r): ?>
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 border">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= htmlspecialchars($r['avatar']) ?>" class="rounded-circle border" style="width: 44px; height: 44px; object-fit: cover;">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($r['name']) ?></h6>
                                            <span class="badge bg-primary-subtle text-primary border rounded-pill px-2 py-0-5 small"><?= htmlspecialchars($r['role_title']) ?></span>
                                            <span class="small text-muted d-block" style="font-size: 0.72rem;"><?= htmlspecialchars($r['term_year']) ?></span>
                                        </div>
                                    </div>
                                    <a href="profile.php?delete_leader=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Remove this leader from roster?');">
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
</div>

<!-- Modal: Add Annual Leadership Roster Member -->
<div class="modal fade" id="addLeaderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold modal-title"><i class="bi bi-person-plus text-primary me-2"></i> Add Core Team Leader</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_leader">
                <div class="modal-body space-y-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Member Name *</label>
                        <input type="text" name="leader_name" class="form-control rounded-3" placeholder="e.g. Riya Sharma" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Role Title *</label>
                            <input type="text" name="role_title" class="form-control rounded-3" placeholder="e.g. President" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Academic Term Year</label>
                            <input type="text" name="term_year" class="form-control rounded-3" value="2025-2026">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role Category</label>
                        <select name="category" id="roleCategorySelect" class="form-select rounded-3">
                            <option value="president">President / Lead</option>
                            <option value="vice_president">Vice President</option>
                            <option value="secretary">Secretary</option>
                            <option value="treasurer">Treasurer</option>
                            <option value="faculty_coordinator">Faculty Coordinator</option>
                            <option value="core_member">Core Team Member</option>
                            <option value="other">Other (Specify Custom Role Category)</option>
                        </select>
                        <input type="text" name="custom_category" id="customCategoryInput" class="form-control rounded-3 mt-2 d-none" placeholder="Enter custom role category (e.g. PR & Media Lead)...">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control rounded-3" placeholder="email@uit.edu">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control rounded-3" placeholder="+91 98765...">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><i class="bi bi-upload text-primary me-1"></i> Upload Leader Photo (From PC)</label>
                        <input type="file" name="avatar_file" class="form-control rounded-3" accept="image/*">
                        <span class="form-text text-muted small">Select member profile photo from your computer.</span>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Add to Roster</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const roleSelect = document.getElementById('roleCategorySelect');
    const customInput = document.getElementById('customCategoryInput');
    
    if (roleSelect && customInput) {
        roleSelect.addEventListener('change', () => {
            if (roleSelect.value === 'other') {
                customInput.classList.remove('d-none');
                customInput.focus();
            } else {
                customInput.classList.add('d-none');
            }
        });
    }
});
</script>
</body>
</html>
