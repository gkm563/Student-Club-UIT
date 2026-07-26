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

// Handle Quick Status Change (Hide, Archive, Draft, Publish)
if (isset($_GET['set_status']) && isset($_GET['id'])) {
    $evtId = $_GET['id'];
    $newStatus = $_GET['set_status'];
    $allowedStatuses = ['upcoming', 'ongoing', 'completed', 'cancelled', 'draft', 'hidden', 'archived'];
    if (in_array($newStatus, $allowedStatuses)) {
        $stmtStatus = $db->prepare("UPDATE events SET status = ? WHERE id = ? AND club_id = ?");
        $stmtStatus->execute([$newStatus, $evtId, $club['id']]);
        header('Location: /admin/events.php?msg=Status+updated');
        exit;
    }
}

// Handle Duplicate Event
if (isset($_GET['duplicate']) && !empty($_GET['duplicate'])) {
    $dupId = $_GET['duplicate'];
    $origStmt = $db->prepare("SELECT * FROM events WHERE id = ? AND club_id = ?");
    $origStmt->execute([$dupId, $club['id']]);
    $orig = $origStmt->fetch();

    if ($orig) {
        $newId = 'evt_' . bin2hex(random_bytes(4));
        $newTitle = $orig['title'] . ' (Copy)';
        $newSlug = slugify($newTitle) . '-' . rand(100, 999);
        $insDup = $db->prepare("
            INSERT INTO events (id, club_id, title, slug, banner, description, venue, event_date, registration_link, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
        ");
        $insDup->execute([$newId, $club['id'], $newTitle, $newSlug, $orig['banner'], $orig['description'], $orig['venue'], $orig['event_date'], $orig['registration_link']]);
        header('Location: /admin/events.php?msg=Event+duplicated+as+draft');
        exit;
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
                        <option value="draft">Drafted Only</option>
                        <option value="hidden">Hidden / Private Only</option>
                        <option value="archived">Archived Only</option>
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
                            <th style="cursor: pointer;" class="sortable-header" data-sort-key="title">
                                Event Details <i class="bi bi-arrow-down-up text-muted ms-1 small"></i>
                            </th>
                            <th style="cursor: pointer;" class="sortable-header" data-sort-key="venue">
                                Venue / Location <i class="bi bi-arrow-down-up text-muted ms-1 small"></i>
                            </th>
                            <th style="cursor: pointer;" class="sortable-header" data-sort-key="date">
                                Date & Time <i class="bi bi-arrow-down-up text-muted ms-1 small"></i>
                            </th>
                            <th style="cursor: pointer;" class="sortable-header" data-sort-key="status">
                                Status <i class="bi bi-arrow-down-up text-muted ms-1 small"></i>
                            </th>
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
                                            <!-- Frequently Used Action 1: Edit Details -->
                                            <a href="/admin/event-detail.php?id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-bold" title="Edit Event">
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </a>

                                            <!-- Frequently Used Action 2: Quick Hide / Publish Toggle -->
                                            <?php if ($ev['status'] === 'hidden'): ?>
                                                <a href="/admin/events.php?set_status=upcoming&id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill px-2-5 py-1 fw-semibold" title="Publish / Make Public">
                                                    <i class="bi bi-eye me-1"></i> Publish
                                                </a>
                                            <?php else: ?>
                                                <a href="/admin/events.php?set_status=hidden&id=<?= $ev['id'] ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-2-5 py-1 fw-semibold" title="Hide / Make Private">
                                                    <i class="bi bi-eye-slash me-1"></i> Hide
                                                </a>
                                            <?php endif; ?>

                                            <!-- Rarely Used Actions: Triple Dot Dropdown -->
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light border rounded-circle d-flex align-items-center justify-content-center p-1" style="width: 32px; height: 32px;" type="button" data-bs-toggle="dropdown" title="More Actions">
                                                    <i class="bi bi-three-dots-vertical fs-6 text-secondary"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end rounded-3 shadow border-0 small">
                                                    <li>
                                                        <a class="dropdown-item py-1-5" href="/admin/events.php?duplicate=<?= $ev['id'] ?>">
                                                            <i class="bi bi-copy text-info me-2"></i> Duplicate Event
                                                        </a>
                                                    </li>
                                                    <?php if ($ev['status'] !== 'draft'): ?>
                                                        <li>
                                                            <a class="dropdown-item py-1-5" href="/admin/events.php?set_status=draft&id=<?= $ev['id'] ?>">
                                                                <i class="bi bi-file-earmark-lock text-secondary me-2"></i> Save as Draft
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if ($ev['status'] !== 'archived'): ?>
                                                        <li>
                                                            <a class="dropdown-item py-1-5" href="/admin/events.php?set_status=archived&id=<?= $ev['id'] ?>">
                                                                <i class="bi bi-archive text-warning me-2"></i> Archive Event
                                                            </a>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item py-1-5 text-danger" href="/admin/events.php?delete=<?= $ev['id'] ?>" onclick="return confirm('Permanently delete this event?');">
                                                            <i class="bi bi-trash me-2"></i> Delete Event
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
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
                        <label class="form-label small fw-semibold"><i class="bi bi-upload text-primary me-1"></i> Upload Banner Image (From PC) *</label>
                        <input type="file" name="banner_file" class="form-control rounded-3" accept="image/*" required>
                        <span class="form-text text-muted small">Select a PNG, JPG, or WEBP poster from your computer.</span>
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
    const headers = document.querySelectorAll('.sortable-header');
    
    if (!tableBody) return;
    const rows = Array.from(tableBody.querySelectorAll('tr[data-title]'));
    let currentColumnSort = { key: 'date', dir: 'desc' };

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
            let valA = a.dataset[currentColumnSort.key] || '';
            let valB = b.dataset[currentColumnSort.key] || '';

            if (currentColumnSort.key === 'date') {
                return currentColumnSort.dir === 'desc' 
                    ? valB.localeCompare(valA) 
                    : valA.localeCompare(valB);
            } else {
                return currentColumnSort.dir === 'asc' 
                    ? valA.localeCompare(valB) 
                    : valB.localeCompare(valA);
            }
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

    // Column header click listeners
    headers.forEach(header => {
        header.addEventListener('click', () => {
            const key = header.dataset.sortKey;
            if (currentColumnSort.key === key) {
                currentColumnSort.dir = currentColumnSort.dir === 'asc' ? 'desc' : 'asc';
            } else {
                currentColumnSort.key = key;
                currentColumnSort.dir = 'asc';
            }

            // Update icons
            headers.forEach(h => {
                const icon = h.querySelector('i');
                if (icon) icon.className = 'bi bi-arrow-down-up text-muted ms-1 small';
            });
            const activeIcon = header.querySelector('i');
            if (activeIcon) {
                activeIcon.className = currentColumnSort.dir === 'asc' ? 'bi bi-sort-alpha-down text-primary ms-1 fw-bold' : 'bi bi-sort-alpha-down-alt text-primary ms-1 fw-bold';
            }

            filterAndSort();
        });
    });

    searchInput?.addEventListener('input', filterAndSort);
    statusFilter?.addEventListener('change', filterAndSort);
    sortOrder?.addEventListener('change', (e) => {
        if (e.target.value === 'date-desc') { currentColumnSort = { key: 'date', dir: 'desc' }; }
        else if (e.target.value === 'date-asc') { currentColumnSort = { key: 'date', dir: 'asc' }; }
        else if (e.target.value === 'title-asc') { currentColumnSort = { key: 'title', dir: 'asc' }; }
        filterAndSort();
    });
});
</script>
</body>
</html>
