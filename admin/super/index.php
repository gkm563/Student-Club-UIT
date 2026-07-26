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
    <title>Dean's Portal – Overview | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
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

        /* ── Club bar ── */
        .club-bar-wrap { background: #f1f5f9; border-radius: 6px; height: 6px; overflow: hidden; margin-top: 4px; }
        .club-bar-fill { height: 100%; border-radius: 6px; background: linear-gradient(90deg, #6366f1, #a855f7); }
    </style>
</head>
<body>

<div class="d-flex" style="min-height:100vh;">

    <!-- ══════════════════ SIDEBAR ══════════════════ -->
    <aside class="super-sidebar d-none d-md-flex flex-column">

        <!-- Brand -->
        <div class="p-4 border-bottom border-white-10">
            <div class="d-flex align-items-center gap-3 mb-1">
                <img src="/assets/United Logo.webp" alt="ClubHub" style="height:28px;opacity:0.9;">
                <div>
                    <div class="fw-bold text-white lh-1" style="font-size:0.95rem;">ClubHub</div>
                    <div class="text-white-50" style="font-size:0.58rem;letter-spacing:1.5px;">ADMIN PORTAL</div>
                </div>
            </div>
            <div class="text-white-50 mt-2" style="font-size:0.68rem;">United Institute of Technology</div>
        </div>

        <!-- Navigation -->
        <nav class="px-3 py-3 flex-grow-1">

            <a href="/admin/super/index.php" class="admin-nav-link active">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>

            <div class="sidebar-section-label">Club Management</div>
            <a href="/admin/super/clubs.php" class="admin-nav-link">
                <i class="bi bi-trophy"></i> Clubs
                <i class="bi bi-chevron-right ms-auto" style="font-size:0.65rem;opacity:0.5;"></i>
            </a>
            <a href="/admin/super/categories.php" class="admin-nav-link">
                <i class="bi bi-grid-3x3-gap"></i> Categories
            </a>
            <a href="/admin/super/users.php" class="admin-nav-link">
                <i class="bi bi-person-gear"></i> Club Admins
            </a>

            <div class="sidebar-section-label">Content Management</div>
            <a href="/admin/super/clubs.php" class="admin-nav-link">
                <i class="bi bi-calendar-event"></i> Events
            </a>
            <a href="/admin/super/logs.php" class="admin-nav-link">
                <i class="bi bi-images"></i> Gallery
            </a>
            <a href="/admin/super/messages.php" class="admin-nav-link">
                <i class="bi bi-megaphone"></i> Announcements
            </a>

            <div class="sidebar-section-label">User & Access</div>
            <a href="/admin/super/users.php" class="admin-nav-link">
                <i class="bi bi-people"></i> Users
            </a>
            <a href="/admin/super/logs.php" class="admin-nav-link">
                <i class="bi bi-shield-check"></i> Roles & Permissions
            </a>
            <a href="/admin/super/audit-logs.php" class="admin-nav-link">
                <i class="bi bi-bar-chart-line"></i> Reports & Analytics
            </a>

            <div class="sidebar-section-label">System & Settings</div>
            <a href="/admin/super/logs.php" class="admin-nav-link">
                <i class="bi bi-gear"></i> Settings
            </a>
            <a href="/" target="_blank" class="admin-nav-link">
                <i class="bi bi-globe2"></i> SEO & Website
            </a>
            <a href="/admin/super/audit-logs.php" class="admin-nav-link">
                <i class="bi bi-journal-text"></i> System Logs
            </a>
            <a href="/admin/super/logs.php" class="admin-nav-link">
                <i class="bi bi-cloud-arrow-up"></i> Backup & Restore
            </a>

            <div class="mt-3 mb-1">
                <a href="/" target="_blank" class="admin-nav-link" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);">
                    <i class="bi bi-box-arrow-up-right"></i> View Website
                </a>
            </div>
        </nav>

        <!-- Bottom: Dean Profile -->
        <div class="p-3 border-top border-white-10">
            <div class="d-flex align-items-center gap-3">
                <div class="position-relative">
                    <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                         style="width:38px;height:38px;background:linear-gradient(135deg,#6366f1,#a855f7);font-size:0.9rem;flex-shrink:0;">
                        <?= strtoupper(substr($firstName, 0, 1)) ?>
                    </div>
                    <span class="position-absolute bottom-0 end-0 rounded-circle" style="width:9px;height:9px;background:#22c55e;border:2px solid #0f172a;"></span>
                </div>
                <div class="flex-grow-1 overflow-hidden">
                    <div class="text-white fw-semibold text-truncate" style="font-size:0.82rem;"><?= htmlspecialchars($deanName) ?></div>
                    <div class="text-white-50" style="font-size:0.65rem;">Dean (Student Affairs)</div>
                </div>
                <a href="/admin/logout.php" class="text-white-50 text-decoration-none" title="Sign Out">
                    <i class="bi bi-box-arrow-right" style="font-size:1rem;"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- ══════════════════ MAIN ══════════════════ -->
    <div class="flex-grow-1 d-flex flex-column" style="min-width:0;">

        <!-- Top Header -->
        <header class="bg-white border-bottom px-4 py-3 d-flex align-items-center justify-content-between sticky-top" style="z-index:900;box-shadow:0 1px 8px rgba(0,0,0,0.06);">
            <div>
                <h5 class="fw-bold mb-0 text-dark">Welcome back, <?= htmlspecialchars($firstName) ?>! 👋</h5>
                <p class="text-muted mb-0" style="font-size:0.75rem;">Here's an overview of your college club ecosystem.</p>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="input-group input-group-sm" style="width:220px;">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control bg-light border-start-0 ps-0" placeholder="Search clubs, events, users…" style="font-size:0.8rem;">
                    <span class="input-group-text bg-light text-muted" style="font-size:0.65rem;">Ctrl /</span>
                </div>
                <div class="position-relative">
                    <button class="btn btn-light rounded-circle p-2 border" style="width:38px;height:38px;"><i class="bi bi-bell fs-6"></i></button>
                    <span class="position-absolute top-0 end-0 badge bg-danger rounded-pill" style="font-size:0.55rem;padding:3px 5px;">12</span>
                </div>
                <div class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 bg-white" style="font-size:0.78rem;cursor:pointer;">
                    <i class="bi bi-calendar3 text-muted"></i>
                    <span class="fw-semibold text-dark"><?= date('d M Y') ?></span>
                    <i class="bi bi-chevron-down text-muted" style="font-size:0.65rem;"></i>
                </div>
            </div>
        </header>

        <!-- Page Body -->
        <div class="p-4 p-md-5 flex-grow-1">

            <!-- ── 5 Stat Cards ── -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-lg">
                    <div class="stat-card bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="stat-icon-box" style="background:#eff6ff;color:#3b82f6;"><i class="bi bi-trophy-fill"></i></div>
                            <div class="text-end">
                                <div class="stat-trend trend-up"><i class="bi bi-arrow-up-short"></i>12% this month</div>
                            </div>
                        </div>
                        <div class="fw-bold" style="font-size:1.75rem;line-height:1;"><?= $totalClubs ?></div>
                        <div class="text-muted mt-1" style="font-size:0.78rem;">Total Clubs</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-card bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="stat-icon-box" style="background:#f0fdf4;color:#22c55e;"><i class="bi bi-calendar-event-fill"></i></div>
                            <div class="text-end">
                                <div class="stat-trend trend-up"><i class="bi bi-arrow-up-short"></i>18% this month</div>
                            </div>
                        </div>
                        <div class="fw-bold" style="font-size:1.75rem;line-height:1;"><?= $totalEvents ?></div>
                        <div class="text-muted mt-1" style="font-size:0.78rem;">Total Events</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-card bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="stat-icon-box" style="background:#fefce8;color:#f59e0b;"><i class="bi bi-people-fill"></i></div>
                            <div class="text-end">
                                <div class="stat-trend trend-up"><i class="bi bi-arrow-up-short"></i>22% this month</div>
                            </div>
                        </div>
                        <div class="fw-bold" style="font-size:1.75rem;line-height:1;"><?= $totalLeaders ?></div>
                        <div class="text-muted mt-1" style="font-size:0.78rem;">Total Members</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-card bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="stat-icon-box" style="background:#f0f9ff;color:#06b6d4;"><i class="bi bi-person-fill-check"></i></div>
                            <div class="text-end">
                                <div class="stat-trend trend-up"><i class="bi bi-arrow-up-short"></i>16% this month</div>
                            </div>
                        </div>
                        <div class="fw-bold" style="font-size:1.75rem;line-height:1;"><?= $totalUsers ?></div>
                        <div class="text-muted mt-1" style="font-size:0.78rem;">Total Users</div>
                    </div>
                </div>
                <div class="col-6 col-lg">
                    <div class="stat-card bg-white">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="stat-icon-box" style="background:#fdf4ff;color:#a855f7;"><i class="bi bi-images"></i></div>
                            <div class="text-end">
                                <div class="stat-trend trend-up"><i class="bi bi-arrow-up-short"></i>9% this month</div>
                            </div>
                        </div>
                        <div class="fw-bold" style="font-size:1.75rem;line-height:1;"><?= $totalGallery ?></div>
                        <div class="text-muted mt-1" style="font-size:0.78rem;">Gallery Images</div>
                    </div>
                </div>
            </div>

            <!-- ── Executive Dean Intelligence & Audit Row ── -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="border-left: 4px solid #3b82f6 !important;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-3 text-primary bg-primary-subtle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-award-fill fs-4"></i>
                            </div>
                            <div>
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-2 py-1 mb-1" style="font-size: 0.65rem;">Top Performing Club</span>
                                <h6 class="fw-bold text-dark mb-0 text-truncate"><?= htmlspecialchars($topPerformingClub['name'] ?? 'GDGOC UIT') ?></h6>
                                <p class="small text-muted mb-0" style="font-size: 0.72rem;"><?= (int)($topPerformingClub['total_events'] ?? 0) ?> Official Events Organized</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="border-left: 4px solid #10b981 !important;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-3 text-success bg-success-subtle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-people-fill fs-4"></i>
                            </div>
                            <div>
                                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1 mb-1" style="font-size: 0.65rem;">Largest Club Roster</span>
                                <h6 class="fw-bold text-dark mb-0 text-truncate"><?= htmlspecialchars($largestClub['name'] ?? 'GFG SC UIT') ?></h6>
                                <p class="small text-muted mb-0" style="font-size: 0.72rem;"><?= (int)($largestClub['member_count'] ?? 0) ?> Active Core Officers</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="border-left: 4px solid #ef4444 !important;">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-circle p-3 text-danger bg-danger-subtle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                            </div>
                            <div>
                                <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1 mb-1" style="font-size: 0.65rem;">Audit Alert</span>
                                <h6 class="fw-bold text-dark mb-0 text-truncate"><?= count($dormantClubs) ?> Inactive Clubs</h6>
                                <p class="small text-muted mb-0" style="font-size: 0.72rem;">No event logged in last 60 days</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Pending Club & Event Proposals Table ── -->
            <div class="card border-0 shadow-sm rounded-4 mb-4 bg-white">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-file-earmark-text text-primary me-2"></i>Pending Club & Event Proposals</h6>
                        <span class="text-muted small" style="font-size:0.72rem;">Submitted by students and faculty for Dean Student Welfare approval</span>
                    </div>
                    <span class="badge bg-purple-subtle text-purple px-3 py-1 rounded-pill" style="background:#f5f3ff; color:#7c3aed; font-size:0.7rem;"><?= count($pendingProposals) ?> Proposals</span>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0" style="font-size:0.82rem;">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Proposed Title</th>
                                <th>Applicant</th>
                                <th>Faculty Mentor</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($pendingProposals)): ?>
                                <tr><td colspan="6" class="text-center py-3 text-muted small">No pending proposals submitted yet.</td></tr>
                            <?php else: foreach ($pendingProposals as $prop): ?>
                                <tr>
                                    <td>
                                        <span class="badge rounded-pill px-2 py-1 <?= $prop['proposal_type'] === 'new_club' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success' ?>">
                                            <?= $prop['proposal_type'] === 'new_club' ? 'New Club' : 'New Event' ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($prop['proposed_title']) ?></td>
                                    <td>
                                        <div><?= htmlspecialchars($prop['applicant_name']) ?></div>
                                        <div class="text-muted small" style="font-size:0.7rem;"><?= htmlspecialchars($prop['applicant_email']) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($prop['faculty_mentor'] ?: 'N/A') ?></td>
                                    <td><?= date('d M Y', strtotime($prop['created_at'])) ?></td>
                                    <td>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 py-1">
                                            <?= ucfirst($prop['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ── Row 2: Top Clubs + Upcoming Events + Category Distribution ── -->
            <div class="row g-4 mb-4">

                <!-- Top Performing Clubs -->
                <div class="col-lg-4">
                    <div class="content-card h-100">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Top Performing Clubs</h6>
                                <p class="text-muted mb-0" style="font-size:0.72rem;">By number of events</p>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2" style="font-size:0.65rem;">This Month</span>
                        </div>
                        <div class="p-4">
                            <?php if(empty($topClubs)): ?>
                                <div class="text-center py-4 text-muted small">No clubs found yet.</div>
                            <?php else: $rank = 1; foreach ($topClubs as $tc): ?>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="fw-bold text-muted" style="width:18px;font-size:0.8rem;"><?= $rank++ ?></div>
                                <img src="<?= htmlspecialchars($tc['logo'] ?? '/assets/United Logo.webp') ?>" alt=""
                                     style="width:32px;height:32px;border-radius:8px;object-fit:cover;border:1px solid #e2e8f0;"
                                     onerror="this.src='/assets/United Logo.webp'">
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-semibold text-dark text-truncate" style="font-size:0.82rem;"><?= htmlspecialchars($tc['name']) ?></div>
                                    <div class="club-bar-wrap mt-1">
                                        <?php $maxEvents = max(1, $topClubs[0]['event_count']); $pct = round(($tc['event_count']/$maxEvents)*100); ?>
                                        <div class="club-bar-fill" style="width:<?= $pct ?>%;"></div>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="fw-bold text-dark" style="font-size:0.82rem;"><?= $tc['event_count'] ?> <small class="text-muted fw-normal">evts</small></div>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="col-lg-5">
                    <div class="content-card h-100">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Upcoming Events</h6>
                                <p class="text-muted mb-0" style="font-size:0.72rem;"><?= $upcomingEvents ?> events scheduled</p>
                            </div>
                            <a href="/admin/super/clubs.php" class="text-primary small fw-semibold text-decoration-none">View Calendar →</a>
                        </div>
                        <div class="p-4">
                            <?php if(empty($nextEvents)): ?>
                                <div class="text-center py-4 text-muted small">No upcoming events found.</div>
                            <?php else: foreach($nextEvents as $ne):
                                $d = new DateTime($ne['event_date']);
                                $statusColors = ['upcoming'=>'primary','ongoing'=>'success','completed'=>'secondary'];
                                $sc = $statusColors[$ne['status']] ?? 'secondary';
                            ?>
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="text-center flex-shrink-0 rounded-3" style="background:#eff6ff;min-width:44px;padding:8px 6px;">
                                    <div class="fw-bold text-primary lh-1" style="font-size:1rem;"><?= $d->format('d') ?></div>
                                    <div class="text-primary" style="font-size:0.58rem;letter-spacing:0.5px;"><?= strtoupper($d->format('M')) ?></div>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-semibold text-dark text-truncate" style="font-size:0.82rem;"><?= htmlspecialchars($ne['title']) ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;">
                                        <i class="bi bi-clock me-1"></i><?= $d->format('h:i A') ?>
                                        <?php if($ne['venue']): ?>&nbsp;<i class="bi bi-geo-alt ms-2 me-1"></i><?= htmlspecialchars($ne['venue']) ?><?php endif; ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.68rem;"><i class="bi bi-shield-fill me-1 text-primary" style="font-size:9px;"></i><?= htmlspecialchars($ne['club_name']) ?></div>
                                </div>
                                <span class="badge bg-<?= $sc ?>-subtle text-<?= $sc ?> border border-<?= $sc ?>-subtle rounded-pill px-2 py-1 flex-shrink-0" style="font-size:0.65rem;"><?= ucfirst($ne['status']) ?></span>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Club Distribution by Category -->
                <div class="col-lg-3">
                    <div class="content-card h-100">
                        <div class="card-header-custom">
                            <h6 class="fw-bold mb-0 text-dark">Club Distribution</h6>
                            <p class="text-muted mb-0" style="font-size:0.72rem;">By category</p>
                        </div>
                        <div class="p-4">
                            <?php
                            $palette = ['#6366f1','#f59e0b','#22c55e','#06b6d4','#a855f7','#ef4444','#ec4899','#84cc16'];
                            $pi = 0;
                            foreach($catDist as $cd):
                                $pct = $totalClubs > 0 ? round(($cd['cnt']/$totalClubs)*100,1) : 0;
                                $color = $palette[$pi % count($palette)]; $pi++;
                            ?>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="rounded-circle flex-shrink-0" style="width:10px;height:10px;background:<?= $color ?>;"></span>
                                <span class="text-dark small text-truncate flex-grow-1" style="font-size:0.78rem;"><?= htmlspecialchars($cd['name']) ?></span>
                                <span class="fw-bold text-dark" style="font-size:0.78rem;"><?= $cd['cnt'] ?></span>
                                <span class="text-muted" style="font-size:0.68rem;">(<?= $pct ?>%)</span>
                            </div>
                            <?php endforeach; ?>
                            <div class="border-top mt-3 pt-3 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-dark"><?= $totalClubs ?> <span class="text-muted fw-normal small">Total Clubs</span></span>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2" style="font-size:0.65rem;"><?= $totalCats ?> Categories</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Row 3: Recent Clubs + Recent Activity + Quick Actions ── -->
            <div class="row g-4 mb-4">

                <!-- Recently Registered Clubs -->
                <div class="col-lg-5">
                    <div class="content-card">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">Recently Registered Clubs</h6>
                            <a href="/admin/super/clubs.php" class="text-primary small fw-semibold text-decoration-none">View All →</a>
                        </div>
                        <?php if(empty($recentClubs)): ?>
                            <div class="p-4 text-center text-muted small">
                                <i class="bi bi-folder-plus d-block fs-2 mb-2 opacity-50"></i>
                                No clubs registered yet.
                                <a href="/admin/super/clubs.php" class="fw-bold text-primary">Add first club →</a>
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead style="background:#f8fafc;">
                                    <tr class="small text-muted">
                                        <th class="ps-4 py-3 fw-semibold">Club Name</th>
                                        <th class="py-3 fw-semibold">Category</th>
                                        <th class="py-3 fw-semibold">Status</th>
                                        <th class="py-3 fw-semibold text-end pe-4">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach($recentClubs as $rc): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= htmlspecialchars($rc['logo'] ?? '/assets/United Logo.webp') ?>" alt=""
                                                     style="width:32px;height:32px;border-radius:8px;object-fit:cover;border:1px solid #e2e8f0;"
                                                     onerror="this.src='/assets/United Logo.webp'">
                                                <div class="fw-semibold text-dark" style="font-size:0.82rem;"><?= htmlspecialchars($rc['name']) ?></div>
                                            </div>
                                        </td>
                                        <td class="py-3">
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1" style="font-size:0.65rem;"><?= htmlspecialchars($rc['category_name']) ?></span>
                                        </td>
                                        <td class="py-3">
                                            <?php $sc2 = $rc['status']==='active' ? 'success' : 'warning'; ?>
                                            <span class="badge bg-<?= $sc2 ?>-subtle text-<?= $sc2 ?> border border-<?= $sc2 ?>-subtle rounded-pill px-2 py-1" style="font-size:0.65rem;"><?= ucfirst($rc['status']) ?></span>
                                        </td>
                                        <td class="py-3 text-end pe-4">
                                            <a href="/admin/super/clubs.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-semibold" style="font-size:0.72rem;">Manage</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="col-lg-4">
                    <div class="content-card">
                        <div class="card-header-custom d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">Recent Activities</h6>
                            <a href="/admin/super/logs.php" class="text-primary small fw-semibold text-decoration-none">View All →</a>
                        </div>
                        <div class="p-4">
                            <?php if(empty($recentActivity)): ?>
                                <div class="text-center py-4 text-muted small">No activity yet.</div>
                            <?php else: foreach($recentActivity as $ra):
                                $created = new DateTime($ra['created_at']);
                                $diff = (new DateTime())->diff($created);
                                $ago = $diff->d > 0 ? $diff->d.'d ago' : ($diff->h > 0 ? $diff->h.'h ago' : $diff->i.'m ago');
                            ?>
                            <div class="activity-row">
                                <span class="activity-dot" style="background:#6366f1;"></span>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="text-dark text-truncate" style="font-size:0.8rem;font-weight:500;">
                                        <?= htmlspecialchars($ra['title']) ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.68rem;">
                                        By <?= htmlspecialchars($ra['club_name']) ?>
                                    </div>
                                </div>
                                <span class="text-muted flex-shrink-0" style="font-size:0.68rem;"><?= $ago ?></span>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-3">
                    <div class="content-card">
                        <div class="card-header-custom">
                            <h6 class="fw-bold mb-0 text-dark">Quick Actions</h6>
                        </div>
                        <div class="p-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="/admin/super/clubs.php" class="qa-btn">
                                        <i class="bi bi-plus-circle-fill text-primary"></i>
                                        <span>Create New Club</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/admin/super/categories.php" class="qa-btn">
                                        <i class="bi bi-grid-3x3-gap-fill text-success"></i>
                                        <span>Add Category</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/admin/super/messages.php" class="qa-btn">
                                        <i class="bi bi-megaphone-fill text-warning"></i>
                                        <span>Announcement</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/admin/super/audit-logs.php" class="qa-btn">
                                        <i class="bi bi-bar-chart-fill text-purple" style="color:#a855f7!important;"></i>
                                        <span>Generate Report</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/admin/super/users.php" class="qa-btn">
                                        <i class="bi bi-person-plus-fill text-info"></i>
                                        <span>Manage Users</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="/admin/super/clubs.php" class="qa-btn">
                                        <i class="bi bi-calendar-check-fill text-danger"></i>
                                        <span>View All Events</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Quick Insights Bottom Bar ── -->
            <div class="content-card p-4">
                <div class="row g-4 align-items-center">
                    <div class="col-auto">
                        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-lightning-fill text-warning me-2"></i>Quick Insights</h6>
                    </div>
                    <div class="col">
                        <div class="d-flex flex-wrap gap-4">
                            <div>
                                <span class="text-muted small">Active Clubs:</span>
                                <span class="fw-bold text-success ms-1"><?= $activeClubs ?></span>
                            </div>
                            <div>
                                <span class="text-muted small">Inactive Clubs:</span>
                                <span class="fw-bold text-danger ms-1"><?= $inactiveClubs ?></span>
                            </div>
                            <div>
                                <span class="text-muted small">Upcoming Events:</span>
                                <span class="fw-bold text-primary ms-1"><?= $upcomingEvents ?></span>
                            </div>
                            <div>
                                <span class="text-muted small">Total Categories:</span>
                                <span class="fw-bold text-dark ms-1"><?= $totalCats ?></span>
                            </div>
                            <div>
                                <span class="text-muted small">Gallery Photos:</span>
                                <span class="fw-bold text-dark ms-1"><?= $totalGallery ?></span>
                            </div>
                            <div>
                                <span class="text-muted small">Total Users:</span>
                                <span class="fw-bold text-dark ms-1"><?= $totalUsers ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- end page body -->
    </div><!-- end main -->
</div><!-- end d-flex -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
