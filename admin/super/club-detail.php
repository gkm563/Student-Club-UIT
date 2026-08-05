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

$clubId = trim($_GET['id'] ?? '');

if (empty($clubId)) {
    header('Location: clubs.php');
    exit;
}

// ── 1. Handle Status Toggle ──────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $cStmt = $db->prepare("SELECT name, status FROM clubs WHERE id = ?");
    $cStmt->execute([$clubId]);
    $cRow = $cStmt->fetch();
    
    if ($cRow) {
        $newStatus = ($cRow['status'] === 'active') ? 'inactive' : 'active';
        $stmt = $db->prepare("UPDATE clubs SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $clubId]);

        log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CLUB_STATUS_CHANGED', 'club', $clubId, "Set '" . $cRow['name'] . "' status to '$newStatus' from Club Detailed Overview");
        $message = "Club status updated to " . strtoupper($newStatus) . " successfully!";
    }
}

// ── 2. Handle Edit Club Details ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_club') {
    $name = trim($_POST['name'] ?? '');
    $shortName = trim($_POST['short_name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 1);
    $tagline = trim($_POST['tagline'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');

    if (!empty($name) && !empty($shortName)) {
        try {
            $stmt = $db->prepare("
                UPDATE clubs 
                SET name = ?, short_name = ?, category_id = ?, tagline = ?, description = ?, email = ?, website = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $shortName, $categoryId, $tagline, $description, $email, $website, $clubId]);

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CLUB_EDITED', 'club', $clubId, "Updated club details for '$name' ($shortName) from Detailed Overview");
            $message = "Club details for '$name' updated successfully!";
        } catch (Exception $e) {
            $error = 'Error updating club: ' . $e->getMessage();
        }
    }
}

// ── 3. Handle Leadership Password Reset ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $userId = $_POST['user_id'] ?? '';
    $newPass = trim($_POST['new_password'] ?? '');

    if (!empty($userId) && !empty($newPass)) {
        try {
            $passHash = password_hash($newPass, PASSWORD_DEFAULT);
            $rStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $rStmt->execute([$passHash, $userId]);

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CREDENTIAL_RESET', 'user', $userId, "Reset password for club admin user ID $userId from Detailed Overview");
            $message = "Password updated successfully for club leadership account.";
        } catch (Exception $e) {
            $error = "Error resetting password: " . $e->getMessage();
        }
    }
}

// ── 4. Handle Direct Advisory Dispatch to Club Lead ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'dispatch_advisory') {
    $advisoryNote = trim($_POST['advisory_note'] ?? '');
    if (!empty($advisoryNote)) {
        try {
            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'DEAN_ADVISORY_ISSUED', 'club', $clubId, "Dispatched Dean Executive Advisory to club ID '$clubId': '$advisoryNote'");
            $message = "Official Dean Executive Advisory dispatched successfully to Chapter Leadership!";
        } catch (Exception $e) {
            $error = "Error dispatching advisory: " . $e->getMessage();
        }
    }
}

// ── Fetch Club Master Details ────────────────────────────────────────
$clubStmt = $db->prepare("
    SELECT c.*, cat.name as category_name, cat.icon as category_icon, u.id as user_id, u.email as admin_email, u.full_name as admin_name
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN club_admins ca ON ca.club_id = c.id
    LEFT JOIN users u ON ca.user_id = u.id
    WHERE c.id = ?
    LIMIT 1
");
$clubStmt->execute([$clubId]);
$club = $clubStmt->fetch();

if (!$club) {
    header('Location: clubs.php');
    exit;
}

// Categories for edit modal
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Leadership Roster
$leadStmt = $db->prepare("SELECT * FROM leadership WHERE club_id = ? ORDER BY order_index ASC, id ASC");
$leadStmt->execute([$clubId]);
$leaders = $leadStmt->fetchAll();

// Club Events Portfolio
$eventStmt = $db->prepare("SELECT * FROM events WHERE club_id = ? ORDER BY event_date DESC");
$eventStmt->execute([$clubId]);
$events = $eventStmt->fetchAll();

// Gallery Photos
$galStmt = $db->prepare("SELECT * FROM gallery_items WHERE club_id = ? ORDER BY created_at DESC LIMIT 16");
$galStmt->execute([$clubId]);
$gallery = $galStmt->fetchAll();

// Specific Audit Trail Logs
$auditStmt = $db->prepare("
    SELECT * FROM audit_logs 
    WHERE details LIKE ? 
    ORDER BY created_at DESC LIMIT 12
");
$auditStmt->execute(['%' . $club['name'] . '%']);
$auditLogs = $auditStmt->fetchAll();

// ── Executive Analytical Metrics ─────────────────────────────────────
$eventsTotalCount   = count($events);
$eventsLast7Days    = (int)$db->query("SELECT COUNT(*) FROM events WHERE club_id = '$clubId' AND event_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$eventsThisMonth    = (int)$db->query("SELECT COUNT(*) FROM events WHERE club_id = '$clubId' AND event_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
$totalAttended      = (int)$db->query("SELECT SUM(actual_attended) FROM events WHERE club_id = '$clubId'")->fetchColumn();
$totalBudget        = (float)$db->query("SELECT SUM(budget_utilized) FROM events WHERE club_id = '$clubId'")->fetchColumn();
$galleryCount       = (int)$db->query("SELECT COUNT(*) FROM gallery_items WHERE club_id = '$clubId'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($club['name']) ?> – 360° Executive Intelligence | Dean Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #1e293b; }

        .exec-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            padding: 20px 24px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.03);
            transition: all 0.25s ease;
        }

        .club-banner-header {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px 28px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
        }

        .kpi-icon-box {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .gallery-card-img {
            height: 180px;
            object-fit: cover;
            border-radius: 12px;
            transition: transform 0.25s ease;
            cursor: pointer;
        }
        .gallery-card-img:hover {
            transform: scale(1.03);
        }

        .table-custom th {
            font-size: 0.72rem;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            font-weight: 700;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 16px;
        }
        .table-custom td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }

        .nav-pills-custom .nav-link {
            border-radius: 50rem;
            padding: 10px 22px;
            font-weight: 700;
            font-size: 0.86rem;
            color: #64748b;
            transition: all 0.2s ease;
        }
        .nav-pills-custom .nav-link.active {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.25);
        }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">
    <!-- Universal Super Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Workspace -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">

        <!-- Top Navigation Bar -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <a href="clubs.php" class="btn btn-light border rounded-pill px-3 py-1.5 text-secondary fw-semibold small">
                <i class="bi bi-arrow-left me-1"></i> Back to Campus Roster
            </a>

            <div class="d-flex align-items-center gap-2 flex-wrap">
                <!-- See Chapter Live on Public Website Button -->
                <a href="../../club-detail.php?id=<?= $club['id'] ?>" target="_blank" class="btn btn-sm btn-outline-dark rounded-pill px-3.5 py-2 fw-bold shadow-sm" title="View live public student page for this chapter">
                    <i class="bi bi-globe me-1 text-primary"></i> See Chapter Live on Public Website <i class="bi bi-box-arrow-up-right ms-1" style="font-size:0.7rem;"></i>
                </a>

                <!-- View Chapter Analytics Button -->
                <a href="analytics.php?club_id=<?= $club['id'] ?>" class="btn btn-sm btn-purple rounded-pill px-3.5 py-2 fw-bold text-white shadow-sm" style="background:linear-gradient(135deg,#7c3aed,#4f46e5); border:none;" title="View Chapter Deep Analytics">
                    <i class="bi bi-bar-chart-line-fill me-1"></i> Chapter Analytics &rarr;
                </a>

                <!-- Status Toggle Action Button -->
                <a href="club-detail.php?id=<?= $club['id'] ?>&action=toggle_status" 
                   class="btn btn-sm <?= ($club['status'] === 'active') ? 'btn-outline-danger' : 'btn-success' ?> rounded-pill px-3.5 py-2 fw-bold shadow-sm">
                    <i class="bi <?= ($club['status'] === 'active') ? 'bi-power' : 'bi-check-circle-fill' ?> me-1"></i>
                    <?= ($club['status'] === 'active') ? 'Suspend Chapter (OFF)' : 'Activate Chapter (ON)' ?>
                </a>

                <!-- Contact Lead Button -->
                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3.5 py-2 fw-bold text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#contactLeadModal">
                    <i class="bi bi-chat-text-fill me-1"></i> Contact Club Lead
                </button>

                <!-- Edit Club Details Modal Button -->
                <button type="button" class="btn btn-sm btn-dark rounded-pill px-3.5 py-2 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#editClubModal">
                    <i class="bi bi-pencil-square me-1"></i> Edit Chapter
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Executive Club Header Banner -->
        <div class="club-banner-header mb-4 position-relative overflow-hidden">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-4">
                <div class="d-flex align-items-center gap-4">
                    <img src="<?= htmlspecialchars($club['logo'] ?: '../../assets/United Logo.webp') ?>" 
                         class="rounded-4 border shadow-sm flex-shrink-0" 
                         style="width:96px; height:96px; object-fit:cover;" alt="Club Logo">
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">
                                <i class="<?= htmlspecialchars($club['category_icon'] ?: 'bi-tag') ?> me-1"></i>
                                <?= htmlspecialchars($club['category_name']) ?>
                            </span>
                            <?php if ($club['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success border rounded-pill px-2.5 py-1 small"><i class="bi bi-circle-fill me-1" style="font-size:0.5rem;"></i> Active Chapter</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger border rounded-pill px-2.5 py-1 small"><i class="bi bi-x-circle-fill me-1"></i> Suspended</span>
                            <?php endif; ?>
                        </div>
                        <h2 class="fw-bold text-dark mb-1"><?= htmlspecialchars($club['name']) ?> <span class="text-secondary fs-5 font-monospace">(<?= htmlspecialchars($club['short_name']) ?>)</span></h2>
                        <p class="text-secondary mb-0 small"><i class="bi bi-quote me-1"></i><?= htmlspecialchars($club['tagline'] ?: 'Official Student Chapter of United Institute of Technology') ?></p>
                    </div>
                </div>

                <!-- Assigned Lead Info Box -->
                <div class="bg-light p-3 rounded-4 border text-md-end flex-shrink-0">
                    <div class="text-secondary small font-monospace fw-bold text-uppercase" style="font-size:0.68rem;">OFFICIAL CLUB LEAD</div>
                    <div class="fw-bold text-dark fs-6 mt-1"><i class="bi bi-person-circle text-primary me-1"></i><?= htmlspecialchars($club['admin_name'] ?: 'Unassigned Lead') ?></div>
                    <div class="small text-secondary font-monospace"><?= htmlspecialchars($club['admin_email'] ?: 'No email configured') ?></div>
                    <?php if ($club['user_id']): ?>
                        <button type="button" class="btn btn-xs btn-link p-0 text-decoration-none small text-warning fw-bold mt-1" data-bs-toggle="modal" data-bs-target="#resetPassModal">
                            <i class="bi bi-key-fill me-1"></i> Reset Password
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- 6 Executive KPI Analytics Cards Deck -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-4 col-xl-2">
                <div class="exec-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small font-monospace fw-bold">TOTAL EVENTS</span>
                        <div class="kpi-icon-box bg-primary-subtle text-primary" style="width:36px;height:36px;font-size:1rem;"><i class="bi bi-trophy-fill"></i></div>
                    </div>
                    <div class="fs-4 fw-bold text-dark lh-1"><?= $eventsTotalCount ?></div>
                    <div class="text-secondary" style="font-size:0.7rem;">All Time</div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="exec-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small font-monospace fw-bold">LAST 7 DAYS</span>
                        <div class="kpi-icon-box bg-warning-subtle text-warning" style="width:36px;height:36px;font-size:1rem;"><i class="bi bi-lightning-charge-fill"></i></div>
                    </div>
                    <div class="fs-4 fw-bold text-dark lh-1"><?= $eventsLast7Days ?></div>
                    <div class="text-warning fw-semibold" style="font-size:0.7rem;">Recent Events</div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="exec-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small font-monospace fw-bold">THIS MONTH</span>
                        <div class="kpi-icon-box bg-info-subtle text-info" style="width:36px;height:36px;font-size:1rem;"><i class="bi bi-calendar-check-fill"></i></div>
                    </div>
                    <div class="fs-4 fw-bold text-dark lh-1"><?= $eventsThisMonth ?></div>
                    <div class="text-info fw-semibold" style="font-size:0.7rem;">Last 30 Days</div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="exec-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small font-monospace fw-bold">ATTENDEES</span>
                        <div class="kpi-icon-box bg-success-subtle text-success" style="width:36px;height:36px;font-size:1rem;"><i class="bi bi-people-fill"></i></div>
                    </div>
                    <div class="fs-4 fw-bold text-dark lh-1"><?= number_format($totalAttended) ?></div>
                    <div class="text-success fw-semibold" style="font-size:0.7rem;">Student Reach</div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="exec-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small font-monospace fw-bold">PHOTOS / MEDIA</span>
                        <div class="kpi-icon-box bg-purple-subtle text-purple" style="width:36px;height:36px;font-size:1rem;background:#f5f3ff;color:#7c3aed;"><i class="bi bi-image-fill"></i></div>
                    </div>
                    <div class="fs-4 fw-bold text-dark lh-1"><?= $galleryCount ?></div>
                    <div class="text-purple fw-semibold" style="font-size:0.7rem;color:#7c3aed;">Media Uploads</div>
                </div>
            </div>

            <div class="col-6 col-md-4 col-xl-2">
                <div class="exec-card p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="text-secondary small font-monospace fw-bold">CORE ROSTER</span>
                        <div class="kpi-icon-box bg-secondary-subtle text-secondary" style="width:36px;height:36px;font-size:1rem;"><i class="bi bi-person-badge-fill"></i></div>
                    </div>
                    <div class="fs-4 fw-bold text-dark lh-1"><?= count($leaders) ?></div>
                    <div class="text-secondary" style="font-size:0.7rem;">Active Officers</div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs: Overview & Roster, Events Portfolio, Gallery, Audit -->
        <ul class="nav nav-pills nav-pills-custom gap-2 mb-4" id="clubDetailTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#tab-overview">
                    <i class="bi bi-shield-lock me-1.5"></i> Chapter Roster & Leadership
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="events-tab" data-bs-toggle="pill" data-bs-target="#tab-events">
                    <i class="bi bi-calendar-event me-1.5"></i> Events Portfolio (<?= count($events) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="gallery-tab" data-bs-toggle="pill" data-bs-target="#tab-gallery">
                    <i class="bi bi-images me-1.5"></i> Photo Gallery (<?= count($gallery) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" id="audit-tab" data-bs-toggle="pill" data-bs-target="#tab-audit">
                    <i class="bi bi-activity me-1.5"></i> Governance Audit Trail
                </button>
            </li>
        </ul>

        <!-- Tab Panes Content -->
        <div class="tab-content">
            
            <!-- TAB 1: CHAPTER ROSTER & LEADERSHIP HIERARCHY -->
            <div class="tab-pane fade show active" id="tab-overview">
                <div class="row g-4">
                    
                    <!-- Left: Founding & Active Leadership Roster -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3 pb-3 border-bottom">
                                <div>
                                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 small">CAMPUS LEADERSHIP DIRECTORY</span>
                                    <h5 class="fw-bold text-dark mb-0 mt-1">Founding & Active Executive Officers</h5>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#contactLeadModal">
                                    <i class="bi bi-send me-1"></i> Contact Lead
                                </button>
                            </div>

                            <?php if (empty($leaders)): ?>
                                <div class="text-center py-5 text-muted">
                                    <i class="bi bi-people fs-1 text-secondary opacity-50 d-block mb-2"></i>
                                    No leadership officers configured for this chapter yet.
                                </div>
                            <?php else: ?>
                                <div class="row g-3">
                                    <?php foreach ($leaders as $idx => $ldr): ?>
                                        <div class="col-md-6">
                                            <div class="p-3 rounded-4 border bg-light d-flex align-items-center gap-3">
                                                <img src="<?= htmlspecialchars(!empty($ldr['avatar']) ? $ldr['avatar'] : 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=300&auto=format&fit=crop') ?>" 
                                                     class="rounded-circle border flex-shrink-0" 
                                                     style="width:54px; height:54px; object-fit:cover;" alt="">
                                                <div class="overflow-hidden">
                                                    <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                        <span class="fw-bold text-dark small text-truncate"><?= htmlspecialchars($ldr['name']) ?></span>
                                                        <?php if ($idx === 0): ?>
                                                            <span class="badge bg-warning text-dark rounded-pill px-2 py-0.5" style="font-size:0.62rem;">Founding President</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-primary fw-bold" style="font-size:0.78rem;"><?= htmlspecialchars($ldr['role_title'] ?? $ldr['category']) ?></div>
                                                    <?php if (!empty($ldr['email'])): ?>
                                                        <div class="text-secondary font-monospace" style="font-size:0.72rem;"><?= htmlspecialchars($ldr['email']) ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Chapter Description & Objectives -->
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle text-primary me-2"></i>Chapter Charter & Description</h5>
                            <p class="text-secondary leading-relaxed small mb-0"><?= nl2br(htmlspecialchars($club['description'] ?: 'No detailed description provided for this student chapter.')) ?></p>
                        </div>
                    </div>

                    <!-- Right: Chapter Quick Credentials & Contact Cards -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 bg-white p-4 mb-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check text-success me-2"></i>Chapter Official Metadata</h6>
                            <div class="d-flex flex-column gap-3 font-monospace small">
                                <div>
                                    <span class="text-secondary d-block font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">CHAPTER ID</span>
                                    <code class="fs-6 text-dark"><?= htmlspecialchars($club['id']) ?></code>
                                </div>
                                <div>
                                    <span class="text-secondary d-block font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">DOMAIN CATEGORY</span>
                                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1"><?= htmlspecialchars($club['category_name']) ?></span>
                                </div>
                                <div>
                                    <span class="text-secondary d-block font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">OFFICIAL CONTACT EMAIL</span>
                                    <a href="mailto:<?= htmlspecialchars($club['email']) ?>" class="text-primary text-decoration-underline"><?= htmlspecialchars($club['email'] ?: 'Not configured') ?></a>
                                </div>
                                <div>
                                    <span class="text-secondary d-block font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">WEBSITE / PORTAL</span>
                                    <a href="<?= htmlspecialchars($club['website'] ?: '#') ?>" target="_blank" class="text-primary text-decoration-underline"><?= htmlspecialchars($club['website'] ?: 'N/A') ?></a>
                                </div>
                                <div>
                                    <span class="text-secondary d-block font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">FOUNDED DATE</span>
                                    <span class="text-dark fw-bold"><?= date('F j, Y', strtotime($club['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Direct Advisory Dispatch Box -->
                        <div class="card border-0 shadow-sm rounded-4 p-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);">
                            <h6 class="fw-bold mb-2 text-white"><i class="bi bi-send-check-fill text-warning me-2"></i>Dispatch Dean Advisory</h6>
                            <p class="small text-white-50 mb-3">Send official institutional instructions directly to the Club President & Lead officers.</p>
                            <form action="club-detail.php?id=<?= $club['id'] ?>" method="POST">
                                <input type="hidden" name="action" value="dispatch_advisory">
                                <textarea name="advisory_note" class="form-control bg-dark border-secondary text-white rounded-3 small mb-3" rows="3" placeholder="Enter official Dean advisory note..." required></textarea>
                                <button type="submit" class="btn btn-warning text-dark font-monospace fw-bold btn-sm rounded-pill w-100">
                                    <i class="bi bi-send-fill me-1"></i> Send Advisory Notice
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

            <!-- TAB 2: EVENTS PORTFOLIO & INTERACTIVE AUDIT MODALS -->
            <div class="tab-pane fade" id="tab-events">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-event text-primary me-2"></i>Events Portfolio Conducted by <?= htmlspecialchars($club['name']) ?></h6>
                        <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 font-monospace"><?= count($events) ?> Events Recorded</span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="clubEventsTable">
                            <thead class="table-light">
                                <tr class="small text-secondary">
                                    <th>EVENT TITLE</th>
                                    <th>DATE & VENUE</th>
                                    <th>SPEAKER / GUEST</th>
                                    <th>ATTENDANCE & BUDGET</th>
                                    <th>STATUS</th>
                                    <th class="text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($events)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">No events recorded for this chapter yet.</td>
                                    </tr>
                                <?php else: foreach ($events as $ev): ?>
                                        <td>
                                            <a href="javascript:void(0)" class="fw-bold text-dark text-decoration-none hover-primary mb-0 d-block" data-bs-toggle="modal" data-bs-target="#clubEventAuditModal_<?= $ev['id'] ?>" title="Click to view full event details & dossier">
                                                <?= htmlspecialchars($ev['title']) ?> <i class="bi bi-info-circle text-primary fs-7 ms-1"></i>
                                            </a>
                                            <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5 mt-1" style="font-size:0.68rem;"><?= htmlspecialchars($ev['event_type'] ?? 'General Event') ?></span>
                                        </td>
                                        <td class="small">
                                            <div class="fw-semibold text-dark"><i class="bi bi-calendar3 me-1 text-primary"></i><?= date('M d, Y', strtotime($ev['event_date'])) ?></div>
                                            <div class="text-secondary" style="font-size:0.75rem;"><i class="bi bi-geo-alt me-1 text-danger"></i><?= htmlspecialchars($ev['venue'] ?? 'Campus Grounds') ?></div>
                                        </td>
                                        <td class="small">
                                            <?php if (!empty($ev['speaker_name'])): ?>
                                                <div class="fw-bold text-dark"><?= htmlspecialchars($ev['speaker_name']) ?></div>
                                                <div class="text-muted" style="font-size:0.72rem;"><?= htmlspecialchars($ev['speaker_designation'] ?: 'Guest Speaker') ?></div>
                                            <?php else: ?>
                                                <span class="text-muted italic">Internal Lead Event</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small font-monospace">
                                            <div><i class="bi bi-people text-success me-1"></i><?= number_format($ev['actual_attended'] ?? 0) ?> Attended</div>
                                            <div class="text-secondary" style="font-size:0.72rem;">Budget: ₹<?= number_format(floatval($ev['budget_utilized'] ?? 0)) ?></div>
                                        </td>
                                        <td>
                                            <?php
                                            $st = strtolower($ev['status']);
                                            $bClass = ($st === 'completed') ? 'bg-success-subtle text-success' : (($st === 'upcoming') ? 'bg-primary-subtle text-primary' : 'bg-warning-subtle text-warning');
                                            ?>
                                            <span class="badge <?= $bClass ?> border rounded-pill px-2.5 py-1 small"><?= ucfirst($st) ?></span>
                                        </td>
                                        <td class="text-end">
                                            <button type="button" class="btn btn-sm btn-light rounded-circle" data-bs-toggle="modal" data-bs-target="#eventAuditModal_<?= $ev['id'] ?>" title="View Executive Event Audit">
                                                <i class="bi bi-eye-fill text-primary"></i>
                                            </button>

                                            <!-- Event Detail Modal -->
                                            <div class="modal fade text-start" id="eventAuditModal_<?= $ev['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content rounded-4 border-0 shadow-lg">
                                                        <div class="modal-header border-0 pb-0 p-4">
                                                            <div>
                                                                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 small mb-1">EVENT GOVERNANCE AUDIT</span>
                                                                <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($ev['title']) ?></h4>
                                                            </div>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body p-4">
                                                            <?php if (!empty($ev['banner'])): ?>
                                                                <img src="<?= htmlspecialchars($ev['banner']) ?>" class="w-100 rounded-3 mb-3 object-fit-cover" style="max-height:220px;" alt="">
                                                            <?php endif; ?>

                                                            <div class="row g-3 mb-4">
                                                                <div class="col-md-6">
                                                                    <div class="bg-light p-3 rounded-3 border">
                                                                        <span class="text-secondary d-block small font-monospace">CHAPTER & LEAD</span>
                                                                        <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($club['name']) ?></span>
                                                                        <div class="small text-muted mt-1">Lead: <strong><?= htmlspecialchars($club['admin_name'] ?: 'Club President') ?></strong></div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <div class="bg-light p-3 rounded-3 border">
                                                                        <span class="text-secondary d-block small font-monospace">DATE & VENUE</span>
                                                                        <span class="fw-bold text-dark fs-6"><?= date('l, F j, Y', strtotime($ev['event_date'])) ?></span>
                                                                        <div class="small text-muted mt-1">Venue: <strong><?= htmlspecialchars($ev['venue'] ?? 'Campus Grounds') ?></strong></div>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="row g-3 mb-4">
                                                                <div class="col-md-4">
                                                                    <div class="p-3 border rounded-3 bg-success-subtle text-success">
                                                                        <span class="d-block small text-uppercase font-monospace fw-bold">REGISTERED COUNT</span>
                                                                        <span class="fs-4 fw-bold"><?= number_format($ev['registered_count'] ?? 0) ?></span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="p-3 border rounded-3 bg-primary-subtle text-primary">
                                                                        <span class="d-block small text-uppercase font-monospace fw-bold">ACTUAL ATTENDED</span>
                                                                        <span class="fs-4 fw-bold"><?= number_format($ev['actual_attended'] ?? 0) ?></span>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-4">
                                                                    <div class="p-3 border rounded-3 bg-purple-subtle text-purple" style="background:#f5f3ff; color:#7c3aed;">
                                                                        <span class="d-block small text-uppercase font-monospace fw-bold">BUDGET UTILIZED</span>
                                                                        <span class="fs-4 fw-bold">₹<?= number_format(floatval($ev['budget_utilized'] ?? 0)) ?></span>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <?php if (!empty($ev['description'])): ?>
                                                                <div class="mb-3">
                                                                    <h6 class="fw-bold text-dark mb-1">Event Summary & Agenda</h6>
                                                                    <p class="small text-secondary mb-0"><?= nl2br(htmlspecialchars($ev['description'])) ?></p>
                                                                </div>
                                                            <?php endif; ?>

                                                            <?php if (!empty($ev['outcomes_summary'])): ?>
                                                                <div class="mb-3">
                                                                    <h6 class="fw-bold text-dark mb-1">Outcomes & Impact Summary</h6>
                                                                    <div class="p-3 bg-light rounded-3 border small text-dark"><?= nl2br(htmlspecialchars($ev['outcomes_summary'])) ?></div>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="modal-footer border-0 p-4 pt-0">
                                                            <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close Audit</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: PHOTO GALLERY GRID WITH ZOOM OVERLAY -->
            <div class="tab-pane fade" id="tab-gallery">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                    <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom">
                        <div>
                            <span class="badge bg-purple-subtle text-purple border rounded-pill px-3 py-1 small" style="background:#f5f3ff; color:#7c3aed;">CHAPTER MEDIA GALLERY</span>
                            <h5 class="fw-bold text-dark mb-0 mt-1">Uploaded Event Photos & Banners</h5>
                        </div>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 font-monospace"><?= count($gallery) ?> Media Items</span>
                    </div>

                    <?php if (empty($gallery)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-images fs-1 text-secondary opacity-50 d-block mb-2"></i>
                            No gallery media items uploaded for this chapter yet.
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach ($gallery as $g): ?>
                                <div class="col-6 col-md-4 col-lg-3">
                                    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                                        <img src="<?= htmlspecialchars($g['media_url']) ?>" 
                                             class="gallery-card-img w-100" 
                                             alt=""
                                             onclick="openPhotoZoom('<?= htmlspecialchars($g['media_url']) ?>', '<?= htmlspecialchars(addslashes($g['caption'] ?? 'Chapter Photo')) ?>')">
                                        <div class="p-2 bg-light border-top">
                                            <div class="fw-bold text-dark small text-truncate"><?= htmlspecialchars($g['caption'] ?: 'Chapter Event Photo') ?></div>
                                            <span class="text-muted" style="font-size:0.7rem;"><?= date('M d, Y', strtotime($g['created_at'])) ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TAB 4: AUDIT TRAIL LOGS -->
            <div class="tab-pane fade" id="tab-audit">
                <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                    <h5 class="fw-bold text-dark mb-3"><i class="bi bi-activity text-purple me-2" style="color:#7c3aed;"></i>Chapter Security & Institutional Audit Trail</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-secondary">
                                    <th>TIMESTAMP</th>
                                    <th>PERFORMED BY</th>
                                    <th>ACTION LOGGED</th>
                                    <th>DETAILS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($auditLogs)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">No specific audit trail records found for this chapter.</td>
                                    </tr>
                                <?php else: foreach ($auditLogs as $log): ?>
                                    <tr>
                                        <td class="small font-monospace text-secondary"><?= date('M d, Y h:i A', strtotime($log['created_at'])) ?></td>
                                        <td class="fw-bold text-dark small"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                                        <td><span class="badge bg-secondary-subtle text-secondary rounded-pill px-2.5 py-1 small"><?= htmlspecialchars($log['action']) ?></span></td>
                                        <td class="small text-secondary"><?= htmlspecialchars($log['details']) ?></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>

<!-- Contact Lead Modal -->
<div class="modal fade" id="contactLeadModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 p-4">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-chat-text-fill text-primary me-2"></i> Contact Club Leadership</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div class="small text-secondary font-monospace fw-bold text-uppercase">LEAD RECIPIENT</div>
                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($club['admin_name'] ?: 'Club President') ?></div>
                    <div class="small text-primary font-monospace"><?= htmlspecialchars($club['admin_email'] ?: 'No email') ?></div>
                </div>
                <div class="d-flex gap-2">
                    <a href="mailto:<?= htmlspecialchars($club['admin_email']) ?>?subject=Executive%20Dean%20Communication%20-%20<?= urlencode($club['name']) ?>" class="btn btn-primary rounded-pill px-4 fw-bold flex-grow-1">
                        <i class="bi bi-envelope-fill me-1"></i> Send Official Email
                    </a>
                    <?php if ($club['phone']): ?>
                        <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $club['phone']) ?>" target="_blank" class="btn btn-success rounded-pill px-3 fw-bold">
                            <i class="bi bi-whatsapp"></i> WhatsApp
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Club Modal -->
<div class="modal fade" id="editClubModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 p-4">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Chapter Master Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="club-detail.php?id=<?= $club['id'] ?>" method="POST">
                <input type="hidden" name="action" value="edit_club">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Club Name *</label>
                            <input type="text" name="name" class="form-control rounded-3" value="<?= htmlspecialchars($club['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Short Code *</label>
                            <input type="text" name="short_name" class="form-control rounded-3" value="<?= htmlspecialchars($club['short_name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Domain Category *</label>
                            <select name="category_id" class="form-select rounded-3" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= ($cat['id'] == $club['category_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Official Contact Email</label>
                            <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($club['email']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Tagline / Motto</label>
                            <input type="text" name="tagline" class="form-control rounded-3" value="<?= htmlspecialchars($club['tagline']) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Detailed Description</label>
                            <textarea name="description" class="form-control rounded-3" rows="4"><?= htmlspecialchars($club['description']) ?></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<?php if ($club['user_id']): ?>
<div class="modal fade" id="resetPassModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 p-4">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-key-fill text-warning me-2"></i> Reset Lead Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="club-detail.php?id=<?= $club['id'] ?>" method="POST">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" value="<?= $club['user_id'] ?>">
                <div class="modal-body p-4">
                    <div class="p-3 bg-light rounded-3 border mb-3 small font-monospace">
                        <div>Lead Name: <strong><?= htmlspecialchars($club['admin_name']) ?></strong></div>
                        <div>Email: <code><?= htmlspecialchars($club['admin_email']) ?></code></div>
                    </div>
                    <label class="form-label small fw-semibold">New Strong Password *</label>
                    <input type="password" name="new_password" class="form-control rounded-3" placeholder="Enter new password" required>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Photo Zoom Modal -->
<div class="modal fade" id="photoZoomModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark text-white rounded-4 border-0 shadow-lg overflow-hidden">
            <div class="modal-header border-0 p-3 bg-dark">
                <h6 class="modal-title fw-bold text-white mb-0" id="photoZoomTitle">Photo Preview</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 text-center bg-black">
                <img id="photoZoomImg" src="" class="img-fluid w-100 object-fit-contain" style="max-height:80vh;" alt="">
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openPhotoZoom(url, title) {
    document.getElementById('photoZoomImg').src = url;
    document.getElementById('photoZoomTitle').innerText = title || 'Chapter Photo Preview';
    const zoomModal = new bootstrap.Modal(document.getElementById('photoZoomModal'));
    zoomModal.show();
}
</script>
</body>
</html>