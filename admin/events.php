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
    $title              = trim($_POST['title'] ?? '');
    $tagline            = trim($_POST['tagline'] ?? '');
    $event_type         = trim($_POST['event_type'] ?? 'Workshop');
    $description        = trim($_POST['description'] ?? '');
    $venue              = trim($_POST['venue'] ?? '');
    $event_date         = $_POST['event_date'] ?? '';
    $reg_link           = trim($_POST['registration_link'] ?? 'contact.html');
    $status             = $_POST['status'] ?? 'upcoming';
    $outcomes_summary   = trim($_POST['outcomes_summary'] ?? '');
    $speaker_name       = trim($_POST['speaker_name'] ?? '');
    $speaker_designation= trim($_POST['speaker_designation'] ?? '');
    $agenda_timeline    = trim($_POST['agenda_timeline'] ?? '');
    $target_audience    = trim($_POST['target_audience'] ?? 'All Departments & Years');
    $bannerUrl          = trim($_POST['banner'] ?? 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=800&auto=format&fit=crop');

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
                INSERT INTO events (
                    id, club_id, title, tagline, slug, banner, description, venue, event_date, registration_link, status,
                    event_type, outcomes_summary, speaker_name, speaker_designation, agenda_timeline, target_audience
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtIns->execute([
                $eventId, $club['id'], $title, $tagline, $slug, $banner, $description, $venue, $event_date, $reg_link, $status,
                $event_type, $outcomes_summary, $speaker_name, $speaker_designation, $agenda_timeline, $target_audience
            ]);
            $success = "Advanced Event '$title' created and published successfully!";

            // Audit Log
            try {
                $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'] ?? '', $_SESSION['full_name'] ?? 'Club Lead', 'EVENT_CREATED', "Created event: $title for club: {$club['name']}"]);
            } catch (Exception $le) { /* Audit log failure is non-critical */ }
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
        header('Location: events.php?msg=Status+updated');
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
        header('Location: events.php?msg=Event+duplicated+as+draft');
        exit;
    }
}

// Handle Delete Event
if (isset($_GET['delete'])) {
    $delId = $_GET['delete'];
    $stmtDel = $db->prepare("DELETE FROM events WHERE id = ? AND club_id = ?");
    $stmtDel->execute([$delId, $club['id']]);
    header('Location: events.php?msg=Event+deleted+successfully');
    exit;
}

// Handle Bulk Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_bulk_events'])) {
    $bulkAction = $_POST['bulk_action'] ?? '';
    $selectedIds = $_POST['selected_events'] ?? [];

    if (!empty($selectedIds) && is_array($selectedIds)) {
        $placeholders = implode(',', array_fill(0, count($selectedIds), '?'));
        if ($bulkAction === 'delete') {
            $params = array_merge($selectedIds, [$club['id']]);
            $bStmt = $db->prepare("DELETE FROM events WHERE id IN ($placeholders) AND club_id = ?");
            $bStmt->execute($params);
            header('Location: events.php?msg=' . count($selectedIds) . '+events+deleted+successfully');
            exit;
        } elseif (in_array($bulkAction, ['upcoming', 'hidden', 'archived', 'draft', 'completed', 'cancelled'])) {
            $params = array_merge([$bulkAction], $selectedIds, [$club['id']]);
            $bStmt = $db->prepare("UPDATE events SET status = ? WHERE id IN ($placeholders) AND club_id = ?");
            $bStmt->execute($params);
            header('Location: events.php?msg=' . count($selectedIds) . '+events+updated+to+' . urlencode($bulkAction));
            exit;
        }
    }
}

// Fetch Events
$stmtEv = $db->prepare("SELECT * FROM events WHERE club_id = ? ORDER BY event_date DESC");
$stmtEv->execute([$club['id']]);
$events = $stmtEv->fetchAll();

// KPI Counts
$kpiTotal    = count($events);
$kpiUpcoming = count(array_filter($events, fn($e) => $e['status'] === 'upcoming'));
$kpiCompleted= count(array_filter($events, fn($e) => $e['status'] === 'completed'));
$kpiDraft    = count(array_filter($events, fn($e) => $e['status'] === 'draft'));
$kpiHidden   = count(array_filter($events, fn($e) => $e['status'] === 'hidden'));
$kpiArchived = count(array_filter($events, fn($e) => $e['status'] === 'archived'));
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
        .sticky-bulk-bar {
            position: sticky;
            top: 70px;
            z-index: 1020;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #fff;
            box-shadow: 0 10px 25px rgba(0,0,0,0.25);
            display: none;
        }
        .sticky-bulk-bar.active { display: flex; }
        .action-btn-pill {
            padding: 5px 12px;
            font-size: 0.78rem;
            font-weight: 600;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            transition: all 0.18s ease;
        }
        .action-btn-pill:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.12);
        }
    </style>
</head>
<body>

<div class="d-flex">
    <!-- Master Sidebar -->
    <?php require_once __DIR__ . '/../includes/club_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5">

        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">
                    <i class="bi bi-patch-check-fill me-1"></i><?= htmlspecialchars($club['name']) ?>
                </span>
                <h2 class="fw-bold mb-1 mt-2">Events & Hackathons Management</h2>
                <p class="text-secondary small mb-0">Create, schedule, hide/publish, and manage all chapter activities.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 py-2-5 fw-bold shadow-sm text-white d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createEventModal">
                <i class="bi bi-calendar-plus-fill fs-5"></i>
                <span>Create New Event</span>
            </button>
        </div>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($success) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_GET['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- KPI Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div><span class="text-secondary small fw-semibold d-block">TOTAL EVENTS</span><h3 class="fw-bold mb-0 text-dark"><?= $kpiTotal ?></h3></div>
                        <div class="rounded-3 p-2.5 bg-primary-subtle text-primary fs-4"><i class="bi bi-calendar-event-fill"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div><span class="text-secondary small fw-semibold d-block">UPCOMING</span><h3 class="fw-bold mb-0 text-success"><?= $kpiUpcoming ?></h3></div>
                        <div class="rounded-3 p-2.5 bg-success-subtle text-success fs-4"><i class="bi bi-clock-history"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div><span class="text-secondary small fw-semibold d-block">COMPLETED</span><h3 class="fw-bold mb-0 text-secondary"><?= $kpiCompleted ?></h3></div>
                        <div class="rounded-3 p-2.5 bg-secondary-subtle text-secondary fs-4"><i class="bi bi-check2-all"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div><span class="text-secondary small fw-semibold d-block">HIDDEN / DRAFTS</span><h3 class="fw-bold mb-0 text-warning"><?= $kpiHidden + $kpiDraft ?></h3></div>
                        <div class="rounded-3 p-2.5 bg-warning-subtle text-warning fs-4"><i class="bi bi-eye-slash-fill"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Search & Filters -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <div class="row g-3 align-items-center">
                <div class="col-md-6 col-lg-7">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-secondary"></i></span>
                        <input type="text" id="eventSearchInput" class="form-control border-start-0" placeholder="Search events by title, venue, or details...">
                    </div>
                </div>
                <div class="col-md-3 col-lg-3">
                    <select id="eventStatusFilter" class="form-select">
                        <option value="all">All Event Statuses</option>
                        <option value="upcoming">Upcoming Only</option>
                        <option value="ongoing">Ongoing Only</option>
                        <option value="completed">Completed Only</option>
                        <option value="hidden">Hidden / Private Only</option>
                        <option value="draft">Drafted Only</option>
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

        <!-- Sticky Bulk Operations Bar -->
        <form action="events.php" method="POST" id="bulkEventsForm">
            <input type="hidden" name="action_bulk_events" value="1">
            <input type="hidden" name="bulk_action" id="bulkEventsActionInput" value="">

            <div class="card rounded-4 p-3 mb-4 sticky-bulk-bar align-items-center justify-content-between" id="bulkEventsBar">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge bg-primary text-white rounded-pill px-3 py-2 fs-6" id="bulkEventCountBadge">0 Selected</span>
                    <span class="small text-white-50 d-none d-md-inline">Perform batch actions on selected event records.</span>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm btn-success rounded-pill px-3 fw-bold" onclick="submitBulkEventAction('upcoming')">
                        <i class="bi bi-eye me-1"></i> Publish Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold" onclick="submitBulkEventAction('hidden')">
                        <i class="bi bi-eye-slash me-1"></i> Hide Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3 fw-bold" onclick="submitBulkEventAction('archived')">
                        <i class="bi bi-archive me-1"></i> Archive Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-danger rounded-pill px-3 fw-bold" onclick="submitBulkEventAction('delete')">
                        <i class="bi bi-trash-fill me-1"></i> Delete Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-light rounded-circle" onclick="clearBulkEventsSelection()" title="Clear Selection">
                        ✕
                    </button>
                </div>
            </div>

            <!-- Events List Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <div class="d-flex align-items-center gap-2">
                        <input type="checkbox" class="form-check-input" id="selectAllEventsChk" onchange="toggleSelectAllEvents()">
                        <h6 class="fw-bold mb-0">Events Directory (<span id="eventCountBadge"><?= count($events) ?></span>)</h6>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="eventsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 40px;"></th>
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
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-calendar-x fs-1 d-block mb-2 text-primary"></i>
                                        No events created yet. Click <strong>"Create New Event"</strong> to publish your first workshop or competition!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($events as $ev): ?>
                                    <tr data-title="<?= e($ev['title']) ?>" data-venue="<?= e($ev['venue']) ?>" data-status="<?= e($ev['status']) ?>" data-date="<?= e($ev['event_date']) ?>">
                                        <td>
                                            <input type="checkbox" name="selected_events[]" value="<?= e($ev['id']) ?>" class="form-check-input event-chk" onchange="updateBulkEventsBarState()">
                                        </td>
                                        <td>
                                            <a href="event-detail.php?id=<?= e($ev['id']) ?>" class="text-decoration-none d-flex align-items-center gap-3">
                                                <img src="<?= e($ev['banner']) ?>" class="rounded-3 border flex-shrink-0" style="width: 58px; height: 42px; object-fit: cover;" onerror="this.src='https://images.unsplash.com/photo-1540575467063-178a50c2df87?q=80&w=300&auto=format&fit=crop'">
                                                <div>
                                                    <h6 class="fw-bold mb-0 text-dark hover-primary"><?= e($ev['title']) ?></h6>
                                                    <span class="small text-muted"><?= e(substr($ev['description'] ?? '', 0, 55)) ?>...</span>
                                                </div>
                                            </a>
                                        </td>
                                        <td><span class="text-dark fw-medium small"><i class="bi bi-geo-alt me-1 text-danger"></i> <?= e($ev['venue']) ?></span></td>
                                        <td><span class="small text-muted"><i class="bi bi-clock me-1"></i> <?= date('d M Y, h:i A', strtotime($ev['event_date'])) ?></span></td>
                                        <td>
                                            <?php if ($ev['status'] === 'upcoming'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill"><i class="bi bi-calendar-check me-1"></i>Upcoming</span>
                                            <?php elseif ($ev['status'] === 'ongoing'): ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-3 py-1 rounded-pill"><i class="bi bi-lightning-fill me-1"></i>Ongoing</span>
                                            <?php elseif ($ev['status'] === 'completed'): ?>
                                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-3 py-1 rounded-pill"><i class="bi bi-check2-all me-1"></i>Completed</span>
                                            <?php elseif ($ev['status'] === 'hidden'): ?>
                                                <span class="badge bg-dark-subtle text-dark border px-3 py-1 rounded-pill"><i class="bi bi-eye-slash-fill me-1"></i>Hidden (Private)</span>
                                            <?php elseif ($ev['status'] === 'archived'): ?>
                                                <span class="badge bg-info-subtle text-info border px-3 py-1 rounded-pill"><i class="bi bi-archive-fill me-1"></i>Archived</span>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark border px-3 py-1 rounded-pill"><i class="bi bi-file-earmark-lock me-1"></i><?= e(ucfirst($ev['status'])) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Action Pills Row -->
                                            <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                                <!-- Edit Event Details & Gallery -->
                                                <a href="event-detail.php?id=<?= e($ev['id']) ?>" class="btn btn-sm btn-primary action-btn-pill text-white" title="Edit Event Details & Photos">
                                                    <i class="bi bi-pencil-square"></i> Edit
                                                </a>

                                                <!-- Duplicate Event -->
                                                <a href="events.php?duplicate=<?= e($ev['id']) ?>" class="btn btn-sm btn-outline-info action-btn-pill" title="Duplicate Event as Draft">
                                                    <i class="bi bi-copy"></i> Duplicate
                                                </a>

                                                <!-- Hide / Publish Live Toggle -->
                                                <?php if ($ev['status'] === 'hidden'): ?>
                                                    <a href="events.php?set_status=upcoming&id=<?= e($ev['id']) ?>" class="btn btn-sm btn-success action-btn-pill text-white" title="Publish to Live Website">
                                                        <i class="bi bi-eye-fill"></i> Publish
                                                    </a>
                                                <?php else: ?>
                                                    <a href="events.php?set_status=hidden&id=<?= e($ev['id']) ?>" class="btn btn-sm btn-outline-secondary action-btn-pill" title="Hide from Live Website">
                                                        <i class="bi bi-eye-slash"></i> Hide
                                                    </a>
                                                <?php endif; ?>

                                                <!-- Archive Event -->
                                                <?php if ($ev['status'] !== 'archived'): ?>
                                                    <a href="events.php?set_status=archived&id=<?= e($ev['id']) ?>" class="btn btn-sm btn-outline-warning action-btn-pill" title="Archive Event Record">
                                                        <i class="bi bi-archive"></i> Archive
                                                    </a>
                                                <?php endif; ?>

                                                <!-- Delete Event -->
                                                <a href="events.php?delete=<?= e($ev['id']) ?>" onclick="return confirm('Are you sure you want to permanently delete this event?');" class="btn btn-sm btn-outline-danger action-btn-pill" title="Delete Event">
                                                    <i class="bi bi-trash"></i> Delete
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
        </form>

    </div>
</div>
    </div>
</div>

<!-- Modal: Advanced Create Event -->
<div class="modal fade" id="createEventModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow-2xl overflow-hidden">
            <div class="p-4 text-white" style="background: linear-gradient(135deg, #1e1b4b, #312e81, #1d4ed8) !important;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-white text-primary rounded-pill px-3 py-1 fw-bold mb-1 small text-uppercase">ADVANCED CREATOR PORTAL</span>
                        <h4 class="fw-bold text-white mb-0"><i class="bi bi-calendar-plus-fill me-2"></i> Publish New Campus Event</h4>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
            </div>

            <form action="events.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action_create" value="1">
                <div class="modal-body p-4 p-md-5 bg-light" style="max-height: 72vh; overflow-y: auto;">
                    
                    <!-- Section 1: Core Info -->
                    <div class="card border-0 shadow-xs rounded-4 p-4 mb-4 bg-white">
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-info-circle text-primary me-2"></i> 1. Core Event Info</h6>
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold text-dark">Event Title *</label>
                                <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Build with AI (Virtual Conference)" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold text-dark">Event Category / Type *</label>
                                <select name="event_type" class="form-select rounded-3">
                                    <option value="Hands-on Workshop" selected>🛠️ Hands-on Workshop</option>
                                    <option value="Competitive Hackathon">🏆 Competitive Hackathon</option>
                                    <option value="Tech Talk / Webinar">🎙️ Tech Talk / Webinar</option>
                                    <option value="Coding Contest">💻 Coding Contest</option>
                                    <option value="Orientation Session">🚀 Orientation Session</option>
                                </select>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Event Subtitle / Tagline</label>
                                <input type="text" name="tagline" class="form-control rounded-3" placeholder="e.g. Winning Strategies & Solution Challenge Briefing 2026">
                            </div>
                        </div>
                    </div>

                    <!-- Section 2: Schedule & Location -->
                    <div class="card border-0 shadow-xs rounded-4 p-4 mb-4 bg-white">
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-clock-history text-primary me-2"></i> 2. Schedule, Status & Venue</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Date & Time *</label>
                                <input type="datetime-local" name="event_date" class="form-control rounded-3" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Publication Status *</label>
                                <select name="status" class="form-select rounded-3">
                                    <option value="upcoming" selected>📅 Upcoming (Registration Open)</option>
                                    <option value="ongoing">🔴 Live Now (Ongoing Session)</option>
                                    <option value="completed">🏆 Completed (Past Session)</option>
                                    <option value="draft">🔒 Draft (Private)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Venue / Hall Location *</label>
                                <input type="text" name="venue" class="form-control rounded-3" placeholder="e.g. Induction Hall, 1st Floor, UIT / Virtual Bevy Stage" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Target Audience / Eligibility</label>
                                <input type="text" name="target_audience" class="form-control rounded-3" placeholder="e.g. Open to CSE, IT & All Departments (1st-4th Year)" value="All UIT Departments & Years">
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Cover Poster -->
                    <div class="card border-0 shadow-xs rounded-4 p-4 mb-4 bg-white">
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-image text-primary me-2"></i> 3. Cover Banner Poster</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Upload Poster (From PC) *</label>
                                <input type="file" name="banner_file" class="form-control rounded-3" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Or Image URL</label>
                                <input type="url" name="banner" class="form-control rounded-3" placeholder="https://images.unsplash.com/photo-...">
                            </div>
                        </div>
                    </div>

                    <!-- Section 4: Rewards & Speaker Info -->
                    <div class="card border-0 shadow-xs rounded-4 p-4 mb-4 bg-white">
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-award text-primary me-2"></i> 4. Swags, Rewards & Keynote Speaker</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Outcomes, Swags & Cash Prizes</label>
                                <input type="text" name="outcomes_summary" class="form-control rounded-3" placeholder="e.g. ₹15,000 Cash Prize, GFG Trophy, Laptop Bags & SAC Verified Certificates">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Key Speaker / Mentor Name</label>
                                <input type="text" name="speaker_name" class="form-control rounded-3" placeholder="e.g. Krishna Aute">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-dark">Speaker Designation / Achievement</label>
                                <input type="text" name="speaker_designation" class="form-control rounded-3" placeholder="e.g. Global Solution Challenge Top 3 Winner">
                            </div>
                        </div>
                    </div>

                    <!-- Section 5: Description & Phase Agenda -->
                    <div class="card border-0 shadow-xs rounded-4 p-4 bg-white">
                        <h6 class="fw-bold text-dark mb-3 border-bottom pb-2"><i class="bi bi-journal-text text-primary me-2"></i> 5. Detailed Writeup, Timeline & Registration</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Full Event Description</label>
                                <textarea name="description" class="form-control rounded-3" rows="4" placeholder="Comprehensive writeup of topics, prerequisites, key takeaways, and session highlights..."></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">Session Agenda Timeline</label>
                                <textarea name="agenda_timeline" class="form-control rounded-3" rows="3" placeholder="Phase 1 (08:30 PM): Registration & Keynote&#10;Phase 2 (08:45 PM): Live Coding Challenge&#10;Phase 3 (10:15 PM): Evaluation & Certificate Ceremony"></textarea>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-dark">External Registration / Contact Link</label>
                                <input type="text" name="registration_link" class="form-control rounded-3" value="contact.html">
                            </div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-white border-top p-3.5 d-flex align-items-center justify-content-between">
                    <button type="button" class="btn btn-light rounded-pill px-4 py-2 fw-semibold" data-bs-dismiss="modal">Discard</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-5 py-2.5 fw-bold text-white shadow-md" style="background: linear-gradient(135deg, #2563eb, #0284c7); border: none;">
                        <span>Publish Advanced Event →</span>
                    </button>
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
            tableBody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No events matching your search or filter criteria.</td></tr>`;
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

// Bulk Selection Functions for Events Table
function updateBulkEventsBarState() {
    const checked = document.querySelectorAll('.event-chk:checked');
    const bulkBar = document.getElementById('bulkEventsBar');
    const badge = document.getElementById('bulkEventCountBadge');

    if (checked.length > 0) {
        if (bulkBar) bulkBar.classList.add('active');
        if (badge) badge.textContent = checked.length + ' Selected';
    } else {
        if (bulkBar) bulkBar.classList.remove('active');
    }
}

function toggleSelectAllEvents() {
    const mainChk = document.getElementById('selectAllEventsChk');
    const chks = document.querySelectorAll('.event-chk');
    chks.forEach(c => c.checked = mainChk ? mainChk.checked : false);
    updateBulkEventsBarState();
}

function clearBulkEventsSelection() {
    document.querySelectorAll('.event-chk').forEach(c => c.checked = false);
    const mainChk = document.getElementById('selectAllEventsChk');
    if (mainChk) mainChk.checked = false;
    updateBulkEventsBarState();
}

function submitBulkEventAction(actionType) {
    const checked = document.querySelectorAll('.event-chk:checked');
    if (checked.length === 0) return;
    
    let confirmMsg = `Are you sure you want to apply '${actionType}' to ${checked.length} selected events?`;
    if (actionType === 'delete') confirmMsg = `Are you sure you want to PERMANENTLY DELETE ${checked.length} selected events? This cannot be undone.`;
    
    if (confirm(confirmMsg)) {
        document.getElementById('bulkEventsActionInput').value = actionType;
        document.getElementById('bulkEventsForm').submit();
    }
}
</script>
</body>
</html>
