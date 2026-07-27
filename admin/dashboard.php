<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: /admin/dean-login.php');
    exit;
}

$db = Database::getConnection();
$deanName  = $_SESSION['full_name'] ?? 'Dean Sir';
$firstName = explode(' ', trim($deanName))[0];

// ── Quick Stats ────────────────────────────────────────
$totalClubs   = $db->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
$activeClubs  = $db->query("SELECT COUNT(*) FROM clubs WHERE status = 'active'")->fetchColumn();
$inactiveClubs= $totalClubs - $activeClubs;
$totalEvents  = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalLeaders = $db->query("SELECT COUNT(*) FROM leadership")->fetchColumn();
$totalGallery = $db->query("SELECT COUNT(*) FROM gallery_items")->fetchColumn();
$totalUsers   = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCats    = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Upcoming events count
$upcomingEvents = $db->query("SELECT COUNT(*) FROM events WHERE event_date >= NOW() AND status NOT IN ('draft','hidden','archived','cancelled')")->fetchColumn();

// ── Recent Clubs ───────────────────────────────────────
$recentClubs = $db->query("
    SELECT c.*, cat.name as category_name
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    ORDER BY c.created_at DESC LIMIT 5
")->fetchAll();

// ── Upcoming Events ────────────────────────────────────
$nextEvents = $db->query("
    SELECT e.*, c.name as club_name
    FROM events e
    JOIN clubs c ON e.club_id = c.id
    WHERE e.event_date >= NOW() AND e.status NOT IN ('draft','hidden','archived','cancelled')
    ORDER BY e.event_date ASC LIMIT 5
")->fetchAll();

// ── Recent Activities (recent events or gallery items) ─
$recentActivity = $db->query("
    SELECT e.title, e.created_at, c.name as club_name, 'event' as type
    FROM events e
    JOIN clubs c ON e.club_id = c.id
    ORDER BY e.created_at DESC LIMIT 5
")->fetchAll();

// ── Category distribution ──────────────────────────────
$catDist = $db->query("
    SELECT cat.name, COUNT(c.id) as cnt
    FROM categories cat
    LEFT JOIN clubs c ON c.category_id = cat.id
    GROUP BY cat.id, cat.name
    ORDER BY cnt DESC
")->fetchAll();

// ── Executive Intelligence Queries ─────────────────────
$topPerformingClub = $db->query("
    SELECT c.name, c.short_name, c.logo, COUNT(e.id) as total_events
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
    SELECT c.name, c.short_name, c.logo, c.email
    FROM clubs c
    LEFT JOIN events e ON e.club_id = c.id
    WHERE e.id IS NULL OR e.created_at < DATE_SUB(NOW(), INTERVAL 60 DAY)
    GROUP BY c.id, c.name, c.short_name, c.logo, c.email
    LIMIT 5
")->fetchAll();

$pendingProposals = $db->query("
    SELECT * FROM club_proposals ORDER BY created_at DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dean Sir Super Admin Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        * { box-sizing: border-box; }
        body { background: #f1f5f9; font-family: 'Inter', system-ui, sans-serif; }

        /* ── Sidebar ── */
        .super-sidebar {
            width: 260px; min-height: 100vh; flex-shrink: 0;
            background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 60%, #0f172a 100%);
            color: #fff; display: flex; flex-direction: column;
            position: sticky; top: 0; overflow-y: auto;
            box-shadow: 4px 0 24px rgba(0,0,0,0.3);
        }
        .admin-nav-link {
            color: rgba(255,255,255,0.65); padding: 10px 14px; border-radius: 10px;
            display: flex; align-items: center; gap: 11px; text-decoration: none;
            font-weight: 500; font-size: 0.82rem; transition: all 0.2s ease; margin-bottom: 1px;
        }
        .admin-nav-link i { font-size: 1rem; width: 18px; text-align: center; flex-shrink: 0; }
        .admin-nav-link:hover { background: rgba(255,255,255,0.1); color: #fff; transform: translateX(2px); }
        .admin-nav-link.active { background: linear-gradient(135deg,#6366f1,#4f46e5); color:#fff; box-shadow: 0 4px 12px rgba(99,102,241,0.4); }
        .admin-nav-link .nav-badge { margin-left:auto; background: #ef4444; color:#fff; border-radius:20px; padding: 1px 7px; font-size:0.65rem; font-weight:700; }
        .sidebar-section-label { color: rgba(255,255,255,0.35); font-size: 0.6rem; letter-spacing: 1.5px; font-weight: 700; text-transform: uppercase; padding: 0 14px; margin: 14px 0 6px; }
        .border-white-10 { border-color: rgba(255,255,255,0.1) !important; }

        /* ── Stat Cards ── */
        .stat-card { border: none; border-radius: 16px; padding: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); transition: transform 0.2s, box-shadow 0.2s; }
        .stat-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.1); }
        .stat-icon-box { width: 46px; height: 46px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .stat-trend { font-size: 0.72rem; font-weight: 600; }
        .trend-up { color: #22c55e; }

        /* ── Content cards ── */
        .content-card { border: none; border-radius: 16px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); background: #fff; }
        .content-card .card-header-custom { padding: 18px 20px 12px; border-bottom: 1px solid #f1f5f9; }

        /* ── Quick Action Buttons ── */
        .qa-btn { border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 10px; text-align: center; text-decoration: none; background: #fff; transition: all 0.2s ease; display: block; }
        .qa-btn:hover { border-color: #6366f1; background: #f5f3ff; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(99,102,241,0.15); }
        .qa-btn i { font-size: 1.5rem; display: block; margin-bottom: 6px; }
        .qa-btn span { font-size: 0.72rem; font-weight: 600; color: #334155; display: block; }

        /* ── Activity items ── */
        .activity-row { display: flex; align-items: center; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f8fafc; }
        .activity-row:last-child { border-bottom: none; }
        .activity-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }

        /* ── Scrollbar ── */
        .super-sidebar::-webkit-scrollbar { width: 4px; }
        .super-sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.15); border-radius: 4px; }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">

    <!-- SIDEBAR -->
    <aside class="super-sidebar d-none d-md-flex flex-column">
        <!-- Brand -->
        <div class="p-4 border-bottom border-white-10">
            <div class="d-flex align-items-center gap-3 mb-1">
                <img src="/assets/United Logo.webp" alt="ClubHub" style="height:28px;opacity:0.9;">
                <div>
                    <div class="fw-bold text-white lh-1" style="font-size:0.95rem;">ClubHub</div>
                    <div class="text-white-50" style="font-size:0.58rem;letter-spacing:1.5px;">DEAN SIR PORTAL</div>
                </div>
            </div>
            <div class="text-white-50 mt-2" style="font-size:0.68rem;">United Institute of Technology</div>
        </div>

        <!-- Navigation -->
        <nav class="px-3 py-3 flex-grow-1">
            <a href="/admin/dashboard.php" class="admin-nav-link active">
                <i class="bi bi-speedometer2"></i> Executive Overview
            </a>

            <div class="sidebar-section-label">Campus Governance</div>
            <a href="/admin/super/clubs.php" class="admin-nav-link">
                <i class="bi bi-trophy"></i> Manage Clubs
            </a>
            <a href="/admin/super/categories.php" class="admin-nav-link">
                <i class="bi bi-grid-3x3-gap"></i> Categories
            </a>
            <a href="/admin/super/users.php" class="admin-nav-link">
                <i class="bi bi-person-gear"></i> Club Accounts
            </a>

            <div class="sidebar-section-label">Supervision & Audit</div>
            <a href="/admin/super/messages.php" class="admin-nav-link">
                <i class="bi bi-inbox-fill"></i> Help Desk Messages
            </a>
            <a href="/admin/super/audit-logs.php" class="admin-nav-link">
                <i class="bi bi-journal-text"></i> Security Audit Logs
            </a>
        </nav>

        <!-- User Profile footer -->
        <div class="p-3 border-top border-white-10">
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="rounded-circle bg-indigo text-white d-flex align-items-center justify-content-center fw-bold" style="width:34px;height:34px;font-size:0.8rem;background:#6366f1;">
                    D
                </div>
                <div class="overflow-hidden">
                    <div class="fw-semibold text-white small text-truncate" style="max-width:130px;"><?= e($deanName) ?></div>
                    <div class="text-white-50 small" style="font-size:0.65rem;">Dean of Student Affairs</div>
                </div>
            </div>
            <a href="/admin/logout.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold" style="font-size:0.75rem;">
                <i class="bi bi-box-arrow-right me-1"></i> Sign Out
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-grow-1 d-flex flex-column min-w-0">

        <!-- Top Header Bar -->
        <header class="bg-white border-bottom px-4 py-3 d-flex align-items-center justify-content-between sticky-top z-3">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-light d-md-none border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <div>
                    <h5 class="fw-bold mb-0 text-dark">Dean Sir Super Admin Portal</h5>
                    <span class="text-muted small">United Institute of Technology – Student Affairs Directorate</span>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="/index.html" class="btn btn-sm btn-outline-secondary rounded-pill px-3" target="_blank">
                    <i class="bi bi-globe me-1"></i> Public Site
                </a>
            </div>
        </header>

        <!-- Main Body -->
        <div class="p-4 p-md-5 flex-grow-1">

            <!-- Hero Welcome Card -->
            <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);">
                <div class="row align-items-center g-3">
                    <div class="col-md-8">
                        <span class="badge bg-indigo text-white rounded-pill px-3 py-1 fw-bold mb-2 small" style="background:#6366f1;"><i class="bi bi-shield-check me-1"></i> DEAN OF STUDENT AFFAIRS</span>
                        <h2 class="fw-bold mb-2">Welcome, <?= e($firstName) ?>! 👑</h2>
                        <p class="text-white-80 mb-0">Overseeing <strong><?= $totalClubs ?> Active Chapters</strong>, campus events, funding proposals, and leadership appointments.</p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="/admin/super/clubs.php" class="btn btn-light rounded-pill px-4 py-2-5 fw-bold text-dark shadow-sm">
                            <i class="bi bi-gear-fill me-1"></i> Governance Portal
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-secondary small fw-semibold">ACTIVE CLUBS</span>
                            <div class="stat-icon-box bg-primary-subtle text-primary"><i class="bi bi-trophy-fill"></i></div>
                        </div>
                        <h3 class="fw-bold text-dark mb-1"><?= $totalClubs ?></h3>
                        <span class="stat-trend trend-up"><i class="bi bi-check-circle-fill me-1"></i>100% Verified</span>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="stat-card bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-secondary small fw-semibold">TOTAL EVENTS</span>
                            <div class="stat-icon-box bg-success-subtle text-success"><i class="bi bi-calendar-event-fill"></i></div>
                        </div>
                        <h3 class="fw-bold text-dark mb-1"><?= $totalEvents ?></h3>
                        <span class="stat-trend text-success"><i class="bi bi-calendar-check me-1"></i><?= $upcomingEvents ?> Upcoming</span>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="stat-card bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-secondary small fw-semibold">CORE LEADERS</span>
                            <div class="stat-icon-box bg-purple-subtle text-purple"><i class="bi bi-person-badge-fill"></i></div>
                        </div>
                        <h3 class="fw-bold text-dark mb-1"><?= $totalLeaders ?></h3>
                        <span class="stat-trend text-primary"><i class="bi bi-people-fill me-1"></i>11 Chapters</span>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="stat-card bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="text-secondary small fw-semibold">PROPOSALS</span>
                            <div class="stat-icon-box bg-warning-subtle text-warning"><i class="bi bi-inbox-fill"></i></div>
                        </div>
                        <h3 class="fw-bold text-dark mb-1"><?= count($pendingProposals) ?></h3>
                        <span class="stat-trend text-warning"><i class="bi bi-clock me-1"></i>Pending Review</span>
                    </div>
                </div>
            </div>

            <!-- Recent Clubs & Proposals Grid -->
            <div class="row g-4 mb-4">
                <div class="col-lg-7">
                    <div class="content-card">
                        <div class="card-header-custom d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Institutional Chapters Overview</h6>
                                <span class="text-muted small">Registered student clubs & lead accounts</span>
                            </div>
                            <a href="/admin/super/clubs.php" class="btn btn-sm btn-outline-primary rounded-pill fw-bold">View All</a>
                        </div>
                        <div class="p-3">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="small text-secondary">
                                            <th>CHAPTER NAME</th>
                                            <th>CATEGORY</th>
                                            <th>STATUS</th>
                                            <th class="text-end">ACTION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentClubs as $rc): ?>
                                            <tr>
                                                <td class="fw-bold text-dark">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="<?= e($rc['logo'] ?: '../assets/United Logo.webp') ?>" class="rounded-2" style="width:32px;height:32px;object-fit:cover;" alt="">
                                                        <span><?= e($rc['name']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="small text-secondary"><?= e($rc['category_name']) ?></td>
                                                <td><span class="badge bg-success-subtle text-success border rounded-pill px-2.5 py-1 small">Active</span></td>
                                                <td class="text-end">
                                                    <a href="/club-detail.html?id=<?= e($rc['id']) ?>" target="_blank" class="btn btn-sm btn-light rounded-circle" title="View Public Page"><i class="bi bi-eye text-primary"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="content-card">
                        <div class="card-header-custom d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Pending Proposals</h6>
                                <span class="text-muted small">New event & club requests</span>
                            </div>
                        </div>
                        <div class="p-3">
                            <?php if (empty($pendingProposals)): ?>
                                <div class="text-center py-4 text-muted small">
                                    <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                                    No pending proposals requiring review.
                                </div>
                            <?php else: ?>
                                <?php foreach ($pendingProposals as $prop): ?>
                                    <div class="p-3 border rounded-3 mb-2 bg-light">
                                        <div class="fw-bold text-dark small mb-1"><?= e($prop['proposed_title']) ?></div>
                                        <div class="d-flex justify-content-between align-items-center text-muted small">
                                            <span>By: <?= e($prop['applicant_name']) ?></span>
                                            <span class="badge bg-warning text-dark rounded-pill">Pending</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
