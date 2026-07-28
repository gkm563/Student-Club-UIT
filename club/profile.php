<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

require_login('../club-login.php');

$userRole = get_current_user_role();
if ($userRole === 'super_admin') {
    header('Location: ../admin/super/index.php');
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
$stmt->execute([get_current_user_id()]);
$club = $stmt->fetch();

if (!$club) {
    echo "No club assigned to your account. Please contact Dean Sir (admin@uit.edu).";
    exit;
}

// 1. Handle Club General Details Update & Toggles
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

    // Section Visibility Toggles
    $showAchievements = isset($_POST['show_achievements']) ? 1 : 0;
    $showLeadership   = isset($_POST['show_leadership']) ? 1 : 0;
    $showRecruitment  = isset($_POST['show_recruitment']) ? 1 : 0;
    $showGallery      = isset($_POST['show_gallery']) ? 1 : 0;
    $achievementsText = trim($_POST['achievements_text'] ?? '');

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
                logo = ?, cover_image = ?,
                show_achievements = ?, show_leadership = ?, show_recruitment = ?, show_gallery = ?, achievements_text = ?
            WHERE id = ?
        ");
        $uStmt->execute([
            $tagline, $description, $mission, $vision,
            $email, $phone, $officeLocation, $meetingTime, $meetingLocation,
            $instagram, $linkedin, $github, $website, $recruitmentOpen,
            $logo, $coverImage,
            $showAchievements, $showLeadership, $showRecruitment, $showGallery, $achievementsText,
            $club['id']
        ]);

        $message = 'Club profile, section visibility toggles, and branding assets updated successfully!';
        // Refresh club data
        $stmt->execute([get_current_user_id()]);
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

// 2b. Handle Annual Roster Leadership Member Edit/Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_leader') {
    $leaderId = trim($_POST['leader_id'] ?? '');
    $name = trim($_POST['leader_name'] ?? '');
    $roleTitle = trim($_POST['role_title'] ?? '');
    $categorySelect = $_POST['category'] ?? 'core_member';
    $customCategory = trim($_POST['custom_category'] ?? '');
    $category = ($categorySelect === 'other' && !empty($customCategory)) ? slugify($customCategory) : $categorySelect;
    
    $termYear = trim($_POST['term_year'] ?? '2025-2026');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $avatarUrl = trim($_POST['avatar'] ?? '');

    $uploadedAvatar = upload_image_file($_FILES['avatar_file'] ?? null, 'roster', $avatarUrl);
    $avatar = !empty($uploadedAvatar) ? $uploadedAvatar : $avatarUrl;

    if (!empty($leaderId) && !empty($name) && !empty($roleTitle)) {
        try {
            $elStmt = $db->prepare("
                UPDATE leadership 
                SET name = ?, role_title = ?, category = ?, term_year = ?, email = ?, phone = ?, avatar = ?
                WHERE id = ? AND club_id = ?
            ");
            $elStmt->execute([$name, $roleTitle, $category, $termYear, $email, $phone, $avatar, $leaderId, $club['id']]);
            $message = "Leadership member '$name' updated successfully!";
        } catch (Exception $e) {
            $error = 'Error updating leadership member: ' . $e->getMessage();
        }
    }
}

// 3. Handle Delete Leadership Member
if (isset($_GET['delete_leader'])) {
    $leadId = $_GET['delete_leader'];
    $dStmt = $db->prepare("DELETE FROM leadership WHERE id = ? AND club_id = ?");
    $dStmt->execute([$leadId, $club['id']]);
    header('Location: profile.php?msg=Leader+removed+successfully');
    exit;
}

// 4. Handle Add Official Club Photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_club_photo') {
    $mediaUrl = trim($_POST['media_url'] ?? '');
    $caption  = trim($_POST['caption'] ?? '');

    $uploadedPhoto = upload_image_file($_FILES['photo_file'] ?? null, 'clubs', $mediaUrl);
    $finalUrl = $uploadedPhoto ?: $mediaUrl;

    if (empty($finalUrl)) {
        $error = "Please select an image file or provide an image URL.";
    } else {
        try {
            $galId = 'gal_' . bin2hex(random_bytes(4));
            $gStmt = $db->prepare("INSERT INTO gallery_items (id, club_id, media_url, caption) VALUES (?, ?, ?, ?)");
            $gStmt->execute([$galId, $club['id'], $finalUrl, $caption]);
            $message = "Official club photo added to gallery successfully!";
        } catch (Exception $e) {
            $error = "Failed to upload photo: " . $e->getMessage();
        }
    }
}

// 5. Handle Delete Official Club Photo
if (isset($_GET['delete_photo'])) {
    $photoId = $_GET['delete_photo'];
    $dpStmt = $db->prepare("DELETE FROM gallery_items WHERE id = ? AND club_id = ?");
    $dpStmt->execute([$photoId, $club['id']]);
    header('Location: profile.php?msg=Gallery+photo+deleted');
    exit;
}

// Fetch Annual Leadership Roster
$rosterStmt = $db->prepare("SELECT * FROM leadership WHERE club_id = ? ORDER BY term_year DESC, created_at ASC");
$rosterStmt->execute([$club['id']]);
$roster = $rosterStmt->fetchAll();

// Fetch official club gallery photos
$clubGalStmt = $db->prepare("SELECT * FROM gallery_items WHERE club_id = ? ORDER BY created_at DESC");
$clubGalStmt->execute([$club['id']]);
$clubPhotos = $clubGalStmt->fetchAll();

// Profile Completion Score
$profileFields = [
    'tagline'         => !empty($club['tagline']),
    'description'     => !empty($club['description']),
    'mission'         => !empty($club['mission']),
    'vision'          => !empty($club['vision']),
    'logo'            => !empty($club['logo']),
    'cover_image'     => !empty($club['cover_image']),
    'email'           => !empty($club['email']),
    'instagram'       => !empty($club['instagram']),
];
$profileScore = round(array_sum($profileFields) / count($profileFields) * 100);
$profileBadge = $profileScore >= 80 ? ['Complete', 'success'] : ($profileScore >= 50 ? ['In Progress', 'warning'] : ['Incomplete', 'danger']);

// Quick Stats counts
try {
    $evtCountStmt = $db->prepare("SELECT COUNT(*) FROM events WHERE club_id = ?");
    $evtCountStmt->execute([$club['id']]);
    $profileEventCount = $evtCountStmt->fetchColumn();
} catch (Exception $e) { $profileEventCount = 0; }
$profileLeaderCount = count($roster ?? []);
$profileGalleryCount = count($clubPhotos ?? []);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($club['name']) ?> | Chapter Setup Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
        .section-toggle-card {
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            transition: all 0.25s ease;
            background: #ffffff;
        }
        .section-toggle-card:hover {
            border-color: #93c5fd;
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.08);
        }
        .form-switch .form-check-input {
            width: 2.8em;
            height: 1.5em;
            cursor: pointer;
        }
        .roster-avatar-wrap {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid #ffffff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Master Sidebar -->
    <?php require_once __DIR__ . '/../includes/club_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5">
        <!-- Top Executive Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">CHAPTER DASHBOARD</span>
                <h2 class="fw-bold mb-1 mt-1"><?= htmlspecialchars($club['name']) ?> Setup</h2>
                <p class="text-secondary small mb-0">Configure your public page, section visibility, branding assets, leadership team, and portfolio gallery.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="../club-detail.html?id=<?= urlencode($club['id']) ?>" target="_blank" class="btn btn-outline-success rounded-pill px-4 py-2.5 fw-bold shadow-xs">
                    <i class="bi bi-box-arrow-up-right me-1.5"></i> Preview Live Club Page
                </a>
                <button type="submit" form="clubProfileForm" class="btn btn-primary rounded-pill px-4 py-2.5 fw-bold text-white shadow-sm">
                    <i class="bi bi-floppy me-1.5"></i> Save Profile & Display Settings
                </button>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_GET['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- 2-Column Master Balanced Layout -->
        <div class="row g-4">
            
            <!-- LEFT COLUMN (7 COLS): Identity, Mission, Secretariat & Section Toggles Form -->
            <div class="col-lg-7">
                <form action="" method="POST" enctype="multipart/form-data" id="clubProfileForm">
                    <input type="hidden" name="action" value="update_club">

                    <!-- 1. General Identity & Tagline Card -->
                    <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4 bg-white">
                        <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-info-circle-fill text-primary me-2"></i> General Chapter Details</h5>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Chapter Tagline / Catchphrase</label>
                            <input type="text" name="tagline" class="form-control rounded-3" value="<?= htmlspecialchars($club['tagline'] ?? '') ?>" placeholder="e.g. Promoting Coding Culture, DSA & Competitive Programming">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Full Chapter Description</label>
                            <textarea name="description" class="form-control rounded-3" rows="4" placeholder="Overview of your student organization, activities, and goals..."><?= htmlspecialchars($club['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Mission Statement</label>
                                <textarea name="mission" class="form-control rounded-3" rows="3" placeholder="Our mission is to empower students..."><?= htmlspecialchars($club['mission'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Vision Statement</label>
                                <textarea name="vision" class="form-control rounded-3" rows="3" placeholder="Our vision is to cultivate top-tier coders..."><?= htmlspecialchars($club['vision'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Branding Asset Uploads -->
                        <div class="p-4 bg-light rounded-4 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-palette-fill text-primary me-2"></i> Club Branding Assets</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Upload Logo File (PC)</label>
                                    <input type="file" name="logo_file" class="form-control form-control-sm rounded-3" accept="image/*">
                                    <?php if (!empty($club['logo'])): ?>
                                        <span class="form-text small text-muted">Current: <a href="<?= htmlspecialchars($club['logo']) ?>" target="_blank" class="fw-bold text-primary">View Logo</a></span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Upload Cover Banner (PC)</label>
                                    <input type="file" name="cover_file" class="form-control form-control-sm rounded-3" accept="image/*">
                                    <?php if (!empty($club['cover_image'])): ?>
                                        <span class="form-text small text-muted">Current: <a href="<?= htmlspecialchars($club['cover_image']) ?>" target="_blank" class="fw-bold text-primary">View Banner</a></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Secretariat & Social Handles Card -->
                    <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4 bg-white">
                        <h5 class="fw-bold mb-4 text-dark"><i class="bi bi-geo-alt-fill text-danger me-2"></i> Secretariat & Meeting Schedule</h5>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Official Email</label>
                                <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($club['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Contact Phone</label>
                                <input type="text" name="phone" class="form-control rounded-3" value="<?= htmlspecialchars($club['phone'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Regular Meeting Time</label>
                                <input type="text" name="meeting_time" class="form-control rounded-3" value="<?= htmlspecialchars($club['meeting_time'] ?? '') ?>" placeholder="e.g. Wednesdays 04:00 PM">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Meeting Venue / Location</label>
                                <input type="text" name="meeting_location" class="form-control rounded-3" value="<?= htmlspecialchars($club['meeting_location'] ?? '') ?>" placeholder="e.g. Seminar Hall, UIT">
                            </div>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Instagram URL</label>
                                <input type="url" name="instagram" class="form-control rounded-3" value="<?= htmlspecialchars($club['instagram'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">LinkedIn URL</label>
                                <input type="url" name="linkedin" class="form-control rounded-3" value="<?= htmlspecialchars($club['linkedin'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">GitHub URL</label>
                                <input type="url" name="github" class="form-control rounded-3" value="<?= htmlspecialchars($club['github'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-semibold">Website URL</label>
                                <input type="url" name="website" class="form-control rounded-3" value="<?= htmlspecialchars($club['website'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- 3. Dynamic Section Display Switches Suite Card -->
                    <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4 bg-white">
                        <h5 class="fw-bold mb-2 text-dark"><i class="bi bi-sliders text-primary me-2"></i> Section Display & Feature Switches</h5>
                        <p class="text-secondary small mb-4">Turn public page sections ON or OFF. Only toggled ON sections will be displayed on <code>club-detail.html</code>.</p>

                        <!-- Toggle 1: Recent Achievements & Milestones -->
                        <div class="section-toggle-card p-4 mb-3">
                            <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 mb-0">
                                <label class="form-check-label fw-bold text-dark cursor-pointer mb-0" for="toggleAchievements">
                                    <i class="bi bi-trophy-fill text-warning me-2 fs-5"></i> Show Key Achievements & Milestones Banner
                                </label>
                                <input class="form-check-input ms-3" type="checkbox" role="switch" id="toggleAchievements" name="show_achievements" value="1" <?= ($club['show_achievements'] ?? 1) ? 'checked' : '' ?> onchange="document.getElementById('achievementsBox').classList.toggle('d-none', !this.checked)">
                            </div>
                            <div id="achievementsBox" class="mt-3 pt-3 border-top <?= ($club['show_achievements'] ?? 1) ? '' : 'd-none' ?>">
                                <label class="form-label small fw-semibold">Key Achievements & Highlights (One per line)</label>
                                <textarea name="achievements_text" class="form-control rounded-3" rows="3" placeholder="🏆 Winner of Smart India Hackathon 2025&#10;🚀 250+ Active Coders Onboarded in 2025-26&#10;⭐ SAC Best Technical Society Award 2025"><?= htmlspecialchars($club['achievements_text'] ?? '') ?></textarea>
                            </div>
                        </div>

                        <!-- Toggle 2: Executive Leadership & Core Committee Roster -->
                        <div class="section-toggle-card p-4 mb-3">
                            <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 mb-0">
                                <label class="form-check-label fw-bold text-dark cursor-pointer mb-0" for="toggleLeadership">
                                    <i class="bi bi-award-fill text-primary me-2 fs-5"></i> Show Executive Leadership & Core Committee Roster
                                </label>
                                <input class="form-check-input ms-3" type="checkbox" role="switch" id="toggleLeadership" name="show_leadership" value="1" <?= ($club['show_leadership'] ?? 1) ? 'checked' : '' ?>>
                            </div>
                        </div>

                        <!-- Toggle 3: Student Recruitment Notice -->
                        <div class="section-toggle-card p-4 mb-3">
                            <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 mb-0">
                                <label class="form-check-label fw-bold text-dark cursor-pointer mb-0" for="toggleRecruitment">
                                    <i class="bi bi-person-plus-fill text-success me-2 fs-5"></i> Show Student Recruitment & Hiring Notice
                                </label>
                                <input class="form-check-input ms-3" type="checkbox" role="switch" id="toggleRecruitment" name="show_recruitment" value="1" <?= ($club['show_recruitment'] ?? 1) ? 'checked' : '' ?>>
                            </div>
                        </div>

                        <!-- Toggle 4: Official Photo Gallery -->
                        <div class="section-toggle-card p-4 mb-4">
                            <div class="form-check form-switch d-flex align-items-center justify-content-between p-0 mb-0">
                                <label class="form-check-label fw-bold text-dark cursor-pointer mb-0" for="toggleGallery">
                                    <i class="bi bi-images text-info me-2 fs-5"></i> Show Chapter Photo Gallery & Memories
                                </label>
                                <input class="form-check-input ms-3" type="checkbox" role="switch" id="toggleGallery" name="show_gallery" value="1" <?= ($club['show_gallery'] ?? 1) ? 'checked' : '' ?>>
                            </div>
                        </div>

                        <div class="form-check form-switch mb-4">
                            <input class="form-check-input" type="checkbox" name="recruitment_open" id="recruitmentOpen" <?= ($club['recruitment_open']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-dark" for="recruitmentOpen">Set Recruitment Status to OPEN</label>
                        </div>

                        <button type="submit" class="btn btn-primary rounded-pill px-5 py-3 fw-bold text-white shadow-sm">
                            <i class="bi bi-floppy me-1.5"></i> Save Profile & Display Settings
                        </button>
                    </div>
                </form>
            </div>

            <!-- RIGHT COLUMN (5 COLS): Preview Header, Leadership Roster, Gallery & Honor Roll -->
            <div class="col-lg-5">
                
                <!-- 1. Live Chapter Card & Health Metrics -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white">
                    <?php if (!empty($club['cover_image'])): ?>
                        <div class="position-relative" style="height: 140px;">
                            <img src="<?= htmlspecialchars($club['cover_image']) ?>" class="w-100 h-100 object-fit-cover">
                            <div class="position-absolute inset-0" style="background: linear-gradient(180deg, rgba(15,23,42,0.1) 0%, rgba(15,23,42,0.7) 100%);"></div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <img src="<?= htmlspecialchars($club['logo'] ?: '../assets/United Logo.webp') ?>" class="rounded-4 border bg-white p-1 shadow-xs" style="width: 54px; height: 54px; object-fit: cover;">
                            <div class="min-w-0 flex-grow-1">
                                <h5 class="fw-bold text-dark mb-0 text-truncate"><?= htmlspecialchars($club['name']) ?></h5>
                                <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-0.5 small"><?= htmlspecialchars($club['short_name'] ?: 'Student Chapter') ?></span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="small fw-semibold text-secondary">Profile Setup Health</span>
                            <span class="badge bg-<?= $profileBadge[1] ?>-subtle text-<?= $profileBadge[1] ?> border rounded-pill px-3 py-1 fw-bold"><?= $profileScore ?>%</span>
                        </div>
                        <div class="progress rounded-pill mb-3" style="height: 8px;">
                            <div class="progress-bar bg-<?= $profileBadge[1] ?>" style="width: <?= $profileScore ?>%;"></div>
                        </div>

                        <a href="../club-detail.html?id=<?= urlencode($club['id']) ?>" target="_blank" class="btn btn-success rounded-pill w-100 py-2.5 fw-bold text-white shadow-sm">
                            <i class="bi bi-eye-fill me-1.5"></i> Open Live Public Page
                        </a>
                    </div>
                </div>

                <!-- 2. Annual Leadership Roster Card -->
                <div class="card p-4 border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0 text-dark"><i class="bi bi-award-fill text-primary me-2"></i> Annual Leadership Roster</h5>
                        <button class="btn btn-sm btn-primary rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#addLeaderModal">
                            <i class="bi bi-plus-lg me-1"></i> Add Member
                        </button>
                    </div>
                    <p class="text-secondary small mb-3">Faculty Coordinator, President, Core Leads & Annual Roster.</p>

                    <div class="d-flex flex-column gap-2.5">
                        <?php if (empty($roster)): ?>
                            <div class="text-center py-4 text-muted bg-light rounded-4">
                                <i class="bi bi-person-x fs-2 d-block mb-1"></i>
                                No leadership members added yet. Click "Add Member" to set up your team.
                            </div>
                        <?php else: ?>
                            <?php foreach ($roster as $r): ?>
                                <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-4 border">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="<?= htmlspecialchars($r['avatar']) ?>" class="roster-avatar-wrap" onerror="this.src='../assets/United Logo.webp'">
                                        <div>
                                            <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($r['name']) ?></h6>
                                            <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-0.5 small" style="font-size: 0.70rem;"><?= htmlspecialchars($r['role_title']) ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-1.5">
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-circle edit-leader-btn" 
                                                data-id="<?= htmlspecialchars($r['id']) ?>"
                                                data-name="<?= htmlspecialchars($r['name']) ?>"
                                                data-role="<?= htmlspecialchars($r['role_title']) ?>"
                                                data-term="<?= htmlspecialchars($r['term_year']) ?>"
                                                data-category="<?= htmlspecialchars($r['category']) ?>"
                                                data-email="<?= htmlspecialchars($r['email'] ?? '') ?>"
                                                data-phone="<?= htmlspecialchars($r['phone'] ?? '') ?>"
                                                data-avatar="<?= htmlspecialchars($r['avatar']) ?>"
                                                title="Edit Leader">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="profile.php?delete_leader=<?= $r['id'] ?>" class="btn btn-sm btn-outline-danger rounded-circle" onclick="return confirm('Remove this leader from roster?');" title="Remove Leader">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. Official Portfolio Gallery Manager Card (Below Leadership Roster as requested!) -->
                <div class="card p-4 border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-images text-primary me-2"></i> Official Chapter Photo Gallery</h5>
                    <p class="text-secondary small mb-3">Upload team photos & event memories to show on your public page.</p>

                    <form action="" method="POST" enctype="multipart/form-data" class="mb-3 p-3 bg-light rounded-4 border">
                        <input type="hidden" name="action" value="add_club_photo">
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark"><i class="bi bi-upload text-primary me-1"></i> Upload Photo File (PC)</label>
                            <input type="file" name="photo_file" class="form-control form-control-sm rounded-3" accept="image/*">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-dark">Or Image URL</label>
                            <input type="url" name="media_url" class="form-control form-control-sm rounded-3" placeholder="https://images.unsplash.com/...">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Photo Caption</label>
                            <input type="text" name="caption" class="form-control form-control-sm rounded-3" placeholder="e.g. Executive Team Orientation 2026">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill w-100 py-2 fw-bold text-white shadow-xs">
                            <i class="bi bi-cloud-arrow-up me-1"></i> Upload Photo to Gallery
                        </button>
                    </form>

                    <h6 class="fw-bold small text-muted border-bottom pb-2 mb-3">Uploaded Portfolio Photos (<?= count($clubPhotos) ?>)</h6>
                    <?php if (empty($clubPhotos)): ?>
                        <div class="text-center py-3 text-muted small bg-light rounded-3">No official photos uploaded yet.</div>
                    <?php else: ?>
                        <div class="row g-2">
                            <?php foreach ($clubPhotos as $photo): ?>
                                <div class="col-6">
                                    <div class="rounded-3 overflow-hidden border position-relative" style="height: 100px;">
                                        <img src="<?= htmlspecialchars($photo['media_url']) ?>" class="w-100 h-100 object-fit-cover">
                                        <a href="profile.php?delete_photo=<?= urlencode($photo['id']) ?>" onclick="return confirm('Delete this photo?');" class="btn btn-sm btn-danger rounded-circle position-absolute top-0 end-0 m-1 p-1 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.7rem;" title="Delete Photo">
                                            <i class="bi bi-x-lg"></i>
                                        </a>
                                    </div>
                                    <span class="small text-muted d-block text-truncate mt-1" style="font-size: 0.7rem;"><?= htmlspecialchars($photo['caption'] ?: 'Club Photo') ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 4. Chapter Honors & Recognition Card (Below Leadership Roster as requested!) -->
                <div class="card p-4 border-0 shadow-sm rounded-4 mb-4 bg-white">
                    <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-trophy-fill text-warning me-2"></i> Honors & Recognition</h5>
                    <p class="text-secondary small mb-3">Official Dean Student Welfare (SAC) recognitions & national competition awards.</p>
                    
                    <div class="p-3 bg-light rounded-4 border">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle bg-warning-subtle text-warning-emphasis p-2.5 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                                <i class="bi bi-shield-lock-fill fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark" style="font-size: 0.90rem;">100% SAC Verified Chapter</h6>
                                <p class="small text-secondary mb-0" style="font-size: 0.78rem;">Recognized by Student Welfare Advisory Committee, UIT Prayagraj.</p>
                            </div>
                        </div>
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
                        <input type="text" name="leader_name" class="form-control rounded-3" placeholder="e.g. Ansh Kumar Gupta" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Role Title *</label>
                            <input type="text" name="role_title" class="form-control rounded-3" placeholder="e.g. Chapter President / Campus Mantri" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Academic Term Year</label>
                            <input type="text" name="term_year" class="form-control rounded-3" value="2025-2026">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role Category</label>
                        <select name="category" class="form-select rounded-3">
                            <option value="faculty_coordinator">Faculty Coordinator / Mentor</option>
                            <option value="president" selected>President / Campus Lead</option>
                            <option value="vice_president">Vice President / Co-Lead</option>
                            <option value="secretary">Secretary / Core Lead</option>
                            <option value="core_member">Core Committee Member</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email (Optional)</label>
                            <input type="email" name="email" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone (Optional)</label>
                            <input type="text" name="phone" class="form-control rounded-3">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><i class="bi bi-upload text-primary me-1"></i> Upload Avatar Photo (PC)</label>
                        <input type="file" name="avatar_file" class="form-control rounded-3" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Add Leader</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Annual Leadership Member -->
<div class="modal fade" id="editLeaderModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold modal-title"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Leader Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_leader">
                <input type="hidden" name="leader_id" id="editLeaderId">
                <div class="modal-body space-y-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Member Name *</label>
                        <input type="text" name="leader_name" id="editLeaderName" class="form-control rounded-3" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Role Title *</label>
                            <input type="text" name="role_title" id="editRoleTitle" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Academic Term Year</label>
                            <input type="text" name="term_year" id="editTermYear" class="form-control rounded-3">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Role Category</label>
                        <select name="category" id="editCategory" class="form-select rounded-3">
                            <option value="faculty_coordinator">Faculty Coordinator / Mentor</option>
                            <option value="president">President / Campus Lead</option>
                            <option value="vice_president">Vice President / Co-Lead</option>
                            <option value="secretary">Secretary / Core Lead</option>
                            <option value="core_member">Core Committee Member</option>
                        </select>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" id="editEmail" class="form-control rounded-3">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone</label>
                            <input type="text" name="phone" id="editPhone" class="form-control rounded-3">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><i class="bi bi-upload text-primary me-1"></i> Replace Avatar Photo (PC)</label>
                        <input type="file" name="avatar_file" class="form-control rounded-3" accept="image/*">
                        <input type="hidden" name="avatar" id="editAvatarUrl">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Update Leader</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const editModal = new bootstrap.Modal(document.getElementById('editLeaderModal'));
    document.querySelectorAll('.edit-leader-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('editLeaderId').value = btn.dataset.id || '';
            document.getElementById('editLeaderName').value = btn.dataset.name || '';
            document.getElementById('editRoleTitle').value = btn.dataset.role || '';
            document.getElementById('editTermYear').value = btn.dataset.term || '';
            document.getElementById('editCategory').value = btn.dataset.category || 'core_member';
            document.getElementById('editEmail').value = btn.dataset.email || '';
            document.getElementById('editPhone').value = btn.dataset.phone || '';
            document.getElementById('editAvatarUrl').value = btn.dataset.avatar || '';
            editModal.show();
        });
    });
});
</script>
</body>
</html>
