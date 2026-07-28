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
    ORDER BY cnt DESC
")->fetchAll();

// ── Executive Intelligence Queries ─────────────────────────────────────
$topPerformingClub = $db->query("
    SELECT c.id, c.name, c.short_name, c.logo, COUNT(e.id) as total_events
    FROM clubs c
    LEFT JOIN events e ON e.club_id = c.id
    GROUP BY c.id, c.name, c.short_name, c.logo
    ORDER BY total_events DESC LIMIT 1
")->fetch();

$largestClub = $db->query("
    SELECT c.name, c.short_name, c.logo, COUNT(l.id) as member_count
    FROM clubs c
    LEFT JOIN leadership l ON l.club_id = c.id
    GROUP BY c.id, c.name, c.short_name, c.logo
    ORDER BY member_count DESC LIMIT 1
")->fetch();

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
            border-color: #4f46e5;
            background: #f5f3ff;
            color: #4f46e5;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.12);
        }

        /* Clean Table Overlap Fixes */
        .table-responsive {
            border-radius: 12px;
            overflow-x: auto;
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
                <!-- Live Search Shortcut -->
                <div class="input-group input-group-sm d-none d-sm-flex" style="width:230px;">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="superHeaderSearchInput" class="form-control bg-light border-start-0 ps-0" placeholder="Search clubs, users..." style="font-size:0.82rem;">
                    <span class="input-group-text bg-light text-muted fw-bold" style="font-size:0.65rem;">Ctrl /</span>
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
                    <a href="clubs.php" class="btn btn-primary rounded-pill px-3 py-2 fw-bold text-white shadow-sm d-flex align-items-center gap-2" style="font-size:0.85rem;">
                        <i class="bi bi-plus-circle-fill m-0"></i> Add New Club
                    </a>
                    <a href="proposals.php" class="btn btn-outline-purple rounded-pill px-3 py-2 fw-bold text-purple border-purple d-flex align-items-center gap-2" style="font-size:0.85rem; color:#7c3aed; border-color:#c084fc;">
                        <i class="bi bi-journal-check m-0"></i> Proposals Center
                    </a>
                </div>
            </div>

            <!-- ── 5 Key Performance Metric Cards (Clickable) ── -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg">
                    <a href="clubs.php" class="exec-card" title="Manage Campus Clubs">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="kpi-icon-wrapper bg-primary-subtle text-primary"><i class="bi bi-trophy-fill"></i></div>
                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 py-1 small fw-bold"><?= $activeClubs ?> Active</span>
                        </div>
                        <div class="kpi-value"><?= $totalClubs ?></div>
                        <div class="kpi-label">Campus Chapters</div>
                    </a>
                </div>

                <div class="col-6 col-lg">
                    <a href="clubs.php" class="exec-card" title="View Scheduled Events">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="kpi-icon-wrapper bg-success-subtle text-success"><i class="bi bi-calendar-event-fill"></i></div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 small fw-bold"><?= $upcomingEvents ?> Upcoming</span>
                        </div>
                        <div class="kpi-value"><?= $totalEvents ?></div>
                        <div class="kpi-label">Organized Events</div>
                    </a>
                </div>

                <div class="col-6 col-lg">
                    <a href="users.php" class="exec-card" title="Manage Student Roster">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="kpi-icon-wrapper bg-warning-subtle text-warning"><i class="bi bi-people-fill"></i></div>
                            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small fw-bold">Active Leads</span>
                        </div>
                        <div class="kpi-value"><?= $totalLeaders ?></div>
                        <div class="kpi-label">Core Student Officers</div>
                    </a>
                </div>

                <div class="col-6 col-lg">
                    <a href="proposals.php" class="exec-card" title="Review Student Proposals">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="kpi-icon-wrapper bg-purple-subtle text-purple" style="background:#f5f3ff; color:#7c3aed;"><i class="bi bi-file-earmark-text-fill"></i></div>
                            <?php if ($pendingProposalsCount > 0): ?>
                                <span class="badge bg-warning text-dark rounded-pill px-2 py-1 small fw-bold"><?= $pendingProposalsCount ?> Unreviewed</span>
                            <?php else: ?>
                                <span class="badge bg-success-subtle text-success border rounded-pill px-2 py-1 small fw-bold">Up to date</span>
                            <?php endif; ?>
                        </div>
                        <div class="kpi-value"><?= $pendingProposalsCount ?></div>
                        <div class="kpi-label">Pending Proposals</div>
                    </a>
                </div>

                <div class="col-6 col-lg">
                    <a href="messages.php" class="exec-card" title="Open Helpdesk Inbox">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="kpi-icon-wrapper bg-info-subtle text-info"><i class="bi bi-inbox-fill"></i></div>
                            <?php if ($unreadMessagesCount > 0): ?>
                                <span class="badge bg-danger text-white rounded-pill px-2 py-1 small fw-bold"><?= $unreadMessagesCount ?> New</span>
                            <?php else: ?>
                                <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 small fw-bold">Resolved</span>
                            <?php endif; ?>
                        </div>
                        <div class="kpi-value"><?= $unreadMessagesCount ?></div>
                        <div class="kpi-label">Helpdesk Queries</div>
                    </a>
                </div>
            </div>

            <!-- ── Executive Decision Intelligence Deck (Clickable Cards) ── -->
            <div class="row g-3 g-xl-4 mb-4">
                <!-- Top Performing Club -->
                <div class="col-md-4">
                    <a href="clubs.php?search=<?= urlencode($topPerformingClub['name'] ?? '') ?>" class="exec-card h-100" style="border-top: 4px solid #3b82f6 !important;" title="View Top Performing Club Details">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= htmlspecialchars($topPerformingClub['logo'] ?: '../../assets/United Logo.webp') ?>" class="rounded-3 border flex-shrink-0" style="width:48px;height:48px;object-fit:cover;" alt="">
                            <div class="overflow-hidden">
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 mb-1 small fw-bold">TOP PERFORMING CHAPTER</span>
                                <h6 class="fw-bold text-dark mb-1 text-truncate"><?= htmlspecialchars($topPerformingClub['name'] ?? 'GDGOC UIT') ?></h6>
                                <p class="text-secondary small mb-0" style="font-size:0.75rem;"><i class="bi bi-award text-primary me-1"></i><?= (int)($topPerformingClub['total_events'] ?? 0) ?> Official Events Hosted</p>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Largest Roster -->
                <div class="col-md-4">
                    <a href="users.php" class="exec-card h-100" style="border-top: 4px solid #10b981 !important;" title="View Student Leadership Roster">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= htmlspecialchars($largestClub['logo'] ?: '../../assets/United Logo.webp') ?>" class="rounded-3 border flex-shrink-0" style="width:48px;height:48px;object-fit:cover;" alt="">
                            <div class="overflow-hidden">
                                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 mb-1 small fw-bold">LARGEST CORE ROSTER</span>
                                <h6 class="fw-bold text-dark mb-1 text-truncate"><?= htmlspecialchars($largestClub['name'] ?? 'GFG SC UIT') ?></h6>
                                <p class="text-secondary small mb-0" style="font-size:0.75rem;"><i class="bi bi-people text-success me-1"></i><?= (int)($largestClub['member_count'] ?? 0) ?> Active Core Officers</p>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Dormant Chapters Alert -->
                <div class="col-md-4">
                    <div class="exec-card h-100" style="border-top: 4px solid #ef4444 !important;" data-bs-toggle="modal" data-bs-target="#dormantClubsModal">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge bg-danger-subtle text-danger rounded-pill px-2.5 py-1 small fw-bold">AUDIT ALERT</span>
                            <span class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1" style="font-size:0.7rem;">
                                <i class="bi bi-send me-1"></i> Issue Advisory
                            </span>
                        </div>
                        <h6 class="fw-bold text-dark mb-1"><?= count($dormantClubs) ?> Inactive Chapters</h6>
                        <p class="text-secondary small mb-0" style="font-size:0.75rem;"><i class="bi bi-exclamation-circle me-1 text-danger"></i>No event logged in last 60 days. Click to dispatch advisory.</p>
                    </div>
                </div>
            </div>

            <!-- ── Pending Club & Event Proposals Executive Decision Table ── -->
            <div class="exec-card mb-4 overflow-hidden">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-text text-primary me-2"></i>Pending Club & Event Proposals</h6>
                        <span class="text-secondary small" style="font-size:0.75rem;">Submitted by students & faculty for Dean Student Welfare authorization</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-purple-subtle text-purple border border-purple-subtle rounded-pill px-3 py-1" style="background:#f5f3ff; color:#7c3aed; font-size:0.75rem;"><?= count($pendingProposals) ?> Proposals</span>
                        <a href="proposals.php" class="btn btn-sm btn-outline-purple rounded-pill px-3 py-1 fw-bold" style="font-size:0.75rem; color:#7c3aed; border-color:#c084fc;">
                            Proposals Center &rarr;
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-custom align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Proposed Title</th>
                                <th>Applicant & Verification</th>
                                <th>Faculty Mentor</th>
                                <th>Submission Date</th>
                                <th>Status</th>
                                <th class="text-end">Dean Decision</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingProposals)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted small">
                                        <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                                        All submitted proposals have been processed.
                                    </td>
                                </tr>
                            <?php else: foreach ($pendingProposals as $prop): ?>
                                <tr>
                                    <td>
                                        <span class="badge rounded-pill px-2.5 py-1 <?= $prop['proposal_type'] === 'new_club' ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-success-subtle text-success border border-success-subtle' ?>">
                                            <?= $prop['proposal_type'] === 'new_club' ? 'New Club' : 'New Event' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($prop['proposed_title']) ?></div>
                                        <div class="text-muted small" style="font-size:0.7rem;"><?= htmlspecialchars(substr($prop['description'] ?? '', 0, 45)) ?>...</div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($prop['applicant_name']) ?></div>
                                        <div class="text-muted small font-monospace" style="font-size:0.7rem;"><?= htmlspecialchars($prop['applicant_email']) ?></div>
                                        <?php if (!empty($prop['is_uit_student'])): ?>
                                            <span class="badge bg-purple-subtle text-purple border border-purple-subtle rounded-pill px-2 py-1 mt-1" style="background:#f5f3ff; color:#7c3aed; font-size:0.65rem;">
                                                <i class="bi bi-mortarboard-fill me-1"></i> Student ID: <?= htmlspecialchars($prop['student_id_number'] ?: 'Verified') ?>
                                            </span>
                                            <?php if (!empty($prop['student_id_photo'])): ?>
                                                <a href="../../<?= htmlspecialchars($prop['student_id_photo']) ?>" target="_blank" class="badge bg-light text-primary border text-decoration-none ms-1" style="font-size:0.65rem;">
                                                    <i class="bi bi-card-image me-1"></i> ID Card
                                                </a>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-light text-secondary border rounded-pill px-2 py-1 mt-1" style="font-size:0.65rem;">External Applicant</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-secondary"><?= htmlspecialchars($prop['faculty_mentor'] ?: 'N/A') ?></td>
                                    <td class="small text-secondary font-monospace"><?= date('d M Y', strtotime($prop['created_at'])) ?></td>
                                    <td>
                                        <?php if ($prop['status'] === 'approved'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2.5 py-1">Approved</span>
                                        <?php elseif ($prop['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-2.5 py-1">Rejected</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2.5 py-1">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <?php if ($prop['status'] === 'pending'): ?>
                                            <div class="btn-group btn-group-sm">
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

    const searchInput = document.getElementById('superHeaderSearchInput');
    if (!searchInput) return;

    // Ctrl / Keyboard shortcut focus
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === '/') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });

    // Handle Enter press to redirect to clubs page search
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            const query = searchInput.value.trim();
            if (query) {
                window.location.href = 'clubs.php?search=' + encodeURIComponent(query);
            }
        }
    });
});
</script>
</body>
</html>
