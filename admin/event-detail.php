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
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $venue       = trim($_POST['venue'] ?? '');
    $event_date  = $_POST['event_date'] ?? '';
    $reg_link    = trim($_POST['registration_link'] ?? '/contact.html');
    $status      = $_POST['status'] ?? 'upcoming';
    $bannerUrl   = trim($_POST['banner'] ?? 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop');

    // Process uploaded image file if provided
    $uploadedBanner = upload_image_file($_FILES['banner_file'] ?? null, 'events', $event['banner'] ?? $bannerUrl);
    $banner = !empty($uploadedBanner) ? $uploadedBanner : (!empty($bannerUrl) ? $bannerUrl : $event['banner']);

    if (empty($title) || empty($venue) || empty($event_date)) {
        $error = "Title, venue, and date are required.";
    } else {
        try {
            $uStmt = $db->prepare("
                UPDATE events SET 
                    title = ?, description = ?, venue = ?, event_date = ?, registration_link = ?, status = ?, banner = ?
                WHERE id = ? AND club_id = ?
            ");
            $uStmt->execute([$title, $description, $venue, $event_date, $reg_link, $status, $banner, $eventId, $club['id']]);
            $success = "Event details updated successfully!";
            
            // Refresh event data
            $evtStmt->execute([$eventId, $club['id']]);
            $event = $evtStmt->fetch();
        } catch (Exception $e) {
            $error = "Failed to update event: " . $e->getMessage();
        }
    }
}

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
            <a href="/admin/events.php?delete=<?= $event['id'] ?>" onclick="return confirm('Are you sure you want to delete this event?');" class="btn btn-outline-danger rounded-pill px-4 py-2 fw-bold">
                <i class="bi bi-trash me-1"></i> Delete Event
            </a>
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
            </div>

            <!-- Edit Event Form -->
            <div class="col-lg-8">
                <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4">
                    <h5 class="fw-bold mb-4"><i class="bi bi-pencil-square text-primary me-2"></i> Event Information Editor</h5>
                    
                    <form action="/admin/event-detail.php?id=<?= htmlspecialchars($event['id']) ?>" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action_update" value="1">
                        
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Event Title *</label>
                            <input type="text" name="title" class="form-control rounded-3" value="<?= htmlspecialchars($event['title']) ?>" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Date & Time *</label>
                                <input type="datetime-local" name="event_date" class="form-control rounded-3" value="<?= htmlspecialchars($formattedDate) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Status *</label>
                                <select name="status" class="form-select rounded-3">
                                    <option value="upcoming" <?= $event['status'] === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                                    <option value="completed" <?= $event['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                                    <option value="ongoing" <?= $event['status'] === 'ongoing' ? 'selected' : '' ?>>Ongoing</option>
                                    <option value="cancelled" <?= $event['status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Venue / Location *</label>
                            <input type="text" name="venue" class="form-control rounded-3" value="<?= htmlspecialchars($event['venue']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold"><i class="bi bi-upload text-primary me-1"></i> Upload New Banner Poster (From PC)</label>
                            <input type="file" name="banner_file" class="form-control rounded-3" accept="image/*">
                            <span class="form-text text-muted small">Upload PNG, JPG, or WEBP poster file from your computer.</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Or Image URL</label>
                            <input type="url" name="banner" class="form-control rounded-3" value="<?= htmlspecialchars($event['banner']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Registration Form Link</label>
                            <input type="text" name="registration_link" class="form-control rounded-3" value="<?= htmlspecialchars($event['registration_link']) ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-semibold">Event Description & Agenda</label>
                            <textarea name="description" class="form-control rounded-3" rows="4"><?= htmlspecialchars($event['description']) ?></textarea>
                        </div>

                        <div class="d-flex gap-3">
                            <button type="submit" class="btn btn-primary rounded-pill px-5 py-2-5 fw-bold text-white shadow-sm">
                                Save Changes
                            </button>
                            <a href="/admin/events.php" class="btn btn-light rounded-pill px-4 py-2-5">Cancel</a>
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
