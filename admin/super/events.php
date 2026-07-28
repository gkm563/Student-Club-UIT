<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../dean-login.php');
    exit;
}

$db = Database::getConnection();
$message = '';
$error = '';

// Handle Event Status Updates (Approve, Cancel, Flag)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_event_status') {
    $eventId = trim($_POST['event_id'] ?? '');
    $newStatus = trim($_POST['status'] ?? 'upcoming');

    if (!empty($eventId)) {
        try {
            $stmt = $db->prepare("UPDATE events SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $eventId]);

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'EVENT_STATUS_UPDATED', 'event', $eventId, "Set event ID '$eventId' status to '$newStatus'");
            $message = "Event status updated to " . strtoupper($newStatus) . " successfully!";
        } catch (Exception $e) {
            $error = "Error updating event status: " . $e->getMessage();
        }
    }
}

// Handle Event Deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_event') {
    $eventId = trim($_POST['event_id'] ?? '');
    if (!empty($eventId)) {
        try {
            $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$eventId]);

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'EVENT_DELETED', 'event', $eventId, "Deleted event ID '$eventId'");
            $message = "Event record deleted successfully!";
        } catch (Exception $e) {
            $error = "Error deleting event: " . $e->getMessage();
        }
    }
}

// Fetch Filters Data
$allClubs = $db->query("SELECT id, name, short_name, logo FROM clubs ORDER BY name ASC")->fetchAll();
$allCategories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Main Events Query
$sql = "
    SELECT e.*, c.name as club_name, c.short_name as club_short, c.logo as club_logo, cat.name as category_name,
           u.full_name as lead_name, u.email as lead_email
    FROM events e
    JOIN clubs c ON e.club_id = c.id
    JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN club_admins ca ON ca.club_id = c.id
    LEFT JOIN users u ON ca.user_id = u.id
    ORDER BY 
        CASE 
            WHEN LOWER(e.status) IN ('ongoing', 'live') THEN 1
            WHEN LOWER(e.status) = 'upcoming' OR e.event_date >= NOW() THEN 2
            ELSE 3
        END ASC,
        CASE 
            WHEN LOWER(e.status) = 'upcoming' OR e.event_date >= NOW() THEN e.event_date
        END ASC,
        e.event_date DESC
";
$events = $db->query($sql)->fetchAll();

// Analytics KPIs
$totalEventsCount = count($events);
$totalAttendees = (int)$db->query("SELECT SUM(actual_attended) FROM events")->fetchColumn();
$totalBudget = (float)$db->query("SELECT SUM(budget_utilized) FROM events")->fetchColumn();
$upcomingCount = (int)$db->query("SELECT COUNT(*) FROM events WHERE event_date >= NOW() AND status != 'cancelled'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Governance & Decision Center | Dean Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #1e293b; }
        .stat-chip-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.2s ease;
        }
        .stat-chip-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
        }
        .chip-icon-box {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">
    <!-- Universal Super Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">
        
        <!-- Header Banner -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">INSTITUTIONAL GOVERNANCE</span>
                <h2 class="fw-bold mb-1 text-dark mt-2">Campus Events & Activity Governance</h2>
                <p class="text-secondary small mb-0">Executive oversight of all student chapter workshops, hackathons, guest lectures, attendance & budget utilization.</p>
            </div>
            <a href="proposals.php" class="btn btn-warning text-dark rounded-pill px-4 py-2.5 fw-bold shadow-sm">
                <i class="bi bi-hourglass-split me-1"></i> View Event Proposals
            </a>
        </div>

        <!-- Alert Feedback Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- Stat Deck Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-primary-subtle text-primary"><i class="bi bi-calendar-event-fill"></i></div>
                    <div>
                        <div class="fw-bold text-dark lh-1" style="font-size:1.35rem;"><?= $totalEventsCount ?></div>
                        <div class="text-secondary small fw-semibold mt-1" style="font-size:0.75rem;">Total Events Conducted</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-success-subtle text-success"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="fw-bold text-dark lh-1" style="font-size:1.35rem;"><?= number_format($totalAttendees) ?></div>
                        <div class="text-secondary small fw-semibold mt-1" style="font-size:0.75rem;">Student Attendees</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-purple-subtle text-purple" style="background:#f5f3ff; color:#7c3aed;"><i class="bi bi-currency-rupee"></i></div>
                    <div>
                        <div class="fw-bold text-dark lh-1" style="font-size:1.35rem;">₹<?= number_format($totalBudget) ?></div>
                        <div class="text-secondary small fw-semibold mt-1" style="font-size:0.75rem;">Budget Utilized</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-warning-subtle text-warning"><i class="bi bi-clock-history"></i></div>
                    <div>
                        <div class="fw-bold text-dark lh-1" style="font-size:1.35rem;"><?= $upcomingCount ?></div>
                        <div class="text-secondary small fw-semibold mt-1" style="font-size:0.75rem;">Upcoming Events</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <div class="row g-3 align-items-center">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="eventSearchInput" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Search event title, venue, speaker...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="eventClubFilter" class="form-select rounded-pill">
                        <option value="">All Student Chapters</option>
                        <?php foreach ($allClubs as $ac): ?>
                            <option value="<?= htmlspecialchars($ac['name']) ?>"><?= htmlspecialchars($ac['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="eventStatusFilter" class="form-select rounded-pill">
                        <option value="">All Statuses</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="completed">Completed</option>
                        <option value="ongoing">Ongoing</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2 text-end">
                    <button class="btn btn-outline-secondary rounded-pill w-100 fw-bold" onclick="resetEventFilters()"><i class="bi bi-arrow-counterclockwise me-1"></i> Reset</button>
                </div>
            </div>
        </div>

        <!-- Events Master Directory Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-event text-primary me-2"></i>Campus Events Governance Directory</h6>
                <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 font-monospace" id="eventCountBadge"><?= count($events) ?> Events Found</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="eventsGovernanceTable">
                    <thead class="table-light">
                        <tr class="small text-secondary">
                            <th>EVENT DETAILS</th>
                            <th>HOSTING CHAPTER</th>
                            <th>DATE & VENUE</th>
                            <th>CHIEF GUEST / SPEAKER</th>
                            <th>ATTENDANCE & BUDGET</th>
                            <th>STATUS</th>
                            <th class="text-end">EXECUTIVE ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No campus events recorded yet.</td>
                            </tr>
                        <?php else: foreach ($events as $ev): ?>
                            <tr>
                                <td>
                                    <a href="javascript:void(0)" class="fw-bold text-dark text-decoration-none hover-primary mb-0 d-block" data-bs-toggle="modal" data-bs-target="#eventAuditModal_<?= $ev['id'] ?>" title="Click to view full event details & dossier">
                                        <?= htmlspecialchars($ev['title']) ?> <i class="bi bi-info-circle text-primary fs-7 ms-1"></i>
                                    </a>
                                    <span class="badge bg-light text-secondary border rounded-pill px-2 py-0.5 mt-1" style="font-size:0.68rem;"><?= htmlspecialchars($ev['event_type'] ?? 'General Event') ?></span>
                                </td>
                                <td>
                                    <a href="club-detail.php?id=<?= $ev['club_id'] ?>" class="text-decoration-none d-inline-flex align-items-center gap-2">
                                        <img src="<?= htmlspecialchars($ev['club_logo'] ?: '../../assets/United Logo.webp') ?>" class="rounded-2 border" style="width:24px;height:24px;object-fit:cover;" alt="">
                                        <span class="fw-bold text-primary small"><?= htmlspecialchars($ev['club_short'] ?: $ev['club_name']) ?></span>
                                    </a>
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
                                    $bClass = ($st === 'completed') ? 'bg-success-subtle text-success' : (($st === 'upcoming') ? 'bg-primary-subtle text-primary' : (($st === 'ongoing') ? 'bg-warning-subtle text-warning' : 'bg-danger-subtle text-danger'));
                                    ?>
                                    <span class="badge <?= $bClass ?> border rounded-pill px-2.5 py-1 small"><?= ucfirst($st) ?></span>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex align-items-center justify-content-end gap-1">
                                        <!-- Direct Quick Decision: Mark Completed / Conducted -->
                                        <?php if ($ev['status'] !== 'completed'): ?>
                                            <form action="events.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="update_event_status">
                                                <input type="hidden" name="event_id" value="<?= htmlspecialchars($ev['id']) ?>">
                                                <input type="hidden" name="status" value="completed">
                                                <button type="submit" class="btn btn-sm btn-success rounded-circle shadow-xs" title="Approve & Mark Completed">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Direct Quick Decision: Set Upcoming -->
                                        <?php if ($ev['status'] !== 'upcoming'): ?>
                                            <form action="events.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="update_event_status">
                                                <input type="hidden" name="event_id" value="<?= htmlspecialchars($ev['id']) ?>">
                                                <input type="hidden" name="status" value="upcoming">
                                                <button type="submit" class="btn btn-sm btn-primary rounded-circle shadow-xs" title="Schedule as Upcoming Event">
                                                    <i class="bi bi-calendar-check-fill"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Direct Quick Decision: Cancel / Flag Event -->
                                        <?php if ($ev['status'] !== 'cancelled'): ?>
                                            <form action="events.php" method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="update_event_status">
                                                <input type="hidden" name="event_id" value="<?= htmlspecialchars($ev['id']) ?>">
                                                <input type="hidden" name="status" value="cancelled">
                                                <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle shadow-xs" title="Flag & Cancel Event" onclick="return confirm('Cancel this event?')">
                                                    <i class="bi bi-slash-circle-fill"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Edit Settings Modal Button -->
                                        <button type="button" class="btn btn-sm btn-light rounded-circle shadow-xs" data-bs-toggle="modal" data-bs-target="#editEventStatusModal_<?= $ev['id'] ?>" title="Change Event Status Settings">
                                            <i class="bi bi-gear-fill text-secondary"></i>
                                        </button>

                                        <!-- Direct Delete Event Record -->
                                        <form action="events.php" method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="delete_event">
                                            <input type="hidden" name="event_id" value="<?= htmlspecialchars($ev['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-light text-danger rounded-circle shadow-xs" title="Permanently Delete Event Record" onclick="return confirm('Permanently delete this event record?')">
                                                <i class="bi bi-trash-fill"></i>
                                            </button>
                                        </form>
                                    </div>

                                    <!-- Event Deep Audit Modal -->
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
                                                                <span class="text-secondary d-block small font-monospace">ORGANIZING CHAPTER</span>
                                                                <span class="fw-bold text-dark fs-6"><?= htmlspecialchars($ev['club_name']) ?></span>
                                                                <div class="small text-muted mt-1">Lead: <strong><?= htmlspecialchars($ev['lead_name'] ?: 'Club President') ?></strong> (<?= htmlspecialchars($ev['lead_email'] ?: 'N/A') ?>)</div>
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

                                    <!-- Edit Event Status Modal -->
                                    <div class="modal fade text-start" id="editEventStatusModal_<?= $ev['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-0 pb-0 p-4">
                                                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-gear-fill text-primary me-2"></i> Update Event Status</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="events.php" method="POST">
                                                    <input type="hidden" name="action" value="update_event_status">
                                                    <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                                    <div class="modal-body p-4">
                                                        <label class="form-label small fw-semibold">Event Status</label>
                                                        <select name="status" class="form-select rounded-3">
                                                            <option value="upcoming" <?= strtolower($ev['status']) === 'upcoming' ? 'selected' : '' ?>>Upcoming (Scheduled)</option>
                                                            <option value="ongoing" <?= strtolower($ev['status']) === 'ongoing' ? 'selected' : '' ?>>Ongoing (Live Now)</option>
                                                            <option value="completed" <?= strtolower($ev['status']) === 'completed' ? 'selected' : '' ?>>Completed (Finished)</option>
                                                            <option value="cancelled" <?= strtolower($ev['status']) === 'cancelled' ? 'selected' : '' ?>>Cancelled (Flagged / Blocked)</option>
                                                        </select>
                                                    </div>
                                                    <div class="modal-footer border-0 p-4 pt-0">
                                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Save Status</button>
                                                    </div>
                                                </form>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('eventSearchInput');
    const clubFilter = document.getElementById('eventClubFilter');
    const statusFilter = document.getElementById('eventStatusFilter');
    const tableRows = document.querySelectorAll('#eventsGovernanceTable tbody tr');

    function filterEvents() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
        const clubVal = clubFilter ? clubFilter.value.toLowerCase() : '';
        const statusVal = statusFilter ? statusFilter.value.toLowerCase() : '';
        let visibleCount = 0;

        tableRows.forEach(row => {
            const text = row.innerText.toLowerCase();
            const matchesQuery = !query || text.includes(query);
            const matchesClub = !clubVal || text.includes(clubClubVal);
            const matchesStatus = !statusVal || text.includes(statusVal);

            if (matchesQuery && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        const badge = document.getElementById('eventCountBadge');
        if (badge) badge.innerText = `${visibleCount} Events Found`;
    }

    if (searchInput) searchInput.addEventListener('keyup', filterEvents);
    if (clubFilter) clubFilter.addEventListener('change', filterEvents);
    if (statusFilter) statusFilter.addEventListener('change', filterEvents);

    window.resetEventFilters = function() {
        if (searchInput) searchInput.value = '';
        if (clubFilter) clubFilter.value = '';
        if (statusFilter) statusFilter.value = '';
        filterEvents();
    };
});
</script>
</body>
</html>
