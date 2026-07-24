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

// Handle Create Event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_create'])) {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $venue       = trim($_POST['venue'] ?? '');
    $event_date  = $_POST['event_date'] ?? '';
    $reg_link    = trim($_POST['registration_link'] ?? '/contact.html');
    $status      = $_POST['status'] ?? 'upcoming';
    $bannerUrl   = trim($_POST['banner'] ?? 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop');

    // Process file upload if provided
    $uploadedBanner = upload_image_file($_FILES['banner_file'] ?? null, 'events', $bannerUrl);
    $banner = $uploadedBanner ?: $bannerUrl;

    if (empty($title) || empty($venue) || empty($event_date)) {
        $error = "Title, venue, and date are required.";
    } else {
        try {
            $eventId = 'evt_' . bin2hex(random_bytes(4));
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $title)) . '-' . rand(100, 999);
            
            $stmtIns = $db->prepare("
                INSERT INTO events (id, club_id, title, slug, banner, description, venue, event_date, registration_link, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtIns->execute([$eventId, $club['id'], $title, $slug, $banner, $description, $venue, $event_date, $reg_link, $status]);
            $success = "Event '$title' published successfully!";
        } catch (Exception $e) {
            $error = "Failed to create event: " . $e->getMessage();
        }
    }
}

// Handle Delete Event
if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM events WHERE id = ? AND club_id = ?");
    $stmtDel->execute([$delId, $club['id']]);
    header('Location: /admin/events.php?msg=Event+deleted');
    exit;
}

// Fetch Events
$stmtEv = $db->prepare("SELECT * FROM events WHERE club_id = ? ORDER BY event_date DESC");
$stmtEv->execute([$club['id']]);
$events = $stmtEv->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events | ClubHub UIT</title>
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
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small"><?= htmlspecialchars($club['name']) ?></span>
                <h2 class="fw-bold mb-1">Manage Events & Workshops</h2>
                <p class="text-secondary small mb-0">Create, schedule, and manage events organized by your club.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#createEventModal">
                <i class="bi bi-plus-lg me-1"></i> Create Event
            </button>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Events Search & Sorting Controls -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <div class="row g-3 align-items-center">
                <div class="col-md-6 col-lg-7">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" id="eventSearchInput" class="form-control border-start-0" placeholder="Search events by title, venue, or description...">
                    </div>
                </div>
                <div class="col-md-3 col-lg-3">
                    <select id="eventStatusFilter" class="form-select">
                        <option value="all">All Event Statuses</option>
                        <option value="upcoming">Upcoming Only</option>
                        <option value="ongoing">Ongoing Only</option>
                        <option value="completed">Completed Only</option>
                    </select>
                </div>
                <div class="col-md-3 col-lg-2">
                    <select id="eventSortOrder" class="form-select">
                        <option value="date-desc">Newest First</option>
                        <option value="date-asc">Oldest First</option>
                        <option value="title-asc">Title: A &rarr; Z</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Events List Table -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0">Published Club Events (<span id="eventCountBadge"><?= count($events) ?></span>)</h6>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="eventsTable">
                    <thead class="table-light">
                        <tr>
                            <th>Event Details</th>
                            <th>Venue / Location</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">
                                    <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                                    No events created yet. Click "Create Event" to publish your first workshop or competition!
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $ev): ?>
                                <tr data-title="<?= e($ev['title']) ?>" data-venue="<?= e($ev['venue']) ?>" data-status="<?= e($ev['status']) ?>" data-date="<?= e($ev['event_date']) ?>">
                                    <td>
                                        <a href="/admin/event-detail.php?id=<?= $ev['id'] ?>" class="text-decoration-none d-flex align-items-center gap-3">
                                            <img src="<?= htmlspecialchars($ev['banner']) ?>" class="rounded-3 border" style="width: 54px; height: 38px; object-fit: cover;">
                                            <div>
                                                <h6 class="fw-bold mb-0 text-dark hover-primary"><?= htmlspecialchars($ev['title']) ?></h6>
                                                <span class="small text-muted"><?= htmlspecialchars(substr($ev['description'] ?? '', 0, 50)) ?>...</span>
                                            </div>
                                        </a>
                                    </td>
                                    <td><span class="text-dark fw-medium"><i class="bi bi-geo-alt me-1 text-danger"></i> <?= htmlspecialchars($ev['venue']) ?></span></td>
                                    <td><span class="small text-muted"><i class="bi bi-clock me-1"></i> <?= date('d M Y, h:i A', strtotime($ev['event_date'])) ?></span></td>
                                    <td>
                                        <?php if ($ev['status'] === 'upcoming'): ?>
                                            <span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1">Upcoming</span>
                                        <?php elseif ($ev['status'] === 'completed'): ?>
                                            <span class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-1">Completed</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border rounded-pill px-3 py-1"><?= ucfirst($ev['status']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="/admin/event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold" title="Edit Event">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </a>
                                            <a href="/admin/events.php?delete=<?= $ev['id'] ?>" onclick="return confirm('Are you sure you want to delete this event?');" class="btn btn-sm btn-outline-danger rounded-circle p-1" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Create Event -->
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold modal-title"><i class="bi bi-calendar-plus text-primary me-2"></i> Create New Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="/admin/events.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action_create" value="1">
                <div class="modal-body space-y-3">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Event Title *</label>
                        <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Google Cloud Study Jam 2026" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Date & Time *</label>
                            <input type="datetime-local" name="event_date" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status *</label>
                            <select name="status" class="form-select rounded-3">
                                <option value="upcoming" selected>Upcoming</option>
                                <option value="completed">Completed</option>
                                <option value="ongoing">Ongoing</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Venue / Location *</label>
                        <input type="text" name="venue" class="form-control rounded-3" placeholder="e.g. Auditorium Hall A, UIT" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><i class="bi bi-upload text-primary me-1"></i> Upload Banner Image (From PC)</label>
                        <input type="file" name="banner_file" class="form-control rounded-3" accept="image/*">
                        <span class="form-text text-muted small">Select a PNG, JPG, or WEBP poster from your computer.</span>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Or Image URL</label>
                        <input type="url" name="banner" class="form-control rounded-3" value="https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Registration Link</label>
                        <input type="text" name="registration_link" class="form-control rounded-3" value="/contact.html">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Event Description</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Brief event summary and agenda..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Publish Event</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('eventSearchInput');
    const statusFilter = document.getElementById('eventStatusFilter');
    const sortOrder = document.getElementById('eventSortOrder');
    const tableBody = document.querySelector('#eventsTable tbody');
    const countBadge = document.getElementById('eventCountBadge');
    
    if (!tableBody) return;
    const rows = Array.from(tableBody.querySelectorAll('tr[data-title]'));

    function filterAndSort() {
        const query = (searchInput.value || '').toLowerCase().trim();
        const selectedStatus = statusFilter.value;
        const selectedSort = sortOrder.value;

        let visibleRows = rows.filter(row => {
            const title = (row.dataset.title || '').toLowerCase();
            const venue = (row.dataset.venue || '').toLowerCase();
            const status = (row.dataset.status || '').toLowerCase();

            const matchesQuery = !query || title.includes(query) || venue.includes(query);
            const matchesStatus = (selectedStatus === 'all') || (status === selectedStatus);

            return matchesQuery && matchesStatus;
        });

        // Sort visible rows
        visibleRows.sort((a, b) => {
            if (selectedSort === 'date-desc') {
                return (b.dataset.date || '').localeCompare(a.dataset.date || '');
            } else if (selectedSort === 'date-asc') {
                return (a.dataset.date || '').localeCompare(b.dataset.date || '');
            } else if (selectedSort === 'title-asc') {
                return (a.dataset.title || '').localeCompare(b.dataset.title || '');
            }
            return 0;
        });

        // Re-append sorted rows
        tableBody.innerHTML = '';
        if (visibleRows.length === 0) {
            tableBody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No events matching your search or filter.</td></tr>`;
        } else {
            visibleRows.forEach(row => tableBody.appendChild(row));
        }

        if (countBadge) countBadge.textContent = visibleRows.length;
    }

    searchInput?.addEventListener('input', filterAndSort);
    statusFilter?.addEventListener('change', filterAndSort);
    sortOrder?.addEventListener('change', filterAndSort);
});
</script>
</body>
</html>
