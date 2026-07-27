<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: dean-login.php');
    exit;
}

$db = Database::getConnection();
$deanName  = $_SESSION['full_name'] ?? 'Prof. Sanjay Srivastava';
$firstName = explode(' ', trim($deanName))[0];

// ── Quick Stats Queries ──────────────────────────────────────
$totalClubs    = $db->query("SELECT COUNT(*) FROM clubs")->fetchColumn();
$activeClubs   = $db->query("SELECT COUNT(*) FROM clubs WHERE status = 'active'")->fetchColumn();
$totalEvents   = $db->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalLeaders  = $db->query("SELECT COUNT(*) FROM leadership")->fetchColumn();
$totalGallery  = $db->query("SELECT COUNT(*) FROM gallery_items")->fetchColumn();
$totalUsers    = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalCats     = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$unreadMsgs    = $db->query("SELECT COUNT(*) FROM contact_messages WHERE is_read = 0")->fetchColumn();

// Upcoming events count
$upcomingEvents = $db->query("SELECT COUNT(*) FROM events WHERE event_date >= NOW() AND status NOT IN ('draft','hidden','archived','cancelled')")->fetchColumn();

// ── Recent Registered Clubs ──────────────────────────────────
$recentClubs = $db->query("
    SELECT c.*, cat.name as category_name, u.email as admin_email, u.full_name as admin_name
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN club_admins ca ON ca.club_id = c.id
    LEFT JOIN users u ON ca.user_id = u.id
    ORDER BY c.created_at DESC LIMIT 6
")->fetchAll();

// ── Pending Proposals ────────────────────────────────────────
$pendingProposals = $db->query("
    SELECT * FROM club_proposals ORDER BY created_at DESC LIMIT 5
")->fetchAll();

// ── Recent Security Logs ─────────────────────────────────────
$recentLogs = $db->query("
    SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Overview | Dean Sir Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --dean-sidebar-width: 270px;
            --dean-primary: #4f46e5;
            --dean-bg: #f8fafc;
        }
        body {
            background-color: var(--dean-bg);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            overflow-x: hidden;
        }
        .super-sidebar {
            width: var(--dean-sidebar-width);
            background: linear-gradient(180deg, #0f172a 0%, #1e1b4b 60%, #0f172a 100%);
            min-height: 100vh;
            transition: all 0.3s ease;
            box-shadow: 4px 0 24px rgba(0,0,0,0.3);
        }
        .admin-nav-link {
            color: rgba(255,255,255,0.72);
            padding: 12px 16px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            margin-bottom: 3px;
        }
        .admin-nav-link i {
            font-size: 1.15rem;
            width: 22px;
            text-align: center;
            flex-shrink: 0;
        }
        .admin-nav-link:hover {
            background: rgba(255,255,255,0.12);
            color: #ffffff;
            transform: translateX(3px);
        }
        .admin-nav-link.active {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);
        }
        .sidebar-section-label {
            color: rgba(255,255,255,0.38);
            font-size: 0.65rem;
            letter-spacing: 1.5px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0 14px;
            margin: 18px 0 6px;
        }
        .stat-card {
            border: none;
            border-radius: 20px;
            background: #ffffff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0,0,0,0.06);
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        @media (max-width: 991.98px) {
            .super-sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                z-index: 1050;
                height: 100vh;
            }
            .super-sidebar.show {
                left: 0;
            }
            .sidebar-backdrop {
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background: rgba(15, 23, 42, 0.5);
                backdrop-filter: blur(4px);
                z-index: 1040;
                display: none;
            }
            .sidebar-backdrop.show {
                display: block;
            }
        }
    </style>
</head>
<body>

<!-- Mobile Header Bar (< 992px) -->
<header class="d-lg-none bg-dark text-white p-3 d-flex align-items-center justify-content-between shadow-sm sticky-top" style="background: #0f172a !important;">
    <div class="d-flex align-items-center gap-2">
        <button class="btn btn-outline-light btn-sm rounded-circle p-2" type="button" id="deanSidebarToggle" aria-label="Toggle Sidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <div class="d-flex align-items-center gap-2">
            <img src="../assets/United Logo.webp" style="height: 28px; width: auto;" alt="United Logo">
            <span class="fw-bold text-white small">Dean Sir Portal</span>
        </div>
    </div>
    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small">SUPER ADMIN</span>
</header>

<div class="sidebar-backdrop" id="deanSidebarBackdrop"></div>

<div class="d-flex min-vh-100">

    <!-- SIDEBAR DRAWER -->
    <aside class="super-sidebar p-3 p-md-4 d-flex flex-column justify-content-between flex-shrink-0 shadow-lg" id="deanSidebar">
        <div>
            <!-- Brand Header -->
            <div class="d-flex align-items-center justify-content-between mb-4 pb-3 border-bottom border-white-10">
                <div class="d-flex align-items-center gap-3">
                    <img src="../assets/United Logo.webp" alt="ClubHub" style="height:34px; width:auto; object-fit:contain;">
                    <div>
                        <div class="fw-bold text-white lh-1" style="font-size:0.95rem;">ClubHub UIT</div>
                        <div class="text-white-50" style="font-size:0.6rem;letter-spacing:1.5px;">DEAN SIR PORTAL</div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white d-lg-none" id="deanSidebarClose" aria-label="Close"></button>
            </div>

            <!-- Navigation Links -->
            <nav class="nav flex-column gap-1">
                <a href="dashboard.php" class="admin-nav-link active">
                    <i class="bi bi-speedometer2"></i> Executive Overview
                </a>

                <div class="sidebar-section-label">Campus Governance</div>
                <a href="super/clubs.php" class="admin-nav-link">
                    <i class="bi bi-trophy"></i> Manage Clubs
                    <span class="badge bg-primary rounded-pill ms-auto small"><?= $totalClubs ?></span>
                </a>
                <a href="super/categories.php" class="admin-nav-link">
                    <i class="bi bi-grid-3x3-gap"></i> Categories
                    <span class="badge bg-secondary rounded-pill ms-auto small"><?= $totalCats ?></span>
                </a>
                <a href="super/users.php" class="admin-nav-link">
                    <i class="bi bi-person-gear"></i> Club Accounts
                    <span class="badge bg-info text-dark rounded-pill ms-auto small"><?= $totalUsers ?></span>
                </a>

                <div class="sidebar-section-label">Supervision & Audit</div>
                <a href="super/messages.php" class="admin-nav-link">
                    <i class="bi bi-inbox-fill"></i> Help Desk Messages
                    <?php if ($unreadMsgs > 0): ?>
                        <span class="badge bg-danger rounded-pill ms-auto small"><?= $unreadMsgs ?></span>
                    <?php endif; ?>
                </a>
                <a href="super/audit-logs.php" class="admin-nav-link">
                    <i class="bi bi-journal-text"></i> Security Audit Logs
                </a>

                <div class="sidebar-section-label">Quick Links</div>
                <a href="../index.html" target="_blank" class="admin-nav-link text-info">
                    <i class="bi bi-box-arrow-up-right"></i> View Campus Website
                </a>
            </nav>
        </div>

        <!-- User Profile Footer -->
        <div class="pt-3 border-top border-white-10 mt-4">
            <div class="d-flex align-items-center gap-2.5 mb-3">
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width:38px;height:38px;font-size:0.95rem;background:linear-gradient(135deg,#6366f1,#a855f7);">
                    D
                </div>
                <div class="overflow-hidden text-truncate">
                    <span class="fw-semibold text-white small d-block text-truncate" style="max-width:140px;"><?= e($deanName) ?></span>
                    <span class="text-white-50 small d-block text-truncate" style="font-size:0.7rem; max-width:140px;">Dean of Student Affairs</span>
                </div>
            </div>
            <a href="logout.php" class="btn btn-outline-danger btn-sm w-100 rounded-pill fw-bold">
                <i class="bi bi-box-arrow-right me-1"></i> Sign Out
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">
        <!-- Top Executive Welcome Banner -->
        <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 mb-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);">
            <div class="row align-items-center g-3">
                <div class="col-md-8">
                    <span class="badge bg-indigo text-white rounded-pill px-3 py-1 fw-bold mb-2 small" style="background:#6366f1;"><i class="bi bi-shield-check me-1"></i> DIRECTORATE OF STUDENT AFFAIRS</span>
                    <h2 class="fw-bold mb-2">Welcome, <?= e($firstName) ?>! 👑</h2>
                    <p class="text-white-80 mb-0">Supervising <strong><?= $totalClubs ?> Active Student Chapters</strong>, campus fests, student proposals, and chapter leadership credentials.</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <a href="super/clubs.php" class="btn btn-light rounded-pill px-4 py-2-5 fw-bold text-dark shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Add New Club
                    </a>
                </div>
            </div>
        </div>

        <!-- 4 Quick Stat Cards -->
        <div class="row g-3 g-md-4 mb-4">
            <div class="col-6 col-xl-3">
                <div class="card stat-card p-3 p-md-4 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">Active Clubs</span>
                            <h3 class="fw-bold text-dark mb-0"><?= $totalClubs ?></h3>
                        </div>
                        <div class="stat-icon bg-primary-subtle text-primary">
                            <i class="bi bi-trophy-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card stat-card p-3 p-md-4 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">Campus Events</span>
                            <h3 class="fw-bold text-success mb-0"><?= $totalEvents ?></h3>
                        </div>
                        <div class="stat-icon bg-success-subtle text-success">
                            <i class="bi bi-calendar-event-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card stat-card p-3 p-md-4 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">Core Leaders</span>
                            <h3 class="fw-bold text-info mb-0"><?= $totalLeaders ?></h3>
                        </div>
                        <div class="stat-icon bg-info-subtle text-info">
                            <i class="bi bi-person-badge-fill"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-xl-3">
                <div class="card stat-card p-3 p-md-4 shadow-sm h-100">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-secondary small fw-semibold d-block mb-1">Proposals</span>
                            <h3 class="fw-bold text-warning mb-0"><?= count($pendingProposals) ?></h3>
                        </div>
                        <div class="stat-icon bg-warning-subtle text-warning">
                            <i class="bi bi-inbox-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <!-- Institutional Chapters Overview Table -->
            <div class="col-lg-8">
                <div class="card p-3 p-md-4 border-0 shadow-sm rounded-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Institutional Chapters Overview</h5>
                            <span class="text-secondary small">Registered student clubs & leadership credentials</span>
                        </div>
                        <a href="super/clubs.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">Manage All &rarr;</a>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr class="small text-secondary">
                                    <th>CHAPTER NAME</th>
                                    <th>CATEGORY</th>
                                    <th>LEAD EMAIL</th>
                                    <th>STATUS</th>
                                    <th class="text-end">ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentClubs as $rc): ?>
                                    <tr>
                                        <td class="fw-bold text-dark">
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="<?= e($rc['logo'] ?: '../assets/United Logo.webp') ?>" class="rounded-2" style="width:34px;height:34px;object-fit:cover;" alt="">
                                                <span class="text-truncate" style="max-width: 170px;"><?= e($rc['name']) ?></span>
                                            </div>
                                        </td>
                                        <td class="small text-secondary"><?= e($rc['category_name']) ?></td>
                                        <td class="small font-monospace text-secondary"><?= e($rc['admin_email'] ?: 'Unassigned') ?></td>
                                        <td><span class="badge bg-success-subtle text-success border rounded-pill px-2.5 py-1 small">Active</span></td>
                                        <td class="text-end">
                                            <a href="../club-detail.html?id=<?= e($rc['id']) ?>" target="_blank" class="btn btn-sm btn-light rounded-circle" title="View Public Page"><i class="bi bi-eye text-primary"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pending Proposals Section -->
            <div class="col-lg-4">
                <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold text-dark mb-0">Pending Proposals</h5>
                        <span class="badge bg-warning-subtle text-warning border rounded-pill px-2.5 py-1 small"><?= count($pendingProposals) ?> Review</span>
                    </div>

                    <?php if (empty($pendingProposals)): ?>
                        <div class="text-center py-4 text-muted small">
                            <i class="bi bi-check-circle fs-3 text-success d-block mb-1"></i>
                            No pending proposals requiring review.
                        </div>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2.5">
                            <?php foreach ($pendingProposals as $prop): ?>
                                <div class="p-3 border rounded-3 bg-light">
                                    <div class="fw-bold text-dark small mb-1"><?= e($prop['proposed_title']) ?></div>
                                    <div class="d-flex justify-content-between align-items-center text-muted small mb-2" style="font-size:0.75rem;">
                                        <span>By: <?= e($prop['applicant_name']) ?></span>
                                        <span class="badge bg-warning text-dark rounded-pill"><?= ucfirst(e($prop['status'])) ?></span>
                                    </div>
                                    <p class="small text-secondary mb-0 text-truncate" style="font-size: 0.76rem; max-width: 240px;"><?= e($prop['objective']) ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Audit Trail Logs -->
        <div class="card p-3 p-md-4 border-0 shadow-sm rounded-4 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h5 class="fw-bold text-dark mb-0">Recent Security & Governance Logs</h5>
                    <span class="text-secondary small">Real-time audit trail of administrative activities</span>
                </div>
                <a href="super/audit-logs.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold">Full Logs &rarr;</a>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle small mb-0">
                    <thead class="table-light">
                        <tr class="small text-secondary">
                            <th>TIMESTAMP</th>
                            <th>USER / ACTOR</th>
                            <th>ACTION</th>
                            <th>DETAILS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentLogs as $log): ?>
                            <tr>
                                <td class="text-muted" style="font-size:0.78rem;"><?= date('M j, Y - g:i A', strtotime($log['created_at'])) ?></td>
                                <td class="fw-bold text-dark"><?= e($log['user_name']) ?></td>
                                <td><span class="badge bg-secondary-subtle text-secondary rounded-pill font-monospace"><?= e($log['action']) ?></span></td>
                                <td class="text-secondary"><?= e($log['details']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const deanToggleBtn = document.getElementById('deanSidebarToggle');
    const deanCloseBtn = document.getElementById('deanSidebarClose');
    const deanSidebar = document.getElementById('deanSidebar');
    const deanBackdrop = document.getElementById('deanSidebarBackdrop');

    function openDeanSidebar() {
        deanSidebar.classList.add('show');
        deanBackdrop.classList.add('show');
    }

    function closeDeanSidebar() {
        deanSidebar.classList.remove('show');
        deanBackdrop.classList.remove('show');
    }

    if (deanToggleBtn) deanToggleBtn.addEventListener('click', openDeanSidebar);
    if (deanCloseBtn) deanCloseBtn.addEventListener('click', closeDeanSidebar);
    if (deanBackdrop) deanBackdrop.addEventListener('click', closeDeanSidebar);
</script>
</body>
</html>
