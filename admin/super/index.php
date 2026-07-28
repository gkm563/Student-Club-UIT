<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: /admin/login.php');
    exit;
}

$db = Database::getConnection();
$deanName  = $_SESSION['full_name'] ?? 'Dean Sir';
$firstName = explode(' ', trim($deanName))[0];

$message = '';
$error = '';

// ── 1. Quick Action: Approve / Reject Proposal from Dashboard ─────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'proposal_decision') {
    $propId = trim($_POST['proposal_id'] ?? '');
    $newStatus = trim($_POST['status'] ?? 'pending');

    if ($propId && in_array($newStatus, ['approved', 'rejected', 'pending'])) {
        try {
            $stmt = $db->prepare("UPDATE club_proposals SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $propId]);

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'PROPOSAL_STATUS_CHANGED', 'proposal', $propId, "Updated proposal ID $propId status to " . strtoupper($newStatus) . " from Executive Dashboard");
            $message = "Proposal status updated to " . strtoupper($newStatus) . " successfully!";
        } catch (Exception $e) {
            $error = "Error updating proposal: " . $e->getMessage();
        }
    }
}

// ── 2. Quick Action: Dispatch Advisory to Dormant Chapter ─────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_advisory') {
    $clubId = trim($_POST['club_id'] ?? '');
    $note = trim($_POST['advisory_note'] ?? '');

    if ($clubId && $note) {
        try {
            $cStmt = $db->prepare("SELECT name FROM clubs WHERE id = ?");
            $cStmt->execute([$clubId]);
            $cName = $cStmt->fetchColumn() ?: $clubId;

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'DEAN_ADVISORY_ISSUED', 'club', $clubId, "Issued official Dean Advisory to $cName: '$note'");
            $message = "Official Dean Advisory dispatched to $cName leadership team!";
        } catch (Exception $e) {
            $error = "Error issuing advisory: " . $e->getMessage();
        }
    }
}

// ── Quick Stats ────────────────────────────────────────────────────────
$totalClubs    = (int)$db->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
$activeClubs   = (int)$db->query("SELECT COUNT(*) FROM clubs WHERE status = 'active'")->fetchColumn();
$inactiveClubs = $totalClubs - $activeClubs;
$totalEvents   = (int)$db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalLeaders  = (int)$db->query("SELECT COUNT(*) FROM leadership")->fetchColumn();
$totalGallery  = (int)$db->query("SELECT COUNT(*) FROM gallery_items")->fetchColumn();
$totalUsers    = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCats     = (int)$db->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Badges for pending items
$pendingProposalsCount = (int)$db->query("SELECT COUNT(*) FROM club_proposals WHERE status = 'pending'")->fetchColumn();
$unreadMessagesCount   = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();

// Upcoming events count
$upcomingEvents = (int)$db->query("SELECT COUNT(*) FROM events WHERE event_date >= NOW() AND status NOT IN ('draft','hidden','archived','cancelled')")->fetchColumn();

// ── Recent Clubs ───────────────────────────────────────────────────────
$recentClubs = $db->query("
    SELECT c.*, cat.name as category_name
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    ORDER BY c.created_at DESC LIMIT 5
")->fetchAll();

// ── Upcoming Events ────────────────────────────────────────────────────
$nextEvents = $db->query("
    SELECT e.*, c.name as club_name, c.logo as club_logo
    FROM events e
    JOIN clubs c ON e.club_id = c.id
    WHERE e.event_date >= NOW() AND e.status NOT IN ('draft','hidden','archived','cancelled')
    ORDER BY e.event_date ASC LIMIT 5
")->fetchAll();

// ── Audit Trail Feed ───────────────────────────────────────────────────
$recentActivity = $db->query("
    SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// ── Category distribution ──────────────────────────────────────────────
$catDist = $db->query("
    SELECT cat.name, COUNT(c.id) as cnt
    FROM categories cat
    LEFT JOIN clubs c ON c.category_id = cat.id
    GROUP BY cat.id, cat.name
")->fetchAll();

$dormantClubs = $db->query("
    SELECT c.id, c.name, c.short_name, c.logo, c.email
    FROM clubs c
    LEFT JOIN events e ON e.club_id = c.id
    WHERE e.id IS NULL OR e.created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)
    GROUP BY c.id, c.name, c.short_name, c.logo, c.email
    LIMIT 4
")->fetchAll();

$pendingProposals = $db->query("
    SELECT * FROM club_proposals ORDER BY created_at DESC LIMIT 6
")->fetchAll();

// ── Search Index for Instant Live Global Search ───────────────────────
$clubsSearch  = $db->query("SELECT id, name, short_name, logo, status, 'club' as type FROM clubs")->fetchAll(PDO::FETCH_ASSOC);
$usersSearch  = $db->query("SELECT id, full_name as name, email, role, status, 'user' as type FROM users")->fetchAll(PDO::FETCH_ASSOC);
$propsSearch  = $db->query("SELECT id, proposed_title as name, applicant_name as club_name, status, 'proposal' as type FROM club_proposals")->fetchAll(PDO::FETCH_ASSOC);
$eventsSearch = $db->query("SELECT e.id, e.title as name, c.name as club_name, e.event_date, 'event' as type FROM events e JOIN clubs c ON e.club_id = c.id LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

$searchIndexData = array_merge($clubsSearch, $usersSearch, $propsSearch, $eventsSearch);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Sir Portal – Executive Overview | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #1e293b; }

        /* Crisp Executive Card Styles */
        .exec-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            padding: 20px 22px;
            box-shadow: 0 2px 12px rgba(15, 23, 42, 0.03);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none !important;
            color: inherit;
            display: block;
        }
        .exec-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(79, 70, 229, 0.12);
            border-color: #a5b4fc;
        }
        .exec-card:hover .kpi-icon-wrapper {
            transform: scale(1.1) rotate(-3deg);
            transition: transform 0.25s ease;
        }

        /* SVG & Icon Spacing */
        .exec-card i, .exec-card svg {
            margin-right: 6px;
            display: inline-block;
        }
        .kpi-icon-wrapper i, .kpi-icon-wrapper svg {
            margin-right: 0 !important;
        }

        /* KPI Stat Box */
        .kpi-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
            transition: transform 0.25s ease;
        }

        .kpi-value {
            font-size: 1.85rem;
            font-weight: 800;
            line-height: 1.1;
            margin-top: 8px;
            margin-bottom: 4px;
            color: #0f172a;
        }
        .kpi-label {
            font-size: 0.75rem;
            font-weight: 600;
            color: #64748b;
            letter-spacing: 0.4px;
        }

        /* Executive Quick Action Buttons */
        .exec-qa-btn {
            border: 1px solid #e2e8f0;
            background: #ffffff;
            border-radius: 14px;
            padding: 12px 16px;
            color: #334155;
            font-weight: 600;
            font-size: 0.84rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
        }
        .exec-qa-btn:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            color: #4f46e5;
            transform: translateX(4px);
        }

        /* Table Tweaks */
        .table-custom th {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 12px 16px;
            white-space: nowrap;
        }
        .table-custom td {
            padding: 14px 16px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
        }
        .table-custom tr:last-child td {
            border-bottom: none;
        }

        /* Category distribution bar */
        .cat-bar-bg { background: #e2e8f0; height: 8px; border-radius: 6px; overflow: hidden; }
        .cat-bar-fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #4f46e5, #7c3aed); }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">

    <!-- Light Universal Executive Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Workspace -->
    <div class="flex-grow-1 d-flex flex-column" style="min-width:0;">

        <!-- Sticky Header Bar -->
        <header class="bg-white border-bottom px-4 py-3 d-flex align-items-center justify-content-between sticky-top" style="z-index:900; box-shadow:0 1px 6px rgba(0,0,0,0.04);">
            <div class="d-flex align-items-center gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Welcome back, <?= htmlspecialchars($firstName) ?>! 👋</h5>
                    <p class="text-secondary mb-0" style="font-size:0.78rem;">Executive Overview & Strategic Governance Dashboard</p>
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <!-- Live Search Shortcut Container -->
                <div class="position-relative">
                    <div class="input-group input-group-sm d-none d-sm-flex" style="width:280px;">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="superHeaderSearchInput" class="form-control bg-light border-start-0 ps-0" placeholder="Search clubs, admins, proposals..." style="font-size:0.82rem;" autocomplete="off">
                        <span class="input-group-text bg-light text-muted fw-bold" style="font-size:0.65rem;">Ctrl /</span>
                    </div>

                    <!-- Live Floating Search Results Dropdown -->
                    <div id="globalSearchResultsDropdown" class="card position-absolute end-0 mt-2 shadow-lg border-0 rounded-4 d-none overflow-hidden" style="width:360px; max-height:420px; z-index:9999; top:100%;">
                        <div class="card-header bg-light border-bottom py-2 px-3 d-flex justify-content-between align-items-center">
                            <span class="small fw-bold text-secondary font-monospace">INSTANT LIVE SEARCH RESULTS</span>
                            <button type="button" class="btn-close btn-close-xs" onclick="document.getElementById('globalSearchResultsDropdown').classList.add('d-none');"></button>
                        </div>
                        <div class="list-group list-group-flush overflow-y-auto" id="globalSearchResultsList" style="max-height:360px;">
                            <!-- Instant results rendered dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Date & Live Time Display Pill -->
                <div class="d-none d-md-flex align-items-center gap-2 border rounded-pill px-3 py-1 bg-light text-secondary" style="font-size:0.78rem;">
                    <i class="bi bi-clock-history text-primary me-1"></i>
                    <span class="fw-semibold text-dark" id="superHeaderLiveClock"><?= date('d M Y, h:i:s A') ?></span>
                </div>

                <!-- Quick Helpdesk Link -->
                <a href="messages.php" class="btn btn-light border rounded-circle position-relative p-2" style="width:38px;height:38px;" title="View Helpdesk Messages">
                    <i class="bi bi-bell text-dark fs-6 m-0"></i>
                    <?php if ($unreadMessagesCount > 0): ?>
                        <span class="position-absolute top-0 end-0 badge bg-danger rounded-pill" style="font-size:0.55rem;padding:3px 5px;"><?= $unreadMessagesCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </header>

        <!-- Main Body Workspace -->
        <main class="p-3 p-md-4 p-xl-5 flex-grow-1">

            <!-- Feedback Alert Banner -->
            <?php if (!empty($message)): ?>
                <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
            <?php endif; ?>

            <!-- Header Welcome & Executive Action Toolbar -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-bold small">INSTITUTIONAL GOVERNANCE</span>
                    <h3 class="fw-bold text-dark mb-1 mt-2">Campus Ecosystem Intelligence</h3>
                    <p class="text-secondary small mb-0">Monitor active student chapters, resolve pending proposals, and audit institutional activities.</p>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="analytics.php" class="btn btn-purple text-white fw-bold rounded-pill px-3.5 py-2 small shadow-sm" style="background:linear-gradient(135deg,#7c3aed,#4f46e5); border:none;" title="View Campus Power BI Analytics Suite">
                        <i class="bi bi-bar-chart-line-fill me-1"></i> Campus Analytics &rarr;
                    </a>
                    <a href="proposals.php" class="btn btn-warning text-dark fw-bold rounded-pill px-3.5 py-2 small shadow-sm position-relative">
                        <i class="bi bi-hourglass-split me-1"></i> Pending Proposals
                        <?php if ($pendingProposalsCount > 0): ?>
                            <span class="badge bg-danger rounded-pill ms-1"><?= $pendingProposalsCount ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="clubs.php" class="btn btn-primary fw-bold rounded-pill px-3.5 py-2 small shadow-sm">
                        <i class="bi bi-plus-circle me-1"></i> Register New Chapter
                    </a>
                </div>
            </div>

            <!-- 6 Executive KPI Metric Cards Deck (3 Top, 3 Bottom Layout) -->
            <div class="row g-3 mb-4">
                <!-- 1. Total Clubs -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="clubs.php" class="exec-card h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="kpi-label">TOTAL CAMPUS CLUBS</span>
                            <div class="kpi-icon-wrapper bg-primary-subtle text-primary"><i class="bi bi-trophy"></i></div>
                        </div>
                        <div class="kpi-value"><?= $totalClubs ?></div>
                        <div class="small text-success fw-semibold" style="font-size:0.75rem;"><i class="bi bi-check-circle me-1"></i><?= $activeClubs ?> Active Chapters (ON)</div>
                    </a>
                </div>

                <!-- 2. Pending Proposals -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="proposals.php" class="exec-card h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="kpi-label">PENDING PROPOSALS</span>
                            <div class="kpi-icon-wrapper bg-warning-subtle text-warning"><i class="bi bi-file-earmark-text"></i></div>
                        </div>
                        <div class="kpi-value"><?= $pendingProposalsCount ?></div>
                        <div class="small text-warning fw-bold" style="font-size:0.75rem;"><i class="bi bi-clock me-1"></i>Awaiting Dean Review</div>
                    </a>
                </div>

                <!-- 3. Campus Events Governance -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="events.php" class="exec-card h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="kpi-label">UPCOMING & CAMPUS EVENTS</span>
                            <div class="kpi-icon-wrapper bg-info-subtle text-info"><i class="bi bi-calendar-event"></i></div>
                        </div>
                        <div class="kpi-value"><?= $upcomingEvents ?></div>
                        <div class="small text-info fw-semibold" style="font-size:0.75rem;"><i class="bi bi-calendar-check me-1"></i>Governance Directory</div>
                    </a>
                </div>

                <!-- 4. System Users & Officers -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="users.php" class="exec-card h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="kpi-label">SYSTEM USERS & ROSTER</span>
                            <div class="kpi-icon-wrapper bg-purple-subtle text-purple" style="background:#f5f3ff; color:#7c3aed;"><i class="bi bi-people"></i></div>
                        </div>
                        <div class="kpi-value"><?= $totalUsers ?></div>
                        <div class="small text-secondary fw-semibold" style="font-size:0.75rem;"><i class="bi bi-shield-check me-1"></i>Admins & Club Leads</div>
                    </a>
                </div>

                <!-- 5. Power BI Campus Analytics -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="analytics.php" class="exec-card h-100" style="border-color:#c084fc;">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="kpi-label" style="color:#7c3aed;">CAMPUS DEEP ANALYTICS</span>
                            <div class="kpi-icon-wrapper text-white" style="background:linear-gradient(135deg,#7c3aed,#4f46e5);"><i class="bi bi-bar-chart-line"></i></div>
                        </div>
                        <div class="kpi-value" style="color:#6b21a8;">POWER BI</div>
                        <div class="small fw-bold" style="font-size:0.75rem; color:#7c3aed;"><i class="bi bi-graph-up-arrow me-1"></i>Ecosystem Intelligence & Rankings &rarr;</div>
                    </a>
                </div>

                <!-- 6. Domain Categories -->
                <div class="col-12 col-sm-6 col-lg-4">
                    <a href="categories.php" class="exec-card h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="kpi-label">DOMAIN CATEGORIES</span>
                            <div class="kpi-icon-wrapper bg-success-subtle text-success"><i class="bi bi-grid-3x3-gap"></i></div>
                        </div>
                        <div class="kpi-value"><?= $totalCats ?></div>
                        <div class="small text-success fw-semibold" style="font-size:0.75rem;"><i class="bi bi-folder-check me-1"></i>Active Domain Clusters</div>
                    </a>
                </div>
            </div>

            <!-- Two Column Layout: Proposals Review & Chapter Roster -->
            <div class="row g-4 mb-4">
                
                <!-- Left Column: Pending Proposals & Action Controls -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden h-100">
                        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-hourglass-split text-warning fs-5"></i>
                                <h6 class="fw-bold mb-0 text-dark">Pending Proposals Requiring Approval</h6>
                            </div>
                            <a href="proposals.php" class="text-primary text-decoration-none small fw-bold">View All (<?= $pendingProposalsCount ?>) &rarr;</a>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-custom align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>PROPOSAL / EVENT</th>
                                        <th>CHAPTER</th>
                                        <th>BUDGET</th>
                                        <th class="text-end">EXECUTIVE ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($pendingProposals)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">No pending event proposals at this time. All caught up! 🎉</td>
                                        </tr>
                                    <?php else: foreach ($pendingProposals as $prop): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($prop['title']) ?></div>
                                                <span class="text-secondary small"><?= date('M d, Y', strtotime($prop['created_at'])) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-light text-dark border rounded-pill px-2.5 py-1 small">
                                                    <?= htmlspecialchars($prop['club_name']) ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold font-monospace small">
                                                ₹<?= number_format(floatval($prop['budget_requested'] ?? 0)) ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($prop['status'] === 'pending'): ?>
                                                    <div class="btn-group">
                                                        <form action="index.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="proposal_decision">
                                                            <input type="hidden" name="proposal_id" value="<?= $prop['id'] ?>">
                                                            <input type="hidden" name="status" value="approved">
                                                            <button type="submit" class="btn btn-success btn-sm rounded-pill me-1 px-3 fw-bold" title="Approve Proposal">
                                                                <i class="bi bi-check-lg me-1"></i> Approve
                                                            </button>
                                                        </form>

                                                        <form action="index.php" method="POST" class="d-inline">
                                                            <input type="hidden" name="action" value="proposal_decision">
                                                            <input type="hidden" name="proposal_id" value="<?= $prop['id'] ?>">
                                                            <input type="hidden" name="status" value="rejected">
                                                            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3" title="Reject Proposal">
                                                                Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">Processed</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Recent Clubs Roster & Quick Action Bar -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden h-100">
                        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-trophy text-primary fs-5"></i>
                                <h6 class="fw-bold mb-0 text-dark">Active Campus Chapters</h6>
                            </div>
                            <a href="clubs.php" class="text-primary text-decoration-none small fw-bold">Manage All &rarr;</a>
                        </div>

                        <div class="p-3">
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($recentClubs as $rc): ?>
                                    <?php $rcHealth = calculate_club_profile_health($rc, $db); ?>
                                    <a href="club-detail.php?id=<?= $rc['id'] ?>" class="p-2.5 rounded-3 border d-flex align-items-center justify-content-between text-decoration-none text-dark hover-bg-light" style="transition: background 0.2s ease;">
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="<?= htmlspecialchars($rc['logo'] ?: '../../assets/United Logo.webp') ?>" class="rounded-2 border" style="width:36px;height:36px;object-fit:cover;" alt="">
                                            <div>
                                                <div class="fw-bold mb-0 text-dark"><?= htmlspecialchars($rc['name']) ?></div>
                                                <span class="text-secondary small" style="font-size:0.75rem;"><?= htmlspecialchars($rc['category_name']) ?></span>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-<?= $rcHealth['badge_class'] ?>-subtle text-<?= $rcHealth['badge_class'] ?> border rounded-pill px-2.5 py-1 small">
                                                <i class="bi bi-heart-pulse me-1"></i><?= $rcHealth['score'] ?>% Health
                                            </span>
                                            <span class="badge <?= ($rc['status'] === 'active') ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> rounded-pill px-2.5 py-1 small">
                                                <?= ($rc['status'] === 'active') ? 'Active' : 'Private' ?>
                                            </span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Bottom Row: Audit Feed & Quick Governance Controls -->
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                        <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-shield-check text-purple fs-5" style="color:#7c3aed;"></i>
                                <h6 class="fw-bold mb-0 text-dark">Recent System Audit Log Activity</h6>
                            </div>
                            <a href="audit-logs.php" class="text-primary text-decoration-none small fw-bold">Full Audit Logs &rarr;</a>
                        </div>

                        <div class="p-3">
                            <div class="d-flex flex-column gap-2">
                                <?php foreach ($recentActivity as $act): ?>
                                    <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2.5">
                                            <div class="rounded-circle bg-purple-subtle text-purple d-flex align-items-center justify-content-center fw-bold small flex-shrink-0" style="width:32px;height:32px;background:#f5f3ff;color:#7c3aed;">
                                                <i class="bi bi-shield-lock"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($act['user_name'] ?? 'System') ?> – <span class="text-secondary fw-normal"><?= htmlspecialchars($act['details']) ?></span></div>
                                                <span class="text-muted" style="font-size:0.7rem;"><?= date('M d, Y h:i A', strtotime($act['created_at'])) ?></span>
                                            </div>
                                        </div>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-0.5" style="font-size:0.65rem;"><?= htmlspecialchars($act['action']) ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 bg-white p-4">
                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-lightning-charge text-warning me-2"></i>Executive Portal Shortcuts</h6>
                        <div class="d-flex flex-column gap-2">
                            <a href="users.php" class="exec-qa-btn">
                                <i class="bi bi-shield-lock-fill text-purple" style="color:#7c3aed;"></i> Manage Main Admins & Governance Roles
                            </a>
                            <a href="users.php?tab=club-leads" class="exec-qa-btn">
                                <i class="bi bi-person-badge-fill text-info"></i> Manage Club Leads & Reset Passwords
                            </a>
                            <a href="clubs.php" class="exec-qa-btn">
                                <i class="bi bi-trophy-fill text-primary"></i> Manage Campus Student Clubs & Privacy ON/OFF
                            </a>
                            <a href="proposals.php" class="exec-qa-btn">
                                <i class="bi bi-file-earmark-check-fill text-success"></i> Event Proposals Approval Queue
                            </a>
                            <a href="categories.php" class="exec-qa-btn">
                                <i class="bi bi-grid-3x3-gap-fill text-danger"></i> Domain Categories Management
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Live Ticking Clock Update
    const clockElem = document.getElementById('superHeaderLiveClock');
    if (clockElem) {
        const updateClock = () => {
            const now = new Date();
            const day = String(now.getDate()).padStart(2, '0');
            const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            const month = months[now.getMonth()];
            const year = now.getFullYear();
            let hours = now.getHours();
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12;
            hours = hours ? hours : 12; // hour 0 should be 12
            const strHours = String(hours).padStart(2, '0');
            clockElem.innerText = `${day} ${month} ${year}, ${strHours}:${minutes}:${seconds} ${ampm}`;
        };
        setInterval(updateClock, 1000);
        updateClock();
    }

    // ── Instant Live Search Engine ──────────────────────────────────────
    const searchIndex = <?= json_encode($searchIndexData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
    const searchInput = document.getElementById('superHeaderSearchInput');
    const searchDropdown = document.getElementById('globalSearchResultsDropdown');
    const searchResultsList = document.getElementById('globalSearchResultsList');

    if (searchInput && searchDropdown && searchResultsList) {
        
        // Ctrl / Keyboard shortcut focus
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === '/') {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });

        // Instant Live Input Event
        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();

            if (query.length === 0) {
                searchDropdown.classList.add('d-none');
                return;
            }

            const matches = searchIndex.filter(item => {
                const nameMatch = (item.name || '').toLowerCase().includes(query);
                const shortMatch = (item.short_name || '').toLowerCase().includes(query);
                const emailMatch = (item.email || '').toLowerCase().includes(query);
                const clubMatch = (item.club_name || '').toLowerCase().includes(query);
                return nameMatch || shortMatch || emailMatch || clubMatch;
            }).slice(0, 10); // Limit to top 10 matches

            if (matches.length === 0) {
                searchResultsList.innerHTML = `
                    <div class="p-3 text-center text-muted small">
                        <i class="bi bi-search fs-4 text-secondary d-block mb-1"></i>
                        No matches found for "<strong>${escapeHtml(query)}</strong>"
                    </div>`;
            } else {
                let html = '';
                matches.forEach(item => {
                    let icon = 'bi-file-text';
                    let badgeClass = 'bg-secondary-subtle text-secondary';
                    let typeLabel = 'Item';
                    let link = '#';
                    let subtext = '';

                    if (item.type === 'club') {
                        icon = 'bi-trophy-fill text-warning';
                        badgeClass = 'bg-primary-subtle text-primary';
                        typeLabel = 'CLUB';
                        link = 'club-detail.php?id=' + item.id;
                        subtext = item.short_name ? `Code: ${item.short_name}` : '';
                    } else if (item.type === 'user') {
                        icon = 'bi-person-badge-fill text-purple';
                        badgeClass = (item.role === 'super_admin') ? 'bg-purple-subtle text-purple' : 'bg-info-subtle text-info';
                        typeLabel = (item.role === 'super_admin') ? 'MAIN ADMIN' : 'CLUB LEAD';
                        link = 'users.php?tab=' + (item.role === 'super_admin' ? 'main-admins' : 'club-leads');
                        subtext = item.email || '';
                    } else if (item.type === 'proposal') {
                        icon = 'bi-file-earmark-text-fill text-success';
                        badgeClass = 'bg-warning-subtle text-warning';
                        typeLabel = 'PROPOSAL';
                        link = 'proposals.php';
                        subtext = item.club_name ? `Club: ${item.club_name}` : '';
                    } else if (item.type === 'event') {
                        icon = 'bi-calendar-event-fill text-primary';
                        badgeClass = 'bg-success-subtle text-success';
                        typeLabel = 'EVENT';
                        link = 'clubs.php?search=' + encodeURIComponent(item.club_name || item.name);
                        subtext = item.event_date ? `Date: ${item.event_date}` : '';
                    }

                    html += `
                        <a href="${link}" class="list-group-item list-group-item-action p-2.5 d-flex align-items-center justify-content-between text-decoration-none">
                            <div class="d-flex align-items-center gap-2.5 overflow-hidden">
                                <i class="bi ${icon} fs-5 flex-shrink-0"></i>
                                <div class="text-truncate">
                                    <div class="fw-bold text-dark small text-truncate mb-0">${escapeHtml(item.name)}</div>
                                    ${subtext ? `<div class="text-secondary" style="font-size:0.72rem;">${escapeHtml(subtext)}</div>` : ''}
                                </div>
                            </div>
                            <span class="badge ${badgeClass} rounded-pill px-2 py-0.5 ms-2 flex-shrink-0" style="font-size:0.65rem;">${typeLabel}</span>
                        </a>`;
                });
                searchResultsList.innerHTML = html;
            }

            searchDropdown.classList.remove('d-none');
        });

        // Enter key fallback redirect
        searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                const query = searchInput.value.trim();
                if (query) {
                    window.location.href = 'clubs.php?search=' + encodeURIComponent(query);
                }
            }
        });

        // Close search dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                searchDropdown.classList.add('d-none');
            }
        });
    }
});

function escapeHtml(str) {
    if (!str) return '';
    return str.replace(/[&<>"']/g, function(m) {
        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
    });
}
</script>
</body>
</html>
