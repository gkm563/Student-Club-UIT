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

// ── Filter Parameter: Specific Club Filter ─────────────────────────────
$selectedClubId = trim($_GET['club_id'] ?? '');
$filterClub = null;

if (!empty($selectedClubId)) {
    $fcStmt = $db->prepare("SELECT id, name, short_name, logo, tagline FROM clubs WHERE id = ?");
    $fcStmt->execute([$selectedClubId]);
    $filterClub = $fcStmt->fetch();
    if (!$filterClub) {
        $selectedClubId = ''; // Reset if invalid
    }
}

// ── 1. Star Club of the Month (Last 30 Days Most Active) ─────────────
$starClubStmt = $db->query("
    SELECT c.id, c.name, c.short_name, c.logo, c.tagline, cat.name as category_name,
           COUNT(e.id) as month_events, COALESCE(SUM(e.actual_attended), 0) as month_attendance
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    JOIN events e ON e.club_id = c.id
    WHERE e.event_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY c.id
    ORDER BY month_events DESC, month_attendance DESC
    LIMIT 1
");
$starClub = $starClubStmt->fetch();

// ── 2. Most Active Club in Last 7 Days ────────────────────────────────
$active7DaysStmt = $db->query("
    SELECT c.id, c.name, c.short_name, c.logo, COUNT(e.id) as week_events, COALESCE(SUM(e.actual_attended), 0) as week_attendance
    FROM clubs c
    JOIN events e ON e.club_id = c.id
    WHERE e.event_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY c.id
    ORDER BY week_events DESC, week_attendance DESC
    LIMIT 1
");
$active7DaysClub = $active7DaysStmt->fetch();

// ── 3. Highest Roster Chapter ─────────────────────────────────────────
$topRosterStmt = $db->query("
    SELECT c.id, c.name, c.short_name, c.logo, COUNT(l.id) as roster_count
    FROM clubs c
    LEFT JOIN leadership l ON l.club_id = c.id
    GROUP BY c.id
    ORDER BY roster_count DESC
    LIMIT 1
");
$topRosterClub = $topRosterStmt->fetch();

// ── 4. Best Event of the Month (Highest Attendance) ──────────────────
$bestEventStmt = $db->query("
    SELECT e.*, c.name as club_name, c.short_name as club_short, c.logo as club_logo
    FROM events e
    JOIN clubs c ON e.club_id = c.id
    WHERE e.event_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ORDER BY e.actual_attended DESC
    LIMIT 1
");
$bestEvent = $bestEventStmt->fetch();

// ── 5. Club Rankings Directory (Scoped by Selected Club if filtered) ──
$whereClause = !empty($selectedClubId) ? "WHERE c.id = " . $db->quote($selectedClubId) : "";

$rankingsQuery = "
    SELECT c.id, c.name, c.short_name, c.logo, c.status, cat.name as category_name,
           COUNT(e.id) as total_events,
           COALESCE(SUM(e.actual_attended), 0) as total_attendance,
           COALESCE(SUM(e.budget_utilized), 0) as total_budget,
           (SELECT COUNT(*) FROM leadership l WHERE l.club_id = c.id) as roster_count
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN events e ON e.club_id = c.id
    $whereClause
    GROUP BY c.id, c.name, c.short_name, c.logo, c.status, cat.name
    ORDER BY total_events DESC, total_attendance DESC
";
$clubRankings = $db->query($rankingsQuery)->fetchAll();

// ── 6. Filtered Events Breakdown ──────────────────────────────────────
$eventsQuery = "
    SELECT e.*, c.name as club_name, c.short_name as club_short, c.logo as club_logo
    FROM events e
    JOIN clubs c ON e.club_id = c.id
    $whereClause
    ORDER BY e.event_date DESC
    LIMIT 20
";
$analyticalEvents = $db->query($eventsQuery)->fetchAll();

// Overall Metrics
$totalCampusEvents = (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalCampusAttendance = (int)$db->query("SELECT COALESCE(SUM(actual_attended), 0) FROM events")->fetchColumn();
$totalCampusBudget = (float)$db->query("SELECT COALESCE(SUM(budget_utilized), 0) FROM events")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Analytics & Ecosystem Intelligence | Dean Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #1e293b; }

        .analytics-card {
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: #ffffff;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(15, 23, 42, 0.04);
            transition: all 0.25s ease;
        }
        .analytics-card:hover {
            box-shadow: 0 10px 28px rgba(79, 70, 229, 0.08);
            border-color: #cbd5e1;
        }

        .star-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            border-radius: 20px;
            padding: 28px;
            color: #ffffff;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
        }

        .clickable-row { cursor: pointer; transition: background 0.15s ease; }
        .clickable-row:hover { background: #f1f5f9 !important; }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">
    <!-- Universal Super Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Content Workspace -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">

        <!-- Top Header & Filter Toolbar -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">POWER BI ANALYTICS SUITE</span>
                <h2 class="fw-bold mb-1 text-dark mt-2">Campus Ecosystem Intelligence & Analytics</h2>
                <p class="text-secondary small mb-0">Deep data analytics, chapter performance rankings, event metrics, and budget utilization tracking.</p>
            </div>

            <!-- Active Filter Badge Pill if filtered -->
            <?php if (!empty($filterClub)): ?>
                <div class="d-flex align-items-center gap-2 bg-primary-subtle border border-primary-subtle rounded-pill px-3 py-1-5">
                    <img src="<?= htmlspecialchars($filterClub['logo'] ?: '../../assets/United Logo.webp') ?>" class="rounded-circle border" style="width:24px;height:24px;object-fit:cover;" alt="">
                    <span class="small fw-bold text-primary">Filter: <?= htmlspecialchars($filterClub['name']) ?></span>
                    <a href="analytics.php" class="btn-close btn-close-xs ms-1" title="Clear Club Filter"></a>
                </div>
            <?php endif; ?>
        </div>

        <!-- ── Top Analytical Highlights Row ── -->
        <div class="row g-4 mb-4">
            
            <!-- Star Club of the Month Banner -->
            <div class="col-lg-7">
                <div class="star-banner h-100 d-flex flex-column justify-content-between position-relative overflow-hidden">
                    <div class="position-absolute end-0 top-0 p-4 opacity-10" style="font-size: 8rem; line-height: 0.8; color:#ffffff;">
                        <i class="bi bi-star-fill"></i>
                    </div>

                    <div>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <span class="badge bg-warning text-dark rounded-pill px-3 py-1 fw-bold small">
                                <i class="bi bi-trophy-fill me-1"></i> STAR CLUB OF THE MONTH
                            </span>
                            <span class="badge bg-white-subtle text-white border rounded-pill px-2.5 py-1 small">Top Activity Score</span>
                        </div>

                        <?php if ($starClub): ?>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <img src="<?= htmlspecialchars($starClub['logo'] ?: '../../assets/United Logo.webp') ?>" 
                                     class="rounded-3 border border-white-subtle" style="width:64px;height:64px;object-fit:cover;" alt="">
                                <div>
                                    <h3 class="fw-bold text-white mb-0"><?= htmlspecialchars($starClub['name']) ?></h3>
                                    <span class="text-white-50 small"><?= htmlspecialchars($starClub['category_name']) ?> Domain</span>
                                </div>
                            </div>
                            <p class="text-white-50 small mb-4"><?= htmlspecialchars($starClub['tagline'] ?: 'Demonstrated outstanding campus engagement and event execution.') ?></p>
                        <?php else: ?>
                            <h4 class="text-white">No active club data for this month yet.</h4>
                        <?php endif; ?>
                    </div>

                    <?php if ($starClub): ?>
                        <div class="d-flex align-items-center justify-content-between pt-3 border-top border-white-subtle">
                            <div class="d-flex gap-4 font-monospace">
                                <div>
                                    <span class="text-white-50 d-block small">MONTH EVENTS</span>
                                    <span class="fs-5 fw-bold text-warning"><?= $starClub['month_events'] ?> Events</span>
                                </div>
                                <div>
                                    <span class="text-white-50 d-block small">STUDENT ATTENDEES</span>
                                    <span class="fs-5 fw-bold text-info"><?= number_format($starClub['month_attendance']) ?> Students</span>
                                </div>
                            </div>
                            <a href="club-detail.php?id=<?= $starClub['id'] ?>" class="btn btn-light rounded-pill px-4 fw-bold text-dark shadow-sm">
                                View Chapter 360° &rarr;
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Best Event of the Month Card -->
            <div class="col-lg-5">
                <div class="analytics-card h-100 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <span class="badge bg-success-subtle text-success border rounded-pill px-3 py-1 fw-bold small">
                                <i class="bi bi-award-fill me-1"></i> BEST EVENT OF THE MONTH
                            </span>
                            <span class="text-secondary small font-monospace">Highest Attendance</span>
                        </div>

                        <?php if ($bestEvent): ?>
                            <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($bestEvent['title']) ?></h4>
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <a href="club-detail.php?id=<?= $bestEvent['club_id'] ?>" class="text-decoration-none d-inline-flex align-items-center gap-1.5">
                                    <img src="<?= htmlspecialchars($bestEvent['club_logo'] ?: '../../assets/United Logo.webp') ?>" class="rounded-circle border" style="width:20px;height:20px;object-fit:cover;" alt="">
                                    <span class="fw-bold text-primary small"><?= htmlspecialchars($bestEvent['club_name']) ?></span>
                                </a>
                            </div>

                            <div class="row g-2 mb-3 font-monospace small">
                                <div class="col-6">
                                    <div class="bg-light p-2.5 rounded-3 border">
                                        <span class="text-secondary d-block" style="font-size:0.7rem;">ATTENDANCE</span>
                                        <span class="fw-bold text-success fs-6"><?= number_format($bestEvent['actual_attended']) ?> Attended</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="bg-light p-2.5 rounded-3 border">
                                        <span class="text-secondary d-block" style="font-size:0.7rem;">BUDGET UTILIZED</span>
                                        <span class="fw-bold text-purple fs-6" style="color:#7c3aed;">₹<?= number_format(floatval($bestEvent['budget_utilized'])) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No completed event records for this month yet.</p>
                        <?php endif; ?>
                    </div>

                    <a href="events.php" class="btn btn-outline-primary rounded-pill w-100 fw-bold">
                        <i class="bi bi-calendar-event me-1"></i> Explore Full Events Governance Directory &rarr;
                    </a>
                </div>
            </div>

        </div>

        <!-- ── 3 Compact Highlight Cards ── -->
        <div class="row g-3 mb-4">
            <!-- Most Active Last 7 Days -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning-subtle text-warning d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                            <i class="bi bi-lightning-charge-fill fs-4"></i>
                        </div>
                        <div class="overflow-hidden">
                            <span class="text-secondary small font-monospace fw-bold text-uppercase d-block">MOST ACTIVE (LAST 7 DAYS)</span>
                            <?php if ($active7DaysClub): ?>
                                <a href="club-detail.php?id=<?= $active7DaysClub['id'] ?>" class="fw-bold text-dark text-decoration-none text-truncate d-block">
                                    <?= htmlspecialchars($active7DaysClub['name']) ?>
                                </a>
                                <span class="badge bg-warning-subtle text-warning rounded-pill px-2 py-0.5 small mt-0.5"><?= $active7DaysClub['week_events'] ?> Events this week</span>
                            <?php else: ?>
                                <span class="text-muted small">No weekly activity</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Highest Roster Chapter -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-info-subtle text-info d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                        <div class="overflow-hidden">
                            <span class="text-secondary small font-monospace fw-bold text-uppercase d-block">LARGEST CORE ROSTER</span>
                            <?php if ($topRosterClub): ?>
                                <a href="club-detail.php?id=<?= $topRosterClub['id'] ?>" class="fw-bold text-dark text-decoration-none text-truncate d-block">
                                    <?= htmlspecialchars($topRosterClub['name']) ?>
                                </a>
                                <span class="badge bg-info-subtle text-info rounded-pill px-2 py-0.5 small mt-0.5"><?= $topRosterClub['roster_count'] ?> Active Officers</span>
                            <?php else: ?>
                                <span class="text-muted small">No roster data</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overall Ecosystem Reach -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px;height:48px;">
                            <i class="bi bi-graph-up-arrow fs-4"></i>
                        </div>
                        <div class="overflow-hidden">
                            <span class="text-secondary small font-monospace fw-bold text-uppercase d-block">TOTAL ECOSYSTEM REACH</span>
                            <div class="fw-bold text-dark fs-6"><?= number_format($totalCampusAttendance) ?> Student Attendees</div>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2 py-0.5 small mt-0.5"><?= $totalCampusEvents ?> Events Conducted</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Campus Club Performance Rankings Table ── -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden mb-4">
            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                <div>
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-line-fill text-primary me-2"></i>Campus Chapter Performance & Activity Rankings</h6>
                    <span class="text-secondary small">Click on any club row to view 360° Executive Chapter Details</span>
                </div>
                <a href="clubs.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">Manage Clubs Directory &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="analyticsRankingsTable">
                    <thead class="table-light">
                        <tr class="small text-secondary">
                            <th>RANK</th>
                            <th>CHAPTER NAME</th>
                            <th>DOMAIN CATEGORY</th>
                            <th>EVENTS HOSTED</th>
                            <th>STUDENT ATTENDANCE</th>
                            <th>BUDGET UTILIZED</th>
                            <th>ROSTER OFFICERS</th>
                            <th class="text-end">EXECUTIVE ACTION</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($clubRankings)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">No club performance records available.</td>
                            </tr>
                        <?php else: foreach ($clubRankings as $rankIdx => $cr): ?>
                            <tr class="clickable-row" onclick="window.location.href='club-detail.php?id=<?= $cr['id'] ?>'">
                                <td class="fw-bold text-secondary font-monospace">
                                    <?php if ($rankIdx === 0): ?>
                                        <span class="badge bg-warning text-dark rounded-circle p-2" style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">#1</span>
                                    <?php elseif ($rankIdx === 1): ?>
                                        <span class="badge bg-secondary text-white rounded-circle p-2" style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">#2</span>
                                    <?php elseif ($rankIdx === 2): ?>
                                        <span class="badge bg-danger-subtle text-danger rounded-circle p-2" style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">#3</span>
                                    <?php else: ?>
                                        <span class="ps-2">#<?= $rankIdx + 1 ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2.5">
                                        <img src="<?= htmlspecialchars($cr['logo'] ?: '../../assets/United Logo.webp') ?>" class="rounded-2 border" style="width:32px;height:32px;object-fit:cover;" alt="">
                                        <div>
                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($cr['name']) ?></div>
                                            <span class="text-secondary font-monospace" style="font-size:0.72rem;"><?= htmlspecialchars($cr['short_name']) ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small"><?= htmlspecialchars($cr['category_name']) ?></span>
                                </td>
                                <td class="fw-bold font-monospace text-dark"><?= $cr['total_events'] ?> Events</td>
                                <td class="fw-bold font-monospace text-success"><?= number_format($cr['total_attendance']) ?> Students</td>
                                <td class="fw-bold font-monospace text-purple" style="color:#7c3aed;">₹<?= number_format(floatval($cr['total_budget'])) ?></td>
                                <td>
                                    <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small"><i class="bi bi-people me-1"></i><?= $cr['roster_count'] ?> Officers</span>
                                </td>
                                <td class="text-end" onclick="event.stopPropagation();">
                                    <a href="club-detail.php?id=<?= $cr['id'] ?>" class="btn btn-sm btn-light rounded-circle me-1" title="View 360° Chapter Detail">
                                        <i class="bi bi-arrow-right text-primary fs-6"></i>
                                    </a>
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
</body>
</html>
