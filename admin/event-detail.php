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

$eventId = $_GET['id'] ?? '';
if (empty($eventId)) {
    header('Location: /admin/events.php');
    exit;
}

// Fetch event details
$evtStmt = $db->prepare("SELECT * FROM events WHERE id = ? AND club_id = ?");
$evtStmt->execute([$eventId, $club['id']]);
$event = $evtStmt->fetch();

if (!$event) {
    header('Location: /admin/events.php?error=Event+not+found');
    exit;
}

$success = '';
$error = '';

// Handle Update Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update'])) {
    $title              = trim($_POST['title'] ?? '');
    $tagline            = trim($_POST['tagline'] ?? '');
    $event_type         = trim($_POST['event_type'] ?? 'Workshop');
    $description        = trim($_POST['description'] ?? '');
    $venue              = trim($_POST['venue'] ?? '');
    $event_date         = $_POST['event_date'] ?? '';
    $reg_link           = trim($_POST['registration_link'] ?? 'contact.html');
    $status             = $_POST['status'] ?? 'upcoming';
    $bannerUrl          = trim($_POST['banner'] ?? 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop');
    
    // Detailed Fields
    $registered_count   = (int)($_POST['registered_count'] ?? 0);
    $actual_attended    = (int)($_POST['actual_attended'] ?? 0);
    $outcomes_summary   = trim($_POST['outcomes_summary'] ?? '');
    $speaker_name       = trim($_POST['speaker_name'] ?? '');
    $speaker_designation= trim($_POST['speaker_designation'] ?? '');
    $agenda_timeline    = trim($_POST['agenda_timeline'] ?? '');
    $target_audience    = trim($_POST['target_audience'] ?? 'All Departments & Years');
    $budget_utilized    = (float)($_POST['budget_utilized'] ?? 0.0);

    // Process uploaded image file if provided
    $uploadedBanner = upload_image_file($_FILES['banner_file'] ?? null, 'events', $event['banner'] ?? $bannerUrl);
    $banner = !empty($uploadedBanner) ? $uploadedBanner : (!empty($bannerUrl) ? $bannerUrl : $event['banner']);

    if (empty($title) || empty($venue) || empty($event_date)) {
        $error = "Title, venue, and date are required.";
    } else {
        try {
            $uStmt = $db->prepare("
                UPDATE events SET 
                    title = ?, tagline = ?, event_type = ?, description = ?, venue = ?, event_date = ?, registration_link = ?, status = ?, banner = ?,
                    registered_count = ?, actual_attended = ?, outcomes_summary = ?, speaker_name = ?, speaker_designation = ?, agenda_timeline = ?, target_audience = ?, budget_utilized = ?
                WHERE id = ? AND club_id = ?
            ");
            $uStmt->execute([
                $title, $tagline, $event_type, $description, $venue, $event_date, $reg_link, $status, $banner,
                $registered_count, $actual_attended, $outcomes_summary, $speaker_name, $speaker_designation, $agenda_timeline, $target_audience, $budget_utilized,
                $eventId, $club['id']
            ]);
            $success = "Event details and advanced documentation updated successfully!";
            
            // Refresh event data
            $evtStmt->execute([$eventId, $club['id']]);
            $event = $evtStmt->fetch();
        } catch (Exception $e) {
            $error = "Failed to update event: " . $e->getMessage();
        }
    }
}

// Handle Upload Event Gallery Photo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_upload_event_photo'])) {
    $mediaUrl = trim($_POST['media_url'] ?? '');
    $caption  = trim($_POST['caption'] ?? '');

    $uploadedPhoto = upload_image_file($_FILES['photo_file'] ?? null, 'events', $mediaUrl);
    $finalUrl = $uploadedPhoto ?: $mediaUrl;

    if (empty($finalUrl)) {
        $error = "Please select an image file or provide an image URL.";
    } else {
        try {
            $galId = 'gal_' . bin2hex(random_bytes(4));
            $gStmt = $db->prepare("INSERT INTO gallery_items (id, club_id, event_id, media_url, caption) VALUES (?, ?, ?, ?, ?)");
            $gStmt->execute([$galId, $club['id'], $eventId, $finalUrl, $caption]);
            $success = "Event photo uploaded successfully!";
        } catch (Exception $e) {
            $error = "Failed to upload photo: " . $e->getMessage();
        }
    }
}

// Handle Delete Event Gallery Photo
if (isset($_GET['delete_photo'])) {
    $photoId = $_GET['delete_photo'];
    $dpStmt = $db->prepare("DELETE FROM gallery_items WHERE id = ? AND club_id = ?");
    $dpStmt->execute([$photoId, $club['id']]);
    header('Location: /admin/event-detail.php?id=' . urlencode($eventId) . '&msg=Photo+deleted');
    exit;
}

// Fetch event gallery items
$eventGalStmt = $db->prepare("SELECT * FROM gallery_items WHERE event_id = ? ORDER BY created_at DESC");
$eventGalStmt->execute([$eventId]);
$eventPhotos = $eventGalStmt->fetchAll();

// Format date for datetime-local input
$formattedDate = '';
if (!empty($event['event_date'])) {
    $formattedDate = date('Y-m-d\TH:i', strtotime($event['event_date']));
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Event - <?= htmlspecialchars($event['title']) ?> | ClubHub UIT</title>
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
                <a href="/admin/events.php" class="text-primary fw-bold text-decoration-none small d-inline-block mb-2">&larr; Back to Events List</a>
                <h2 class="fw-bold mb-1">Edit Event Details</h2>
                <p class="text-secondary small mb-0">Modify event schedule, location, banner poster, and agenda.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button type="submit" form="editEventForm" class="btn btn-primary rounded-pill px-4 py-2 fw-bold text-white shadow-sm">
                    <i class="bi bi-floppy me-1"></i> Save Changes
                </button>
                <a href="/admin/events.php?delete=<?= $event['id'] ?>" onclick="return confirm('Are you sure you want to delete this event?');" class="btn btn-outline-danger rounded-pill px-4 py-2 fw-bold">
                    <i class="bi bi-trash me-1"></i> Delete Event
                </a>
            </div>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <!-- Event Preview Card -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <img src="<?= htmlspecialchars($event['banner']) ?>" class="card-img-top" style="height: 200px; object-fit: cover;">
                    <div class="card-body p-4">
                        <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 mb-2 fw-bold text-uppercase" style="font-size: 0.7rem;"><?= htmlspecialchars($event['status']) ?></span>
                        <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($event['title']) ?></h5>
                        <p class="text-secondary small mb-3"><?= htmlspecialchars($event['description'] ?: 'No description provided.') ?></p>
                        
                        <hr class="my-3">
                        <div class="small text-muted space-y-2">
                            <div><i class="bi bi-geo-alt text-danger me-2"></i> <strong>Venue:</strong> <?= htmlspecialchars($event['venue']) ?></div>
                            <div><i class="bi bi-clock text-primary me-2"></i> <strong>Date:</strong> <?= date('d M Y, h:i A', strtotime($event['event_date'])) ?></div>
                            <div><i class="bi bi-link-45deg text-success me-2"></i> <strong>Registration:</strong> <a href="<?= htmlspecialchars($event['registration_link']) ?>" target="_blank" class="text-truncate d-inline-block align-middle" style="max-width: 150px;"><?= htmlspecialchars($event['registration_link']) ?></a></div>
                        </div>
                    </div>
                </div>

                <!-- Event Photo Gallery Manager Card -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4 bg-white p-4">
                    <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-images text-primary me-2"></i> Event Photo Gallery</h5>
                    <p class="text-secondary small mb-3">Upload recap photos from this event for students to view on event page.</p>

                    <!-- Upload Form -->
                    <form action="/admin/event-detail.php?id=<?= htmlspecialchars($event['id']) ?>" method="POST" enctype="multipart/form-data" class="mb-4">
                        <input type="hidden" name="action_upload_event_photo" value="1">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Select Photo (File Upload)</label>
                            <input type="file" name="photo_file" class="form-control form-control-sm rounded-3" accept="image/*">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Photo Caption / Title</label>
                            <input type="text" name="caption" class="form-control form-control-sm rounded-3" placeholder="e.g. Keynote Session / Prize Distribution">
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill w-100 py-2 fw-bold text-white shadow-xs">
                            <i class="bi bi-cloud-arrow-up me-1"></i> Upload Photo to Gallery
                        </button>
                    </form>

                    <!-- Uploaded Photos Grid -->
                    <h6 class="fw-bold small text-muted border-bottom pb-2 mb-3">Uploaded Photos (<?= count($eventPhotos) ?>)</h6>
                    <?php if (empty($eventPhotos)): ?>
                        <div class="text-center py-3 text-muted small bg-light rounded-3">No photos uploaded for this event yet.</div>
                    <?php else: ?>
                        <div class="row g-2">
                            <?php foreach ($eventPhotos as $photo): ?>
                                <div class="col-6">
                                    <div class="rounded-3 overflow-hidden border position-relative" style="height: 100px;">
                                        <img src="<?= htmlspecialchars($photo['media_url']) ?>" class="w-100 h-100 object-fit-cover">
                                        <a href="/admin/event-detail.php?id=<?= urlencode($event['id']) ?>&delete_photo=<?= urlencode($photo['id']) ?>" onclick="return confirm('Delete this photo?');" class="btn btn-sm btn-danger rounded-circle position-absolute top-0 end-0 m-1 p-1 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 0.7rem;" title="Delete Photo">
                                            <i class="bi bi-x-lg"></i>
                                        </a>
                                    </div>
                                    <span class="small text-muted d-block text-truncate mt-1" style="font-size: 0.7rem;"><?= htmlspecialchars($photo['caption'] ?: 'Photo') ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Edit Event Form -->
            <div class="col-lg-8">
                <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4">
                    <h5 class="fw-bold mb-4"><i class="bi bi-pencil-square text-primary me-2"></i> Event Information Editor</h5>
                    
                    <form action="/admin/event-detail.php?id=<?= htmlspecialchars($event['id']) ?>" method="POST" enctype="multipart/form-data" id="editEventForm">
                        <input type="hidden" name="action_update" value="1">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">Event Title *</label>
                                <input type="text" name="title" class="form-control rounded-3" value="<?= htmlspecialchars($event['title']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Event Category / Type *</label>
                                <select name="event_type" class="form-select rounded-3">
                                    <option value="Hands-on Workshop" <?= ($event['event_type'] ?? '') === 'Hands-on Workshop' ? 'selected' : '' ?>>🛠️ Hands-on Workshop</option>
                                    <option value="Competitive Hackathon" <?= ($event['event_type'] ?? '') === 'Competitive Hackathon' ? 'selected' : '' ?>>🏆 Competitive Hackathon</option>
                                    <option value="Tech Talk / Webinar" <?= ($event['event_type'] ?? '') === 'Tech Talk / Webinar' ? 'selected' : '' ?>>🎙️ Tech Talk / Webinar</option>
                                    <option value="Coding Contest" <?= ($event['event_type'] ?? '') === 'Coding Contest' ? 'selected' : '' ?>>💻 Coding Contest</option>
                                    <option value="Orientation Session" <?= ($event['event_type'] ?? '') === 'Orientation Session' ? 'selected' : '' ?>>🚀 Orientation Session</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-semibold">Event Subtitle / Tagline</label>
                                <input type="text" name="tagline" class="form-control rounded-3" value="<?= htmlspecialchars($event['tagline'] ?? '') ?>" placeholder="e.g. Winning Strategies & Solution Challenge Briefing 2026">
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Date & Time *</label>
                                <input type="datetime-local" name="event_date" class="form-control rounded-3" value="<?= htmlspecialchars($formattedDate) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Status *</label>
                                <select name="status" class="form-select rounded-3">
                                    <option value="upcoming" <?= $event['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming (Published)</option>
                                    <option value="ongoing" <?= $event['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing (Live Now)</option>
                                    <option value="completed" <?= $event['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="draft" <?= ($event['status'] === 'draft' || $event['status'] === 'drafted') ? 'selected' : '' ?>>Drafted (Private)</option>
                                    <option value="hidden" <?= ($event['status'] === 'hidden' || $event['status'] === 'private') ? 'selected' : '' ?>>Hidden (Private)</option>
                                    <option value="archived" <?= $event['status'] === 'archived' ? 'selected' : '' ?>>Archived</option>
                                    <option value="cancelled" <?= $event['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Venue / Location *</label>
                                <input type="text" name="venue" class="form-control rounded-3" value="<?= htmlspecialchars($event['venue']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Target Audience / Eligibility</label>
                                <input type="text" name="target_audience" class="form-control rounded-3" value="<?= htmlspecialchars($event['target_audience'] ?? 'All UIT Departments & Years') ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold"><i class="bi bi-upload text-primary me-1"></i> Upload New Banner Poster (From PC)</label>
                            <input type="file" name="banner_file" class="form-control rounded-3" accept="image/*">
                            <span class="form-text text-muted small">Upload PNG, JPG, or WEBP poster file from your computer to replace current poster.</span>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Key Speaker / Mentor Name</label>
                                <input type="text" name="speaker_name" class="form-control rounded-3" value="<?= htmlspecialchars($event['speaker_name'] ?? '') ?>" placeholder="e.g. Krishna Aute">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Speaker Designation / Achievement</label>
                                <input type="text" name="speaker_designation" class="form-control rounded-3" value="<?= htmlspecialchars($event['speaker_designation'] ?? '') ?>" placeholder="e.g. Global Solution Challenge Top 3 Winner">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Registration Form / Contact Link</label>
                            <input type="text" name="registration_link" class="form-control rounded-3" value="<?= htmlspecialchars($event['registration_link']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Full Event Description</label>
                            <textarea name="description" class="form-control rounded-3" rows="4"><?= htmlspecialchars($event['description']) ?></textarea>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Session Agenda Timeline</label>
                            <textarea name="agenda_timeline" class="form-control rounded-3" rows="3" placeholder="Phase 1: Registration&#10;Phase 2: Live Hands-on Coding&#10;Phase 3: Certificate Distribution"><?= htmlspecialchars($event['agenda_timeline'] ?? '') ?></textarea>
                        </div>

                        <!-- Mandatory Post-Event Documentation & Audit Section -->
                        <div class="p-4 bg-light rounded-4 border mb-4">
                            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-shield-check text-success me-2"></i> Post-Event Documentation & Audit (Dean Review)</h6>
                            <p class="text-muted small mb-3" style="font-size:0.75rem;">Required when updating event status to <strong>Completed</strong>. This data provides official documentation to college authorities for future event approvals.</p>

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Registered Participants</label>
                                    <input type="number" name="registered_count" class="form-control rounded-3" value="<?= (int)($event['registered_count'] ?? 0) ?>" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Actual Attendees Present</label>
                                    <input type="number" name="actual_attended" class="form-control rounded-3" value="<?= (int)($event['actual_attended'] ?? 0) ?>" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-semibold">Budget / Resource Utilized (₹)</label>
                                    <input type="number" step="0.01" name="budget_utilized" class="form-control rounded-3" value="<?= (float)($event['budget_utilized'] ?? 0.0) ?>" min="0">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-semibold">Outcomes, Swags & Key Highlights Summary</label>
                                    <textarea name="outcomes_summary" class="form-control rounded-3" rows="3" placeholder="Log key achievements, guest speakers, winner details, and outcomes for college administration records..."><?= htmlspecialchars($event['outcomes_summary'] ?? '') ?></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2-5 fw-bold text-white shadow-sm">
                                Save Changes & Audit Log
                            </button>
                            <a href="admin/events.php" class="btn btn-light rounded-pill px-4 py-2-5">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
