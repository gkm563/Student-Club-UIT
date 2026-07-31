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
$newCredentials = null;

// ── 1. Handle Create New Club & Issue Credentials ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_club') {
    $name = trim($_POST['name'] ?? '');
    $shortName = trim($_POST['short_name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 1);
    $tagline = trim($_POST['tagline'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPassword = trim($_POST['admin_password'] ?? '');
    $adminName = trim($_POST['admin_name'] ?? ($name . ' President / Lead'));

    if (empty($name) || empty($shortName) || empty($adminEmail) || empty($adminPassword)) {
        $error = 'Please fill in all required fields (Club Name, Short Code, Admin Email & Password).';
    } else {
        try {
            $clubId = 'clb_' . bin2hex(random_bytes(4));
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $shortName)) . '-' . rand(100, 999);
            
            // Insert Club
            $stmt = $db->prepare("
                INSERT INTO clubs (id, name, short_name, slug, category_id, tagline, description, logo, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, '../assets/United Logo.webp', 'active')
            ");
            $stmt->execute([$clubId, $name, $shortName, $slug, $categoryId, $tagline, $description]);

            // Create User Account for Club Leadership
            $userId = 'usr_' . bin2hex(random_bytes(4));
            $passHash = password_hash($adminPassword, PASSWORD_DEFAULT);

            $uStmt = $db->prepare("
                INSERT INTO users (id, email, password_hash, full_name, role, status)
                VALUES (?, ?, ?, ?, 'club_admin', 'active')
            ");
            $uStmt->execute([$userId, $adminEmail, $passHash, $adminName]);

            // Link Club to Admin
            $aStmt = $db->prepare("INSERT INTO club_admins (club_id, user_id) VALUES (?, ?)");
            $aStmt->execute([$clubId, $userId]);

            // Audit log
            $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CLUB_CREATED', "Created Club '$name' and issued credentials to $adminEmail"]);

            $newCredentials = [
                'club_name' => $name,
                'email' => $adminEmail,
                'password' => $adminPassword
            ];
            $message = "Club '$name' created successfully! Credentials issued to team.";
        } catch (Exception $e) {
            $error = 'Error creating club: ' . $e->getMessage();
        }
    }
}

// ── 2. Handle Edit Club Details ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_club') {
    $clubId = $_POST['club_id'] ?? '';
    $name = trim($_POST['name'] ?? '');
    $shortName = trim($_POST['short_name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 1);
    $tagline = trim($_POST['tagline'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (!empty($clubId) && !empty($name) && !empty($shortName)) {
        try {
            $stmt = $db->prepare("
                UPDATE clubs 
                SET name = ?, short_name = ?, category_id = ?, tagline = ?, description = ?
                WHERE id = ?
            ");
            $stmt->execute([$name, $shortName, $categoryId, $tagline, $description, $clubId]);

            // Audit log
            $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CLUB_EDITED', "Updated club details for '$name' ($shortName)"]);

            $message = "Club details for '$name' updated successfully!";
        } catch (Exception $e) {
            $error = 'Error updating club: ' . $e->getMessage();
        }
    }
}

// ── 3. Handle Password Reset for a Club Admin ──────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_password') {
    $userId = $_POST['user_id'] ?? '';
    $newPass = trim($_POST['new_password'] ?? '');

    if (!empty($userId) && !empty($newPass)) {
        try {
            $passHash = password_hash($newPass, PASSWORD_DEFAULT);
            $rStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $rStmt->execute([$passHash, $userId]);

            // Audit log
            $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CREDENTIAL_RESET', "Reset password for user account ID: $userId"]);

            $message = "Password updated successfully for club leadership account.";
        } catch (Exception $e) {
            $error = "Error resetting password: " . $e->getMessage();
        }
    }
}

// ── 4. Handle Status Toggle or Delete ──────────────────────────────
if (isset($_GET['toggle_status']) && isset($_GET['club_id'])) {
    $clubId = $_GET['club_id'];
    $currentStatus = $_GET['toggle_status'];
    $newStatus = ($currentStatus === 'active') ? 'inactive' : 'active';
    
    $cStmt = $db->prepare("SELECT name FROM clubs WHERE id = ?");
    $cStmt->execute([$clubId]);
    $cName = $cStmt->fetchColumn() ?: $clubId;

    $stmt = $db->prepare("UPDATE clubs SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $clubId]);

    // Audit Log
    $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CLUB_STATUS_CHANGED', "Set '$cName' status to '$newStatus' (Public website visibility updated)"]);

    header('Location: clubs.php?msg=Status+updated+to+' . urlencode(ucfirst($newStatus)));
    exit;
}

if (isset($_GET['delete_club'])) {
    $clubId = $_GET['delete_club'];
    $cStmt = $db->prepare("SELECT name FROM clubs WHERE id = ?");
    $cStmt->execute([$clubId]);
    $cName = $cStmt->fetchColumn() ?: $clubId;

    $stmt = $db->prepare("DELETE FROM clubs WHERE id = ?");
    $stmt->execute([$clubId]);

    // Audit Log
    $logStmt = $db->prepare("INSERT INTO audit_logs (id, user_id, user_name, action, details, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $logStmt->execute(['log_' . bin2hex(random_bytes(4)), $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CLUB_DELETED', "Permanently deleted club '$cName'"]);

    header('Location: clubs.php?msg=Club+deleted');
    exit;
}

// Fetch all categories
$catStmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $catStmt->fetchAll();

// Fetch all registered clubs with admin details
$clubsStmt = $db->query("
    SELECT c.*, cat.name as category_name, u.id as user_id, u.email as admin_email, u.full_name as admin_name
    FROM clubs c
    JOIN categories cat ON c.category_id = cat.id
    LEFT JOIN club_admins ca ON ca.club_id = c.id
    LEFT JOIN users u ON ca.user_id = u.id
    ORDER BY c.created_at DESC
");
$registeredClubs = $clubsStmt->fetchAll();

// Calculated Stat Chips Metrics
$totalClubsCount   = count($registeredClubs);
$activeClubsCount  = count(array_filter($registeredClubs, fn($c) => $c['status'] === 'active'));
$inactiveClubsCount= $totalClubsCount - $activeClubsCount;
$categoriesCount   = count($categories);
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Campus Clubs | Dean Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #1e293b; }

        /* iOS Style Animated Toggle Switch */
        .toggle-switch-wrap {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            text-decoration: none !important;
            user-select: none;
        }
        .toggle-switch {
            position: relative;
            width: 44px;
            height: 24px;
            border-radius: 20px;
            background: #cbd5e1;
            transition: background-color 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-block;
            flex-shrink: 0;
            border: 1px solid #94a3b8;
        }
        .toggle-switch.active {
            background: #10b981;
            border-color: #059669;
        }
        .toggle-switch-handle {
            position: absolute;
            top: 2px;
            left: 2px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: #ffffff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.25);
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .toggle-switch.active .toggle-switch-handle {
            transform: translateX(20px);
        }
        .toggle-status-label {
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        .toggle-status-label.active { color: #059669; }
        .toggle-status-label.inactive { color: #dc2626; }

        /* Compact Stat Chip Cards */
        .stat-chip-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
            padding: 14px 18px;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.2s ease;
        }
        .stat-chip-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(15, 23, 42, 0.06);
            border-color: #cbd5e1;
        }
        .chip-icon-box {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .pulse-dot-green {
            width: 8px; height: 8px; border-radius: 50%; background: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulseGreen 2s infinite; display: inline-block;
        }
        .pulse-dot-red {
            width: 8px; height: 8px; border-radius: 50%; background: #ef4444; display: inline-block;
        }
        @keyframes pulseGreen {
            0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
            70% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
            100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
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
                <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 fw-bold small">USC UIT CAMPUS GOVERNANCE</span>
                <h2 class="fw-bold mb-1 text-dark mt-2">Manage Campus Clubs & Credentials</h2>
                <p class="text-secondary small mb-0">Create new student chapters, issue leadership credentials, reset passwords, and edit chapter details.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 py-2-5 fw-bold shadow-sm text-white" data-bs-toggle="modal" data-bs-target="#createClubModal">
                <i class="bi bi-plus-lg me-1"></i> Add New Club
            </button>
        </div>

        <!-- Alert Feedback Messages -->
        <?php if (!empty($message) && empty($newCredentials)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- PROMINENT ISSUED CREDENTIALS SUCCESS CARD -->
        <?php if (!empty($newCredentials)): ?>
            <div class="card border-0 shadow-lg rounded-4 p-4 mb-4 text-dark" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border-left: 6px solid #10b981 !important;" id="issuedCredsCard">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3 pb-3 border-bottom border-success-subtle">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 48px; height: 48px;">
                            <i class="bi bi-key-fill fs-3"></i>
                        </div>
                        <div>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold small">CHAPTER CREDENTIALS ISSUED</span>
                            <h4 class="fw-bold mb-0 text-success mt-1"><?= htmlspecialchars($newCredentials['club_name']) ?></h4>
                        </div>
                    </div>
                    <button type="button" class="btn btn-success rounded-pill px-4 py-2 fw-bold text-white shadow-sm" onclick="copyIssuedCredentials()">
                        <i class="bi bi-clipboard-check me-1"></i> Copy Credentials
                    </button>
                </div>

                <div class="row g-3 font-monospace small">
                    <div class="col-md-6 col-lg-4">
                        <div class="bg-white p-3 rounded-3 border">
                            <span class="text-secondary d-block mb-1 font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">LOGIN EMAIL</span>
                            <code class="fs-6 text-primary" id="credEmailText"><?= htmlspecialchars($newCredentials['email']) ?></code>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <div class="bg-white p-3 rounded-3 border">
                            <span class="text-secondary d-block mb-1 font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">ISSUED PASSWORD</span>
                            <div class="d-flex align-items-center justify-content-between">
                                <code class="fs-6 text-danger fw-bold" id="credPassText"><?= htmlspecialchars($newCredentials['password']) ?></code>
                                <button type="button" class="btn btn-xs btn-link p-0 text-secondary" onclick="toggleTextVisibility('credPassText', this)">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-lg-4">
                        <div class="bg-white p-3 rounded-3 border">
                            <span class="text-secondary d-block mb-1 font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">PORTAL LOGIN URL</span>
                            <a href="http://localhost/UIT/club-login.php" target="_blank" class="fw-bold text-primary text-decoration-underline" id="credUrlText">http://localhost/UIT/club-login.php</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- ── 4 Compact Stat Chip Cards Deck ── -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-primary-subtle text-primary"><i class="bi bi-trophy-fill"></i></div>
                    <div>
                        <div class="fw-bold text-dark lh-1" style="font-size:1.35rem;"><?= $totalClubsCount ?></div>
                        <div class="text-secondary small fw-semibold mt-1" style="font-size:0.75rem;">Total Chapters</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-success-subtle text-success"><i class="bi bi-power"></i></div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="pulse-dot-green"></span>
                            <span class="fw-bold text-success lh-1" style="font-size:1.35rem;"><?= $activeClubsCount ?></span>
                        </div>
                        <div class="text-secondary small fw-semibold mt-1" style="font-size:0.75rem;">Active (Public ON)</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-danger-subtle text-danger"><i class="bi bi-eye-slash-fill"></i></div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="pulse-dot-red"></span>
                            <span class="fw-bold text-danger lh-1" style="font-size:1.35rem;"><?= $inactiveClubsCount ?></span>
                        </div>
                        <div class="text-secondary small fw-semibold mt-1" style="font-size:0.75rem;">Inactive (Private OFF)</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-purple-subtle text-purple" style="background:#f5f3ff; color:#7c3aed;"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                    <div>
<?php
// Calculate profile setup health scores for all clubs
$incompleteClubsList = [];
foreach ($registeredClubs as $cCheck) {
    $hCheck = calculate_club_profile_health($cCheck, $db);
    if ($hCheck['score'] < 85) {
        $cCheck['health'] = $hCheck;
        $incompleteClubsList[] = $cCheck;
    }
}
?>

        <?php if (!empty($incompleteClubsList)): ?>
            <div class="alert alert-warning border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle bg-warning text-white p-2.5 d-flex align-items-center justify-content-center flex-shrink-0" style="width: 48px; height: 48px;">
                            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-1"><i class="bi bi-shield-alert me-1"></i> <?= count($incompleteClubsList) ?> Student Chapter(s) Need Profile Setup Completion</h6>
                            <p class="small text-secondary mb-0">The following clubs have low profile completion scores (&lt; 85%): 
                                <strong class="text-dark"><?= implode(', ', array_column($incompleteClubsList, 'name')) ?></strong>.
                            </p>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-dark rounded-pill px-4 py-2 fw-bold shadow-xs" onclick="document.getElementById('clubStatusFilter').value='incomplete'; document.getElementById('clubStatusFilter').dispatchEvent(new Event('change'));">
                        <i class="bi bi-funnel-fill me-1"></i> Filter Incomplete Clubs
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Clubs Filter & Search Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
            <div class="row g-3 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 rounded-start-pill"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" id="clubSearchInput" class="form-control bg-light border-start-0 rounded-end-pill" placeholder="Instant search by name, short code, or email...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select id="clubStatusFilter" class="form-select rounded-pill">
                        <option value="all">Filter by Status: All Chapters</option>
                        <option value="active">Active Only</option>
                        <option value="inactive">Inactive Only</option>
                        <option value="incomplete">Needs Profile Setup (< 85%)</option>
                    </select>
                </div>
                <div class="col-md-3 text-md-end d-flex align-items-center justify-content-end gap-2">
                    <div class="dropdown">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-download me-1"></i> Export Data
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end rounded-4 shadow border-0 p-2">
                            <li><a class="dropdown-item rounded-3 small py-2 fw-medium" href="#" onclick="ClubHubExporter.exportCSV('clubsTable', 'Campus-Clubs-Directory'); return false;"><i class="bi bi-filetype-csv text-success me-2 fs-6"></i> Export CSV (.csv)</a></li>
                            <li><a class="dropdown-item rounded-3 small py-2 fw-medium" href="#" onclick="ClubHubExporter.exportExcel('clubsTable', 'Campus-Clubs-Directory'); return false;"><i class="bi bi-file-earmark-excel text-success me-2 fs-6"></i> Export Excel (.xls)</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-item rounded-3 small py-2 fw-medium" href="#" onclick="ClubHubExporter.exportPDF('clubsTable', 'Campus Student Clubs Directory'); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2 fs-6"></i> Print / Save PDF Report</a></li>
                        </ul>
                    </div>
                    <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-2 fw-bold">
                        <span id="clubCountBadge"><?= count($registeredClubs) ?></span> Clubs
                    </span>
                </div>
            </div>
        </div>

        <!-- Clubs Roster Table -->
        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="clubsTable">
                    <thead class="table-light">
                        <tr class="small text-secondary">
                            <th>CLUB & CODE</th>
                            <th>CATEGORY</th>
                            <th>PROFILE SETUP HEALTH</th>
                            <th>PRESIDENT / LEAD EMAIL</th>
                            <th>STATUS (PUBLIC VISIBILITY)</th>
                            <th class="text-end">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($registeredClubs as $club): ?>
                            <?php $health = calculate_club_profile_health($club, $db); ?>
                            <tr data-name="<?= htmlspecialchars($club['name']) ?>" 
                                data-short="<?= htmlspecialchars($club['short_name']) ?>" 
                                data-category="<?= htmlspecialchars($club['category_name']) ?>" 
                                data-email="<?= htmlspecialchars($club['admin_email'] ?? '') ?>" 
                                data-status="<?= htmlspecialchars($club['status']) ?>"
                                data-health="<?= $health['score'] ?>">
                                <td>
                                    <a href="club-detail.php?id=<?= $club['id'] ?>" class="text-decoration-none d-flex align-items-center gap-3" title="View Executive Club Overview">
                                        <img src="<?= htmlspecialchars($club['logo'] ?: '../../assets/United Logo.webp') ?>" class="rounded-3 border shadow-sm flex-shrink-0" style="width:40px;height:40px;object-fit:cover;" alt="">
                                        <div>
                                            <div class="fw-bold text-dark mb-0 text-primary-hover"><?= htmlspecialchars($club['name']) ?> <i class="bi bi-chevron-right text-primary small" style="font-size:0.7rem;"></i></div>
                                            <span class="badge bg-secondary-subtle text-secondary font-monospace" style="font-size:0.7rem;"><?= htmlspecialchars($club['short_name']) ?></span>
                                        </div>
                                    </a>
                                </td>
                                <td>
                                    <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small d-block mb-1"><?= htmlspecialchars($club['category_name']) ?></span>
                                    <?php if (!empty($club['parent_wing'])): ?>
                                        <span class="badge <?= strpos($club['parent_wing'], 'Developers') !== false ? 'bg-info-subtle text-primary border-info-subtle' : 'bg-danger-subtle text-danger border-danger-subtle' ?> border rounded-pill px-2 py-0.5" style="font-size:0.7rem;">
                                            <i class="bi <?= strpos($club['parent_wing'], 'Developers') !== false ? 'bi-code-slash' : 'bi-palette-fill' ?> me-1"></i><?= htmlspecialchars($club['parent_wing']) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-<?= $health['badge_class'] ?>-subtle text-<?= $health['badge_class'] ?> border rounded-pill px-2.5 py-1 fw-bold" style="font-size: 0.78rem;">
                                            <i class="bi bi-heart-pulse-fill me-1"></i><?= $health['score'] ?>% <?= $health['status'] ?>
                                        </span>
                                    </div>
                                    <div class="progress rounded-pill mt-1" style="height: 5px; width: 110px;">
                                        <div class="progress-bar bg-<?= $health['badge_class'] ?>" style="width: <?= $health['score'] ?>%;"></div>
                                    </div>
                                    <span class="text-muted small" style="font-size:0.68rem;"><?= $health['filled_count'] ?>/<?= $health['total_fields'] ?> criteria completed</span>
                                </td>
                                <td>
                                    <?php if ($club['admin_email']): ?>
                                        <div class="fw-semibold text-dark small mb-0"><?= htmlspecialchars($club['admin_name'] ?: 'Club Lead') ?></div>
                                        <div class="small font-monospace text-secondary"><?= htmlspecialchars($club['admin_email']) ?></div>
                                    <?php else: ?>
                                        <span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i> Unassigned</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <!-- Animated iOS Style Status Toggle Button -->
                                    <a href="clubs.php?toggle_status=<?= $club['status'] ?>&club_id=<?= $club['id'] ?>" class="toggle-switch-wrap" title="Click to toggle <?= htmlspecialchars($club['name']) ?> status">
                                        <span class="toggle-switch <?= $club['status'] === 'active' ? 'active' : '' ?>">
                                            <span class="toggle-switch-handle"></span>
                                        </span>
                                        <span class="toggle-status-label <?= $club['status'] === 'active' ? 'active' : 'inactive' ?>">
                                            <?= $club['status'] === 'active' ? 'ON (Active)' : 'OFF (Private)' ?>
                                        </span>
                                    </a>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <!-- Detailed Executive Overview Page -->
                                        <a href="club-detail.php?id=<?= $club['id'] ?>" class="btn btn-sm btn-light rounded-circle me-1" title="View Detailed Executive Overview">
                                            <i class="bi bi-bar-chart-line-fill" style="color:#7c3aed;"></i>
                                        </a>

                                        <!-- View Public Detail Page -->
                                        <a href="../../club-detail.html?id=<?= $club['id'] ?>" target="_blank" class="btn btn-sm btn-light rounded-circle me-1" title="View Public Page">
                                            <i class="bi bi-eye text-primary"></i>
                                        </a>

                                        <!-- Edit Club Modal Trigger -->
                                        <button type="button" class="btn btn-sm btn-light rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#editClubModal<?= $club['id'] ?>" title="Edit Club Details">
                                            <i class="bi bi-pencil-fill text-dark"></i>
                                        </button>

                                         <!-- Reset Password Modal Trigger -->
                                        <?php if ($club['user_id']): ?>
                                            <button type="button" class="btn btn-sm btn-light rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#resetPassModal<?= $club['user_id'] ?>" title="Reset Leader Password">
                                                <i class="bi bi-key-fill text-warning"></i>
                                            </button>
                                        <?php endif; ?>

                                        <!-- Delete Club -->
                                        <a href="clubs.php?delete_club=<?= $club['id'] ?>" onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars($club['name']) ?> permanently?');" class="btn btn-sm btn-light text-danger rounded-circle" title="Delete Club">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>

                                    <!-- Edit Club Modal -->
                                    <div class="modal fade text-start" id="editClubModal<?= $club['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Club: <?= htmlspecialchars($club['name']) ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="clubs.php" method="POST">
                                                    <input type="hidden" name="action" value="edit_club">
                                                    <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Club Name *</label>
                                                            <input type="text" name="name" class="form-control rounded-3" value="<?= htmlspecialchars($club['name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Short Code *</label>
                                                            <input type="text" name="short_name" class="form-control rounded-3" value="<?= htmlspecialchars($club['short_name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Category *</label>
                                                            <select name="category_id" class="form-select rounded-3">
                                                                <?php foreach ($categories as $cat): ?>
                                                                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $club['category_id'] ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($cat['name']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Tagline</label>
                                                            <input type="text" name="tagline" class="form-control rounded-3" value="<?= htmlspecialchars($club['tagline']) ?>">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-semibold">Description</label>
                                                            <textarea name="description" class="form-control rounded-3" rows="3"><?= htmlspecialchars($club['description']) ?></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0 pt-0">
                                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Reset Password Modal -->
                                    <?php if ($club['user_id']): ?>
                                        <div class="modal fade text-start" id="resetPassModal<?= $club['user_id'] ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content rounded-4 border-0 shadow">
                                                    <div class="modal-header border-0 pb-0">
                                                        <h5 class="modal-title fw-bold text-dark"><i class="bi bi-key-fill text-warning me-2"></i> Reset Password: <?= htmlspecialchars($club['admin_email']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form action="clubs.php" method="POST">
                                                        <input type="hidden" name="action" value="reset_password">
                                                        <input type="hidden" name="user_id" value="<?= $club['user_id'] ?>">
                                                        <div class="modal-body">
                                                            <p class="small text-secondary">Set a new password for <strong><?= htmlspecialchars($club['name']) ?></strong> leadership account.</p>
                                                            <div class="mb-3">
                                                                <label class="form-label small fw-semibold">New Password *</label>
                                                                <input type="password" name="new_password" class="form-control rounded-3" placeholder="Enter new password" required>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 pt-0">
                                                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">Update Password</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add New Club & Issue Credentials Modal -->
<div class="modal fade" id="createClubModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-trophy-fill text-primary me-2"></i> Add New Campus Club & Issue Credentials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="clubs.php" method="POST">
                <input type="hidden" name="action" value="create_club">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Club Full Name *</label>
                            <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. CodeCrafters Developer Club" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Short Code / Acronym *</label>
                            <input type="text" name="short_name" class="form-control rounded-3" placeholder="e.g. CCDC" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Category *</label>
                            <select name="category_id" class="form-select rounded-3" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Tagline</label>
                            <input type="text" name="tagline" class="form-control rounded-3" placeholder="e.g. Building Next-Gen Web Solutions">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" class="form-control rounded-3" rows="2" placeholder="Brief club mandate..."></textarea>
                        </div>
                        <div class="col-12"><hr class="my-2 text-secondary opacity-25"></div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Club President / Lead Name</label>
                            <input type="text" name="admin_name" class="form-control rounded-3" placeholder="e.g. Rahul Sharma">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Club Admin Email *</label>
                            <input type="email" name="admin_email" class="form-control rounded-3" placeholder="e.g. codecrafters@uit.edu" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Initial Password *</label>
                            <input type="password" name="admin_password" class="form-control rounded-3" placeholder="Set initial password for team" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Create Club & Issue Credentials</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="../../assets/js/export_utility.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('clubSearchInput');
    const statusFilter = document.getElementById('clubStatusFilter');
    const tableBody = document.querySelector('#clubsTable tbody');
    const countBadge = document.getElementById('clubCountBadge');
    
    if (!tableBody) return;
    const rows = Array.from(tableBody.querySelectorAll('tr[data-name]'));

    function filterClubs() {
        const query = (searchInput.value || '').toLowerCase().trim();
        const selectedStatus = statusFilter.value;

        let visibleCount = 0;
        rows.forEach(row => {
            const name = (row.dataset.name || '').toLowerCase();
            const shortName = (row.dataset.short || '').toLowerCase();
            const category = (row.dataset.category || '').toLowerCase();
            const email = (row.dataset.email || '').toLowerCase();
            const status = (row.dataset.status || '').toLowerCase();
            const healthScore = parseInt(row.dataset.health || '0', 10);

            const matchesQuery = !query || name.includes(query) || shortName.includes(query) || category.includes(query) || email.includes(query);
            
            let matchesStatus = (selectedStatus === 'all');
            if (selectedStatus === 'active') matchesStatus = (status === 'active');
            if (selectedStatus === 'inactive') matchesStatus = (status === 'inactive');
            if (selectedStatus === 'incomplete') matchesStatus = (healthScore < 85);

            if (matchesQuery && matchesStatus) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (countBadge) countBadge.textContent = visibleCount;
    }

    searchInput?.addEventListener('input', filterClubs);
    statusFilter?.addEventListener('change', filterClubs);

    // Auto-populate search from URL parameter if present
    const urlParams = new URLSearchParams(window.location.search);
    const searchParam = urlParams.get('search');
    if (searchParam && searchInput) {
        searchInput.value = searchParam;
        filterClubs();
    }
});
</script>
</body>
</html>
