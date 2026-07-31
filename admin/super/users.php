<?php
session_start();
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Auth Check for Super Admin (Dean Sir)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'super_admin') {
    header('Location: ../dean-login.php');
    exit;
}

$db = Database::getConnection();
$message = '';
$error = '';
$issuedCredentialsCard = null;

// Fetch Logged-in Super Admin Profile Details
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$loggedUser = $stmt->fetch() ?: [
    'full_name' => $_SESSION['full_name'] ?? 'Prof. Sanjay Srivastava',
    'email' => $_SESSION['email'] ?? 'admin@uit.edu'
];

// Fetch Categories for Club Creation Modal
$categories = $db->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

// Enforce Global Anti-CSRF Token Security Verification for POST Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Security Token Invalid (CSRF Protection Blocked): Please refresh the page and try again.";
    }
}

// ── 0. Handle Create Club & Issue Credentials ────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error) && isset($_POST['action']) && $_POST['action'] === 'create_club') {
    $name = trim($_POST['name'] ?? '');
    $shortName = trim($_POST['short_name'] ?? '');
    $categoryId = intval($_POST['category_id'] ?? 1);
    $tagline = trim($_POST['tagline'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $adminName = trim($_POST['admin_name'] ?? '');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPassword = trim($_POST['admin_password'] ?? '');

    if (empty($name) || empty($shortName) || empty($adminEmail) || empty($adminPassword)) {
        $error = 'Club Name, Short Code, Admin Email, and Password are required.';
    } else {
        try {
            $clubId = 'clb_' . slugify($shortName) . '_uit_' . date('Y');
            $slug = slugify($name);

            // Create Club
            $cStmt = $db->prepare("INSERT INTO clubs (id, name, short_name, slug, category_id, tagline, description, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
            $cStmt->execute([$clubId, $name, $shortName, $slug, $categoryId, $tagline, $description]);

            // Create Club Admin User
            $userId = 'usr_' . bin2hex(random_bytes(4));
            $passHash = password_hash($adminPassword, PASSWORD_DEFAULT);
            $uStmt = $db->prepare("INSERT INTO users (id, email, password_hash, full_name, role, status, created_at) VALUES (?, ?, ?, ?, 'club_admin', 'active', NOW())");
            $uStmt->execute([$userId, $adminEmail, $passHash, $adminName ?: $name . ' Lead']);

            // Link Club to Admin
            $aStmt = $db->prepare("INSERT INTO club_admins (club_id, user_id) VALUES (?, ?)");
            $aStmt->execute([$clubId, $userId]);

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'CLUB_CREATED', 'club', $clubId, "Created Club '$name' and issued credentials to $adminEmail");

            $issuedCredentialsCard = [
                'type' => 'Campus Club Lead Credentials',
                'title' => $name . ' (' . $shortName . ')',
                'lead_name' => $adminName ?: $name . ' Lead',
                'role' => 'Club Admin (President)',
                'email' => $adminEmail,
                'password' => $adminPassword,
                'user_id' => $userId,
                'login_url' => 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/UIT/club-login.php',
                'permissions' => ['Manage Club Profile', 'Organize Events', 'Manage Student Roster', 'Upload Gallery Items']
            ];

            $message = "New Club '$name' created successfully and credentials issued to $adminEmail!";
        } catch (Exception $e) {
            $error = 'Error creating club: ' . $e->getMessage();
        }
    }
}

// ── 1. Handle Create New Main Admin Account ──────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error) && isset($_POST['action']) && $_POST['action'] === 'create_main_admin') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $designation = trim($_POST['designation'] ?? 'Institutional Admin');
    $permissions = $_POST['permissions'] ?? [];

    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'Full Name, Email, and Password are required.';
    } else {
        try {
            // Check existing email
            $chk = $db->prepare("SELECT id FROM users WHERE email = ?");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = "An account with email '$email' already exists!";
            } else {
                $userId = 'usr_admin_' . bin2hex(random_bytes(4));
                $passHash = password_hash($password, PASSWORD_DEFAULT);
                $permJson = json_encode($permissions);

                $ins = $db->prepare("INSERT INTO users (id, email, password_hash, full_name, role, permissions, status, created_at) VALUES (?, ?, ?, ?, 'super_admin', ?, 'active', NOW())");
                $ins->execute([$userId, $email, $passHash, $fullName, $permJson]);

                $permList = implode(', ', $permissions);
                log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'MAIN_ADMIN_CREATED', 'user', $userId, "Created Main Admin '$fullName' ($email) [$designation]. Permissions: [$permList]");

                $issuedCredentialsCard = [
                    'type' => 'Institutional Main Admin Credentials',
                    'title' => $fullName,
                    'lead_name' => $fullName,
                    'role' => $designation . ' (Super Admin)',
                    'email' => $email,
                    'password' => $password,
                    'user_id' => $userId,
                    'login_url' => 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/UIT/admin/dean-login.php',
                    'permissions' => !empty($permissions) ? $permissions : ['Full System Access']
                ];

                $message = "New Main Admin account for '$fullName' created successfully with [$designation] permissions!";
            }
        } catch (Exception $e) {
            $error = 'Error creating Main Admin account: ' . $e->getMessage();
        }
    }
}

// ── 2. Handle Profile Update for Logged-in Admin ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error) && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');

    if (empty($fullName) || empty($email)) {
        $error = 'Full Name and Email are required.';
    } else {
        try {
            if (!empty($newPassword)) {
                $passHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $uStmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, password_hash = ? WHERE id = ?");
                $uStmt->execute([$fullName, $email, $passHash, $_SESSION['user_id']]);
            } else {
                $uStmt = $db->prepare("UPDATE users SET full_name = ?, email = ? WHERE id = ?");
                $uStmt->execute([$fullName, $email, $_SESSION['user_id']]);
            }

            $_SESSION['full_name'] = $fullName;
            $_SESSION['email'] = $email;

            log_audit($db, $_SESSION['user_id'], $fullName, 'DEAN_PROFILE_UPDATED', 'user', $_SESSION['user_id'], "Updated personal admin credentials.");
            $message = 'Your profile credentials updated successfully!';
            
            $stmt->execute([$_SESSION['user_id']]);
            $loggedUser = $stmt->fetch();
        } catch (Exception $e) {
            $error = 'Error updating profile: ' . $e->getMessage();
        }
    }
}

// ── 3. Handle Edit User Account & Permissions Details ────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error) && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $targetUserId = $_POST['user_id'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $status = trim($_POST['status'] ?? 'active');
    $permissions = $_POST['permissions'] ?? null;

    if (!empty($targetUserId) && !empty($fullName) && !empty($email)) {
        try {
            if ($permissions !== null) {
                $permJson = json_encode($permissions);
                $uStmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, status = ?, permissions = ? WHERE id = ?");
                $uStmt->execute([$fullName, $email, $status, $permJson, $targetUserId]);
            } else {
                $uStmt = $db->prepare("UPDATE users SET full_name = ?, email = ?, status = ? WHERE id = ?");
                $uStmt->execute([$fullName, $email, $status, $targetUserId]);
            }

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'USER_EDITED', 'user', $targetUserId, "Updated account details for '$fullName' ($email). Status: $status");

            $message = "User account details and permissions for '$fullName' updated successfully!";
        } catch (Exception $e) {
            $error = 'Error updating user account: ' . $e->getMessage();
        }
    }
}

// ── 4. Handle Reset Password for Any User Account ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error) && isset($_POST['action']) && $_POST['action'] === 'reset_user_password') {
    $targetUserId = $_POST['user_id'] ?? '';
    $newPassword  = trim($_POST['new_password'] ?? '');

    if (!empty($targetUserId) && !empty($newPassword)) {
        try {
            $passHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $rStmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $rStmt->execute([$passHash, $targetUserId]);

            // Fetch user info for output card
            $usrStmt = $db->prepare("SELECT full_name, email, role FROM users WHERE id = ?");
            $usrStmt->execute([$targetUserId]);
            $uData = $usrStmt->fetch();

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'USER_PASSWORD_RESET', 'user', $targetUserId, "Reset password for user account ID: $targetUserId");

            $issuedCredentialsCard = [
                'type' => 'Updated Login Password Credentials',
                'title' => $uData['full_name'] ?? 'User Account',
                'lead_name' => $uData['full_name'] ?? 'User',
                'role' => ($uData['role'] === 'super_admin') ? 'Institutional Main Admin' : 'Club Lead',
                'email' => $uData['email'] ?? '',
                'password' => $newPassword,
                'user_id' => $targetUserId,
                'login_url' => 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/UIT/' . (($uData['role'] === 'super_admin') ? 'admin/dean-login.php' : 'club-login.php'),
                'permissions' => ['Password Reset Complete', 'Active Account Status']
            ];

            $message = "Password updated successfully for " . htmlspecialchars($uData['full_name'] ?? 'user') . "!";
        } catch (Exception $e) {
            $error = "Error resetting user password: " . $e->getMessage();
        }
    }
}

// ── 5. Handle Account Status Toggle (Active / Suspended) ─────────────
if (isset($_GET['toggle_user']) && isset($_GET['current_status'])) {
    $targetUserId = $_GET['toggle_user'];
    $currentStatus = $_GET['current_status'];
    $newStatus = ($currentStatus === 'active') ? 'suspended' : 'active';
    $reqTab = trim($_GET['tab'] ?? '');

    if ($targetUserId === $_SESSION['user_id']) {
        $error = "Security Policy: You cannot suspend your own logged-in account!";
    } else {
        try {
            // Determine user role for tab preservation
            $rStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $rStmt->execute([$targetUserId]);
            $targetRole = $rStmt->fetchColumn();
            $targetTab = !empty($reqTab) ? $reqTab : (($targetRole === 'club_admin') ? 'club-leads' : 'main-admins');

            $uStmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $uStmt->execute([$newStatus, $targetUserId]);

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'USER_STATUS_TOGGLED', 'user', $targetUserId, "Set account status to '$newStatus' for user ID: $targetUserId");

            header("Location: users.php?tab={$targetTab}&msg=" . urlencode("Account status updated to " . ucfirst($newStatus)));
            exit;
        } catch (Exception $e) {
            $error = "Error toggling account status: " . $e->getMessage();
        }
    }
}

// ── 6. Handle Delete User Account ────────────────────────────────────
if (isset($_GET['delete_user'])) {
    $targetUserId = $_GET['delete_user'];
    $reqTab = trim($_GET['tab'] ?? '');

    if ($targetUserId === $_SESSION['user_id']) {
        $error = "Security Policy: You cannot delete your own logged-in admin account!";
    } else {
        try {
            $rStmt = $db->prepare("SELECT role FROM users WHERE id = ?");
            $rStmt->execute([$targetUserId]);
            $targetRole = $rStmt->fetchColumn();
            $targetTab = !empty($reqTab) ? $reqTab : (($targetRole === 'club_admin') ? 'club-leads' : 'main-admins');

            $dStmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $dStmt->execute([$targetUserId]);

            log_audit($db, $_SESSION['user_id'], $_SESSION['full_name'] ?? 'Dean Sir', 'USER_DELETED', 'user', $targetUserId, "Permanently deleted user account ID: $targetUserId");
            header("Location: users.php?tab={$targetTab}&msg=" . urlencode("User account permanently deleted"));
            exit;
        } catch (Exception $e) {
            $error = "Error deleting user account: " . $e->getMessage();
        }
    }
}

// ── Fetch Roster Data ────────────────────────────────────────────────
// Fetch all Main Admin accounts (super_admin)
$mainAdmins = $db->query("
    SELECT * FROM users 
    WHERE role = 'super_admin' 
    ORDER BY created_at ASC
")->fetchAll();

// Fetch all registered Club Lead Accounts (club_admin)
$clubAdmins = $db->query("
    SELECT u.*, c.name as club_name, c.short_name as club_short, c.logo as club_logo, c.id as club_id
    FROM users u
    LEFT JOIN club_admins ca ON ca.user_id = u.id
    LEFT JOIN clubs c ON ca.club_id = c.id
    WHERE u.role = 'club_admin'
    ORDER BY u.created_at DESC
")->fetchAll();

// Statistics
$totalUsersCount = count($mainAdmins) + count($clubAdmins);
$mainAdminsCount = count($mainAdmins);
$clubAdminsCount = count($clubAdmins);
$activeUsersCount = count(array_filter(array_merge($mainAdmins, $clubAdmins), fn($u) => ($u['status'] ?? 'active') === 'active'));
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users & Governance Management | Dean Portal | ClubHub UIT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        body { background: #f8fafc; font-family: 'Inter', system-ui, -apple-system, sans-serif; color: #1e293b; }
        
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
        }
        .chip-icon-box {
            width: 40px; height: 40px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem; flex-shrink: 0;
        }

        .perm-switch-box {
            padding: 12px 16px !important;
            margin-bottom: 8px !important;
            border-radius: 14px !important;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }
        .perm-switch-box:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }

        .nav-pills-custom .nav-link {
            border-radius: 50rem;
            padding: 10px 20px;
            font-weight: 700;
            font-size: 0.85rem;
            color: #64748b;
            transition: all 0.2s ease;
        }
        .nav-pills-custom .nav-link.active {
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(124, 58, 237, 0.25);
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

        .pulse-dot-green {
            width: 8px; height: 8px; border-radius: 50%; background: #10b981;
            box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
            animation: pulseGreen 2s infinite; display: inline-block;
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
    <!-- Universal Sidebar -->
    <?php require_once __DIR__ . '/../../includes/super_sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="flex-grow-1 p-3 p-md-4 p-xl-5 overflow-y-auto">
        
        <!-- Header Banner -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <span class="badge bg-purple-subtle text-purple border rounded-pill px-3 py-1 fw-bold small" style="background:#f5f3ff; color:#7c3aed;">INSTITUTIONAL ACCESS & GOVERNANCE</span>
                <h2 class="fw-bold mb-1 text-dark mt-2">Manage System Users & Admin Permissions</h2>
                <p class="text-secondary small mb-0">Create new Main Admin accounts, assign fine-grained permissions, manage Club Lead credentials, and review governance privileges.</p>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-purple rounded-pill px-4 py-2-5 fw-bold text-white shadow-sm" style="background:linear-gradient(135deg,#7c3aed,#4f46e5); border:none;" data-bs-toggle="modal" data-bs-target="#createMainAdminModal">
                    <i class="bi bi-person-plus-fill me-1"></i> Add Main Admin
                </button>
                <button class="btn btn-primary rounded-pill px-4 py-2-5 fw-bold text-white shadow-sm" data-bs-toggle="modal" data-bs-target="#createClubModal">
                    <i class="bi bi-plus-circle-fill me-1"></i> Register New Club
                </button>
            </div>
        </div>

        <!-- Feedback Alert Messages -->
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_GET['msg']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($message) && !$issuedCredentialsCard): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4"><i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>

        <!-- ── PROMINENT ISSUED CREDENTIALS SUCCESS CARD ── -->
        <?php if ($issuedCredentialsCard): ?>
            <div class="card border-0 shadow-lg rounded-4 p-4 mb-4 text-dark" style="background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); border-left: 6px solid #10b981 !important;" id="issuedCredsCard">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-3 pb-3 border-bottom border-success-subtle">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm" style="width: 48px; height: 48px;">
                            <i class="bi bi-key-fill fs-3"></i>
                        </div>
                        <div>
                            <span class="badge bg-success text-white rounded-pill px-3 py-1 fw-bold small"><?= htmlspecialchars($issuedCredentialsCard['type']) ?></span>
                            <h4 class="fw-bold mb-0 text-success mt-1"><?= htmlspecialchars($issuedCredentialsCard['title']) ?></h4>
                        </div>
                    </div>
                    <button type="button" class="btn btn-success rounded-pill px-4 py-2 fw-bold text-white shadow-sm" onclick="copyIssuedCredentials()">
                        <i class="bi bi-clipboard-check me-1"></i> Copy All Credentials
                    </button>
                </div>

                <div class="row g-3 font-monospace small">
                    <div class="col-md-6 col-lg-3">
                        <div class="bg-white p-3 rounded-3 border">
                            <span class="text-secondary d-block mb-1 font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">NAME / TITLE</span>
                            <strong class="text-dark fs-6"><?= htmlspecialchars($issuedCredentialsCard['lead_name']) ?></strong>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="bg-white p-3 rounded-3 border">
                            <span class="text-secondary d-block mb-1 font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">ROLE DESIGNATION</span>
                            <span class="badge bg-purple-subtle text-purple border rounded-pill px-2.5 py-1 fw-bold" style="background:#f5f3ff; color:#7c3aed;"><?= htmlspecialchars($issuedCredentialsCard['role']) ?></span>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="bg-white p-3 rounded-3 border">
                            <span class="text-secondary d-block mb-1 font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">LOGIN EMAIL</span>
                            <code class="fs-6 text-primary" id="credEmailText"><?= htmlspecialchars($issuedCredentialsCard['email']) ?></code>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3">
                        <div class="bg-white p-3 rounded-3 border">
                            <span class="text-secondary d-block mb-1 font-sans-serif fw-bold text-uppercase" style="font-size:0.68rem;">ISSUED PASSWORD</span>
                            <div class="d-flex align-items-center justify-content-between">
                                <code class="fs-6 text-danger fw-bold" id="credPassText"><?= htmlspecialchars($issuedCredentialsCard['password']) ?></code>
                                <button type="button" class="btn btn-xs btn-link p-0 text-secondary" onclick="toggleTextVisibility('credPassText', this)">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="bg-white p-3 rounded-3 border d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                            <div>
                                <span class="text-secondary me-2 font-sans-serif fw-bold">PORTAL LOGIN URL:</span>
                                <a href="<?= htmlspecialchars($issuedCredentialsCard['login_url']) ?>" target="_blank" class="fw-bold text-primary text-decoration-underline" id="credUrlText"><?= htmlspecialchars($issuedCredentialsCard['login_url']) ?></a>
                            </div>
                            <span class="text-muted small font-sans-serif">Share these credentials securely with the designated administrator.</span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- 4 Compact Stat Chip Cards Deck -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-primary-subtle text-primary"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <div class="fw-bold text-dark lh-1" style="font-size:1.35rem;"><?= $totalUsersCount ?></div>
                        <div class="text-secondary small fw-semibold mt-1" style="font-size:0.75rem;">Total Registered Accounts</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-purple-subtle text-purple" style="background:#f5f3ff; color:#7c3aed;"><i class="bi bi-shield-lock-fill"></i></div>
                    <div>
                        <div class="fw-bold lh-1" style="font-size:1.35rem; color:#7c3aed;"><?= $mainAdminsCount ?></div>
                        <div class="text-secondary small fw-semibold mt-1" style="font-size:0.75rem;">Institutional Main Admins</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-info-subtle text-info"><i class="bi bi-person-badge-fill"></i></div>
                    <div>
                        <div class="fw-bold text-info lh-1" style="font-size:1.35rem;"><?= $clubAdminsCount ?></div>
                        <div class="text-secondary small fw-semibold mt-1" style="font-size:0.75rem;">Club Lead Accounts</div>
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-3">
                <div class="stat-chip-card">
                    <div class="chip-icon-box bg-success-subtle text-success"><i class="bi bi-check-circle-fill"></i></div>
                    <div>
                        <div class="d-flex align-items-center gap-2">
                            <span class="pulse-dot-green"></span>
                            <span class="fw-bold text-success lh-1" style="font-size:1.35rem;"><?= $activeUsersCount ?></span>
                        </div>
                        <div class="text-secondary small fw-semibold mt-1" style="font-size:0.75rem;">Active Login Status</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Custom Nav Tabs (Main Admins vs Club Leads) ── -->
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <ul class="nav nav-pills nav-pills-custom bg-white p-1.5 rounded-pill border shadow-xs" id="userTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="main-admins-tab" data-bs-toggle="pill" data-bs-target="#mainAdminsSection" type="button" role="tab">
                        <i class="bi bi-shield-lock-fill me-1.5"></i> Institutional Main Admins (<?= $mainAdminsCount ?>)
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="club-leads-tab" data-bs-toggle="pill" data-bs-target="#clubLeadsSection" type="button" role="tab">
                        <i class="bi bi-people-fill me-1.5"></i> Club Leads & Chapter Officers (<?= $clubAdminsCount ?>)
                    </button>
                </li>
            </ul>

            <div class="d-flex align-items-center gap-2">
                <input type="text" id="userDirectorySearch" class="form-control form-control-sm rounded-pill px-3" placeholder="🔍 Search accounts..." style="width: 210px;">
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary rounded-pill px-3 dropdown-toggle fw-semibold" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-download me-1"></i> Export Data
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end rounded-4 shadow border-0 p-2">
                        <li><a class="dropdown-item rounded-3 small py-2 fw-medium" href="#" onclick="const activeTab = document.querySelector('#userTabs .nav-link.active')?.id; const tableId = (activeTab === 'club-leads-tab') ? 'clubLeadsTable' : 'mainAdminsTable'; const name = (activeTab === 'club-leads-tab') ? 'Club-Leads-Roster' : 'Main-Admins-Roster'; ClubHubExporter.exportCSV(tableId, name); return false;"><i class="bi bi-filetype-csv text-success me-2 fs-6"></i> Export CSV (.csv)</a></li>
                        <li><a class="dropdown-item rounded-3 small py-2 fw-medium" href="#" onclick="const activeTab = document.querySelector('#userTabs .nav-link.active')?.id; const tableId = (activeTab === 'club-leads-tab') ? 'clubLeadsTable' : 'mainAdminsTable'; const name = (activeTab === 'club-leads-tab') ? 'Club-Leads-Roster' : 'Main-Admins-Roster'; ClubHubExporter.exportExcel(tableId, name); return false;"><i class="bi bi-file-earmark-excel text-success me-2 fs-6"></i> Export Excel (.xls)</a></li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item rounded-3 small py-2 fw-medium" href="#" onclick="const activeTab = document.querySelector('#userTabs .nav-link.active')?.id; const tableId = (activeTab === 'club-leads-tab') ? 'clubLeadsTable' : 'mainAdminsTable'; const title = (activeTab === 'club-leads-tab') ? 'Campus Club Leads Directory' : 'Institutional Main Admins Directory'; ClubHubExporter.exportPDF(tableId, title); return false;"><i class="bi bi-file-earmark-pdf text-danger me-2 fs-6"></i> Print / Save PDF Report</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="tab-content" id="userTabsContent">
            
            <!-- ── TAB 1: INSTITUTIONAL MAIN ADMINS ── -->
            <div class="tab-pane fade show active" id="mainAdminsSection" role="tabpanel">
                <div class="row g-4">
                    
                    <!-- Left Column: Personal Profile Update Card -->
                    <div class="col-lg-4">
                        <div class="card p-4 border-0 shadow-sm rounded-4 bg-white mb-4">
                            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                                <div class="bg-indigo text-white rounded-circle d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 52px; height: 52px; background: linear-gradient(135deg,#6366f1,#a855f7);">
                                    <?= strtoupper(substr($loggedUser['full_name'] ?? 'D', 0, 1)) ?>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($loggedUser['full_name']) ?></h6>
                                    <span class="badge bg-purple-subtle text-purple border rounded-pill px-2.5 py-0.5 small" style="background:#f5f3ff; color:#7c3aed;">YOUR LOGGED-IN ACCOUNT</span>
                                </div>
                            </div>

                            <form action="users.php" method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                
                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Your Full Name</label>
                                    <input type="text" name="full_name" class="form-control rounded-3" value="<?= htmlspecialchars($loggedUser['full_name']) ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-semibold">Your Login Email</label>
                                    <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($loggedUser['email']) ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-semibold">New Password (blank = keep current)</label>
                                    <div class="input-group">
                                        <input type="password" name="new_password" id="personalPassInput" class="form-control rounded-start-3" placeholder="••••••••">
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('personalPassInput', this)">
                                            <i class="bi bi-eye-slash"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary small fw-bold px-2.5" onclick="generateStrongPassword('personalPassInput')">
                                            Auto Pass
                                        </button>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary rounded-pill w-100 fw-bold py-2.5 text-white shadow-sm">
                                    <i class="bi bi-shield-check me-1"></i> Update Your Credentials
                                </button>
                            </form>
                        </div>

                        <!-- Security Audit Protocol Card -->
                        <div class="card p-4 border-0 shadow-sm rounded-4 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-shield-lock-fill text-warning fs-5"></i>
                                <h6 class="fw-bold mb-0 text-white">Super Admin Access Control</h6>
                            </div>
                            <p class="small text-white-50 mb-3">Main Admins have elevated institutional authority across all chapters, event proposals, budget approvals, and user accounts.</p>
                            <div class="small font-monospace text-white-50">
                                <div>Session Role: <code>SUPER_ADMIN</code></div>
                                <div>Audit Logging: <code>ACTIVE (ENFORCED)</code></div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Main Admins Table -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                            <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between">
                                <h6 class="fw-bold text-dark mb-0"><i class="bi bi-shield-lock text-purple me-2"></i>Institutional Main Admin Accounts Directory</h6>
                                <button class="btn btn-sm btn-purple rounded-pill px-3 py-1 fw-bold text-white" style="background:linear-gradient(135deg,#7c3aed,#4f46e5); border:none;" data-bs-toggle="modal" data-bs-target="#createMainAdminModal">
                                    <i class="bi bi-plus-lg me-1"></i> Add New Main Admin
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="mainAdminsTable">
                                    <thead class="table-light">
                                        <tr class="small text-secondary">
                                            <th>ADMIN USER</th>
                                            <th>OFFICIAL EMAIL</th>
                                            <th>PERMISSIONS SCOPE</th>
                                            <th>STATUS</th>
                                            <th class="text-end">ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mainAdmins as $ma): 
                                            $userPerms = !empty($ma['permissions']) ? json_decode($ma['permissions'], true) : ['manage_clubs', 'approve_proposals', 'reset_credentials'];
                                            $permCount = is_array($userPerms) ? count($userPerms) : 0;
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center gap-2.5">
                                                        <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold small flex-shrink-0" style="width:36px;height:36px; background:linear-gradient(135deg,#7c3aed,#4f46e5);">
                                                            <?= strtoupper(substr($ma['full_name'], 0, 1)) ?>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold text-dark mb-0"><?= htmlspecialchars($ma['full_name']) ?></div>
                                                            <?php if ($ma['id'] === $_SESSION['user_id']): ?>
                                                                <span class="badge bg-success-subtle text-success rounded-pill px-2 py-0.5" style="font-size:0.65rem;">Logged In (You)</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="small font-monospace text-secondary"><?= htmlspecialchars($ma['email']) ?></td>
                                                <td>
                                                    <span class="badge bg-purple-subtle text-purple border rounded-pill px-2.5 py-1 small" style="background:#f5f3ff; color:#7c3aed;">
                                                        <i class="bi bi-sliders me-1"></i> <?= ($permCount >= 5 || empty($ma['permissions'])) ? 'Full Access' : "Custom ($permCount Permissions)" ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php $isMaActive = (($ma['status'] ?? 'active') === 'active'); ?>
                                                    <?php if ($ma['id'] !== $_SESSION['user_id']): ?>
                                                        <a href="users.php?toggle_user=<?= $ma['id'] ?>&current_status=<?= $ma['status'] ?? 'active' ?>&tab=main-admins" class="text-decoration-none" title="Click to toggle active status">
                                                            <div class="d-inline-flex align-items-center gap-2 px-2.5 py-1 rounded-pill bg-light border shadow-2xs">
                                                                <span class="toggle-switch <?= $isMaActive ? 'active' : '' ?>">
                                                                    <span class="toggle-switch-handle"></span>
                                                                </span>
                                                                <span class="toggle-status-label <?= $isMaActive ? 'active' : 'inactive' ?>">
                                                                    <?= $isMaActive ? 'ON (Active)' : 'OFF (Suspended)' ?>
                                                                </span>
                                                            </div>
                                                        </a>
                                                    <?php else: ?>
                                                        <div class="d-inline-flex align-items-center gap-2 px-2.5 py-1 rounded-pill bg-light border">
                                                            <span class="toggle-switch active">
                                                                <span class="toggle-switch-handle"></span>
                                                            </span>
                                                            <span class="toggle-status-label active">ON (You)</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">
                                                    <div class="btn-group">
                                                        <!-- Edit User Trigger -->
                                                        <button type="button" class="btn btn-sm btn-light rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#editUserModal_<?= $ma['id'] ?>" title="Edit User Details & Permissions">
                                                            <i class="bi bi-pencil-fill text-dark"></i>
                                                        </button>

                                                        <!-- Reset Password Trigger -->
                                                        <button type="button" class="btn btn-sm btn-light rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#resetPassModal_<?= $ma['id'] ?>" title="Reset Password">
                                                            <i class="bi bi-key-fill text-warning"></i>
                                                        </button>

                                                        <?php if ($ma['id'] !== $_SESSION['user_id']): ?>
                                                            <a href="users.php?delete_user=<?= $ma['id'] ?>" onclick="return confirm('Are you sure you want to delete admin account <?= htmlspecialchars($ma['full_name']) ?>?');" class="btn btn-sm btn-light text-danger rounded-circle" title="Delete Admin">
                                                                <i class="bi bi-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>

                                                    <!-- Edit Admin & Permissions Modal -->
                                                    <div class="modal fade text-start" id="editUserModal_<?= $ma['id'] ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-dialog-centered modal-lg">
                                                            <div class="modal-content rounded-4 border-0 shadow">
                                                                <div class="modal-header border-0 pb-0 p-4">
                                                                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Account & Permissions: <?= htmlspecialchars($ma['full_name']) ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form action="users.php" method="POST">
                                                                    <input type="hidden" name="action" value="edit_user">
                                                                    <input type="hidden" name="user_id" value="<?= $ma['id'] ?>">
                                                                    <div class="modal-body p-4">
                                                                        <div class="row g-3 mb-3">
                                                                            <div class="col-md-6">
                                                                                <label class="form-label small fw-semibold">Full Name *</label>
                                                                                <input type="text" name="full_name" class="form-control rounded-3" value="<?= htmlspecialchars($ma['full_name']) ?>" required>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <label class="form-label small fw-semibold">Official Email *</label>
                                                                                <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($ma['email']) ?>" required>
                                                                            </div>
                                                                            <div class="col-12">
                                                                                <label class="form-label small fw-semibold">Account Status</label>
                                                                                <select name="status" class="form-select rounded-3">
                                                                                    <option value="active" <?= ($ma['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Allowed Login)</option>
                                                                                    <option value="suspended" <?= ($ma['status'] ?? 'active') === 'suspended' ? 'selected' : '' ?>>Suspended (Blocked Login)</option>
                                                                                </select>
                                                                            </div>
                                                                        </div>

                                                                        <hr class="my-4">

                                                                        <!-- Permissions Matrix Toggles -->
                                                                        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-sliders me-2 text-primary"></i>Administrative Permissions Matrix</h6>
                                                                        <div class="row g-3">
                                                                            <div class="col-md-6">
                                                                                <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                                                                    <label class="form-check-label small fw-bold mb-0" for="edit_perm1_<?= $ma['id'] ?>">Manage Clubs & Privacy</label>
                                                                                    <input class="form-check-input ms-0" type="checkbox" name="permissions[]" value="manage_clubs" id="edit_perm1_<?= $ma['id'] ?>" <?= (is_array($userPerms) && in_array('manage_clubs', $userPerms)) ? 'checked' : '' ?>>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                                                                    <label class="form-check-label small fw-bold mb-0" for="edit_perm2_<?= $ma['id'] ?>">Approve / Reject Proposals</label>
                                                                                    <input class="form-check-input ms-0" type="checkbox" name="permissions[]" value="approve_proposals" id="edit_perm2_<?= $ma['id'] ?>" <?= (is_array($userPerms) && in_array('approve_proposals', $userPerms)) ? 'checked' : '' ?>>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                                                                    <label class="form-check-label small fw-bold mb-0" for="edit_perm3_<?= $ma['id'] ?>">Issue / Reset Credentials</label>
                                                                                    <input class="form-check-input ms-0" type="checkbox" name="permissions[]" value="reset_credentials" id="edit_perm3_<?= $ma['id'] ?>" <?= (is_array($userPerms) && in_array('reset_credentials', $userPerms)) ? 'checked' : '' ?>>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                                                                    <label class="form-check-label small fw-bold mb-0" for="edit_perm4_<?= $ma['id'] ?>">Manage Admin User Accounts</label>
                                                                                    <input class="form-check-input ms-0" type="checkbox" name="permissions[]" value="manage_users" id="edit_perm4_<?= $ma['id'] ?>" <?= (is_array($userPerms) && in_array('manage_users', $userPerms)) ? 'checked' : '' ?>>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                                                                    <label class="form-check-label small fw-bold mb-0" for="edit_perm5_<?= $ma['id'] ?>">View Security Audit Logs</label>
                                                                                    <input class="form-check-input ms-0" type="checkbox" name="permissions[]" value="audit_logs" id="edit_perm5_<?= $ma['id'] ?>" <?= (is_array($userPerms) && in_array('audit_logs', $userPerms)) ? 'checked' : '' ?>>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                                                                    <label class="form-check-label small fw-bold mb-0" for="edit_perm6_<?= $ma['id'] ?>">Manage Categories & Domains</label>
                                                                                    <input class="form-check-input ms-0" type="checkbox" name="permissions[]" value="categories_manage" id="edit_perm6_<?= $ma['id'] ?>" <?= (is_array($userPerms) && in_array('categories_manage', $userPerms)) ? 'checked' : '' ?>>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer border-0 pt-0 p-4">
                                                                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Save Changes</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- Reset Password Modal -->
                                                    <div class="modal fade text-start" id="resetPassModal_<?= $ma['id'] ?>" tabindex="-1">
                                                        <div class="modal-dialog modal-dialog-centered">
                                                            <div class="modal-content rounded-4 border-0 shadow">
                                                                <div class="modal-header border-0 pb-0">
                                                                    <h5 class="modal-title fw-bold text-dark"><i class="bi bi-key-fill text-warning me-2"></i> Reset Password for <?= htmlspecialchars($ma['full_name']) ?></h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <form action="users.php" method="POST">
                                                                    <input type="hidden" name="action" value="reset_user_password">
                                                                    <input type="hidden" name="user_id" value="<?= $ma['id'] ?>">
                                                                    <div class="modal-body">
                                                                        <p class="small text-secondary mb-3">Issue new login password for <code><?= htmlspecialchars($ma['email']) ?></code>.</p>
                                                                        <div class="mb-3">
                                                                            <label class="form-label small fw-semibold">New Password *</label>
                                                                            <div class="input-group">
                                                                                <input type="password" name="new_password" id="resetPassInput_<?= htmlspecialchars($ma['id']) ?>" class="form-control rounded-start-3" placeholder="Enter new password" required>
                                                                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('resetPassInput_<?= htmlspecialchars($ma['id']) ?>', this)">
                                                                                    <i class="bi bi-eye-slash"></i>
                                                                                </button>
                                                                                <button type="button" class="btn btn-outline-primary small fw-bold px-2.5" onclick="generateStrongPassword('resetPassInput_<?= htmlspecialchars($ma['id']) ?>')">
                                                                                    Auto Pass
                                                                                </button>
                                                                            </div>
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
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── TAB 2: CLUB LEADS & CHAPTER OFFICERS ── -->
            <div class="tab-pane fade" id="clubLeadsSection" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                    <div class="card-header bg-white border-bottom py-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold text-dark mb-0"><i class="bi bi-people text-primary me-2"></i>Registered Club Lead Accounts Directory</h6>
                            <span class="text-secondary small">Student chapter presidents and official club login accounts</span>
                        </div>
                        <button class="btn btn-sm btn-primary rounded-pill px-3 py-1 fw-bold text-white" data-bs-toggle="modal" data-bs-target="#createClubModal">
                            <i class="bi bi-plus-lg me-1"></i> Register New Chapter & Lead
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="clubLeadsTable">
                            <thead class="table-light">
                                <tr class="small text-secondary">
                                    <th>LEAD NAME</th>
                                    <th>ASSIGNED CHAPTER</th>
                                    <th>LOGIN EMAIL</th>
                                    <th>STATUS</th>
                                    <th class="text-end">ACTIONS</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($clubAdmins)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">No club lead accounts registered yet.</td>
                                    </tr>
                                <?php else: foreach ($clubAdmins as $ca): ?>
                                    <tr>
                                        <td class="fw-bold text-dark">
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded-circle bg-light border text-primary d-flex align-items-center justify-content-center fw-bold small flex-shrink-0" style="width:36px;height:36px;">
                                                    <?= strtoupper(substr($ca['full_name'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div><?= htmlspecialchars($ca['full_name']) ?></div>
                                                    <span class="badge bg-secondary-subtle text-secondary" style="font-size:0.65rem;">Club Admin</span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($ca['club_id']): ?>
                                                <a href="club-detail.php?id=<?= $ca['club_id'] ?>" class="text-decoration-none d-inline-flex align-items-center gap-2">
                                                    <img src="<?= htmlspecialchars($ca['club_logo'] ?: '../../assets/United Logo.webp') ?>" class="rounded-2 border" style="width:24px;height:24px;object-fit:cover;" alt="">
                                                    <span class="badge bg-success-subtle text-success border rounded-pill px-2.5 py-1 small">
                                                        <?= htmlspecialchars($ca['club_short'] ?: $ca['club_name']) ?>
                                                    </span>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-danger small"><i class="bi bi-exclamation-circle me-1"></i> Unassigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small font-monospace text-secondary"><?= htmlspecialchars($ca['email']) ?></td>
                                        <td>
                                            <?php $isLeadActive = (($ca['status'] ?? 'active') === 'active'); ?>
                                            <a href="users.php?toggle_user=<?= $ca['id'] ?>&current_status=<?= $ca['status'] ?? 'active' ?>&tab=club-leads" class="text-decoration-none" title="Click to toggle status (Active / Suspended)">
                                                <div class="d-inline-flex align-items-center gap-2 px-2.5 py-1 rounded-pill bg-light border shadow-2xs">
                                                    <span class="toggle-switch <?= $isLeadActive ? 'active' : '' ?>">
                                                        <span class="toggle-switch-handle"></span>
                                                    </span>
                                                    <span class="toggle-status-label <?= $isLeadActive ? 'active' : 'inactive' ?>">
                                                        <?= $isLeadActive ? 'ON (Active)' : 'OFF (Suspended)' ?>
                                                    </span>
                                                </div>
                                            </a>
                                        </td>
                                        <td class="text-end">
                                            <div class="btn-group">
                                                <!-- View Assigned Club Detail -->
                                                <?php if ($ca['club_id']): ?>
                                                    <a href="club-detail.php?id=<?= $ca['club_id'] ?>" class="btn btn-sm btn-light rounded-circle me-1" title="View Executive Club Overview">
                                                        <i class="bi bi-bar-chart-line-fill" style="color:#7c3aed;"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <!-- Edit Lead Account Details Trigger -->
                                                <button type="button" class="btn btn-sm btn-light rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#editUserModal_<?= $ca['id'] ?>" title="Edit Lead Account Details">
                                                    <i class="bi bi-pencil-fill text-dark"></i>
                                                </button>

                                                <!-- Reset Password Trigger -->
                                                <button type="button" class="btn btn-sm btn-light rounded-circle me-1" data-bs-toggle="modal" data-bs-target="#resetLeadPassModal_<?= $ca['id'] ?>" title="Reset Leader Password">
                                                    <i class="bi bi-key-fill text-warning"></i>
                                                </button>

                                                <!-- Delete User -->
                                                <a href="users.php?delete_user=<?= $ca['id'] ?>" onclick="return confirm('Are you sure you want to delete lead account <?= htmlspecialchars($ca['full_name']) ?>?');" class="btn btn-sm btn-light text-danger rounded-circle" title="Delete Account">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </div>

                                            <!-- Edit User Modal -->
                                            <div class="modal fade text-start" id="editUserModal_<?= $ca['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content rounded-4 border-0 shadow">
                                                        <div class="modal-header border-0 pb-0 p-4">
                                                            <h5 class="modal-title fw-bold text-dark"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Account: <?= htmlspecialchars($ca['full_name']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="users.php" method="POST">
                                                            <input type="hidden" name="action" value="edit_user">
                                                            <input type="hidden" name="user_id" value="<?= $ca['id'] ?>">
                                                            <div class="modal-body p-4">
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-semibold">Lead Full Name *</label>
                                                                    <input type="text" name="full_name" class="form-control rounded-3" value="<?= htmlspecialchars($ca['full_name']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-semibold">Official Admin Email *</label>
                                                                    <input type="email" name="email" class="form-control rounded-3" value="<?= htmlspecialchars($ca['email']) ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-semibold">Account Status</label>
                                                                    <select name="status" class="form-select rounded-3">
                                                                        <option value="active" <?= ($ca['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active (Allowed Login)</option>
                                                                        <option value="suspended" <?= ($ca['status'] ?? 'active') === 'suspended' ? 'selected' : '' ?>>Suspended (Blocked Login)</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0 pt-0 p-4">
                                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold text-white">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Reset Password Modal -->
                                            <div class="modal fade text-start" id="resetLeadPassModal_<?= $ca['id'] ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content rounded-4 border-0 shadow">
                                                        <div class="modal-header border-0 pb-0 p-4">
                                                            <h5 class="modal-title fw-bold text-dark"><i class="bi bi-key-fill text-warning me-2"></i> Reset Password for <?= htmlspecialchars($ca['full_name']) ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form action="users.php" method="POST">
                                                            <input type="hidden" name="action" value="reset_user_password">
                                                            <input type="hidden" name="user_id" value="<?= $ca['id'] ?>">
                                                            <div class="modal-body p-4">
                                                                <p class="small text-secondary mb-3">Set new password for club login <code><?= htmlspecialchars($ca['email']) ?></code>.</p>
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-semibold">New Password *</label>
                                                                    <div class="input-group">
                                                                        <input type="password" name="new_password" id="resetLeadPassInput_<?= htmlspecialchars($ca['id']) ?>" class="form-control rounded-start-3" placeholder="Enter new password" required>
                                                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('resetLeadPassInput_<?= htmlspecialchars($ca['id']) ?>', this)">
                                                                            <i class="bi bi-eye-slash"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-outline-primary small fw-bold px-2.5" onclick="generateStrongPassword('resetLeadPassInput_<?= htmlspecialchars($ca['id']) ?>')">
                                                                            Auto Pass
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0 pt-0 p-4">
                                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-warning rounded-pill px-4 fw-bold text-dark">Update Password</button>
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

    </div>
</div>

<!-- ── CREATE NEW MAIN ADMIN MODAL ── -->
<div class="modal fade text-start" id="createMainAdminModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom p-4">
                <div>
                    <span class="badge bg-purple-subtle text-purple border rounded-pill px-3 py-1 fw-bold small" style="background:#f5f3ff; color:#7c3aed;">SUPER ADMIN PROVISIONING</span>
                    <h5 class="modal-title fw-bold text-dark mt-1"><i class="bi bi-shield-plus text-purple me-2" style="color:#7c3aed;"></i>Create New Main Admin Account</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="users.php" method="POST">
                <input type="hidden" name="action" value="create_main_admin">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Full Name *</label>
                            <input type="text" name="full_name" class="form-control rounded-3" placeholder="e.g. Dr. Rajesh Sharma" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Institutional Email *</label>
                            <input type="email" name="email" class="form-control rounded-3" placeholder="e.g. rajesh.sharma@uit.edu" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Initial Password *</label>
                            <div class="input-group">
                                <input type="password" name="password" id="newAdminPasswordInput" class="form-control rounded-start-3" placeholder="••••••••" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('newAdminPasswordInput', this)" title="Toggle Eye View">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary fw-bold px-3" onclick="generateStrongPassword('newAdminPasswordInput')" title="Auto Generate Password">
                                    <i class="bi bi-magic me-1"></i> Auto Generate Password
                                </button>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Role Designation Title</label>
                            <select name="designation" id="adminRoleDesignationSelect" class="form-select rounded-3">
                                <option value="Associate Dean">Associate Dean (Full Access)</option>
                                <option value="USC UIT Coordinator">USC UIT Coordinator (Clubs & Proposals)</option>
                                <option value="System Administrator">System Administrator (IT & Users)</option>
                                <option value="Faculty Advisor">Faculty Advisor (Proposals Oversight)</option>
                                <option value="Institutional Admin" selected>Institutional Admin (Standard)</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Administrative Permissions Matrix Toggles -->
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-sliders me-2 text-primary"></i>Administrative Permissions Matrix</h6>
                        <span class="badge bg-primary-subtle text-primary border rounded-pill px-2.5 py-1 small" id="presetBadge">Preset Applied</span>
                    </div>
                    <p class="text-secondary small mb-3">Selecting a Role Title automatically toggles its recommended permissions level. You can also manually toggle any switch on or off.</p>

                    <div class="row g-2" id="permissionsContainer">
                        <div class="col-md-6">
                            <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                <label class="form-check-label small fw-bold mb-0" for="perm_manage_clubs">Manage Clubs & Privacy</label>
                                <input class="form-check-input perm-check ms-0" type="checkbox" name="permissions[]" value="manage_clubs" id="perm_manage_clubs" checked>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                <label class="form-check-label small fw-bold mb-0" for="perm_approve_proposals">Approve / Reject Proposals</label>
                                <input class="form-check-input perm-check ms-0" type="checkbox" name="permissions[]" value="approve_proposals" id="perm_approve_proposals" checked>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                <label class="form-check-label small fw-bold mb-0" for="perm_reset_credentials">Issue / Reset Credentials</label>
                                <input class="form-check-input perm-check ms-0" type="checkbox" name="permissions[]" value="reset_credentials" id="perm_reset_credentials" checked>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                <label class="form-check-label small fw-bold mb-0" for="perm_manage_users">Manage Admin User Accounts</label>
                                <input class="form-check-input perm-check ms-0" type="checkbox" name="permissions[]" value="manage_users" id="perm_manage_users">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                <label class="form-check-label small fw-bold mb-0" for="perm_audit_logs">View Security Audit Logs</label>
                                <input class="form-check-input perm-check ms-0" type="checkbox" name="permissions[]" value="audit_logs" id="perm_audit_logs">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch perm-switch-box d-flex align-items-center justify-content-between ps-3">
                                <label class="form-check-label small fw-bold mb-0" for="perm_categories_manage">Manage Categories & Domains</label>
                                <input class="form-check-input perm-check ms-0" type="checkbox" name="permissions[]" value="categories_manage" id="perm_categories_manage">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-top p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-purple rounded-pill px-4 fw-bold text-white shadow-sm" style="background:linear-gradient(135deg,#7c3aed,#4f46e5); border:none;">
                        <i class="bi bi-person-check-fill me-1"></i> Provision Admin Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ── CREATE NEW CLUB & ISSUE CREDENTIALS MODAL ── -->
<div class="modal fade" id="createClubModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 p-4">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-trophy-fill text-primary me-2"></i> Add New Campus Club & Issue Credentials</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="users.php" method="POST">
                <input type="hidden" name="action" value="create_club">
                <div class="modal-body p-4">
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
                            <div class="input-group">
                                <input type="password" name="admin_password" id="newClubPasswordInput" class="form-control rounded-start-3" placeholder="Set initial password for team" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('newClubPasswordInput', this)" title="Toggle Eye View">
                                    <i class="bi bi-eye-slash"></i>
                                </button>
                                <button type="button" class="btn btn-outline-primary fw-bold px-3" onclick="generateStrongPassword('newClubPasswordInput')" title="Auto Generate Password">
                                    <i class="bi bi-magic me-1"></i> Auto Generate Password
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 p-4">
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
    // Toggle Password Visibility Eye Icon
    function togglePasswordVisibility(inputId, btn) {
        const input = document.getElementById(inputId);
        if (!input) return;
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            if (icon) { icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
        } else {
            input.type = 'password';
            if (icon) { icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
        }
    }

    // Toggle Password Text Visibility inside Credentials Card
    function toggleTextVisibility(elementId, btn) {
        const el = document.getElementById(elementId);
        if (!el) return;
        const icon = btn.querySelector('i');
        if (el.dataset.hidden === 'true') {
            el.innerText = el.dataset.realText;
            el.dataset.hidden = 'false';
            if (icon) { icon.classList.remove('bi-eye'); icon.classList.add('bi-eye-slash'); }
        } else {
            if (!el.dataset.realText) el.dataset.realText = el.innerText;
            el.innerText = '••••••••••••';
            el.dataset.hidden = 'true';
            if (icon) { icon.classList.remove('bi-eye-slash'); icon.classList.add('bi-eye'); }
        }
    }

    // Auto Generate Strong Random Password
    function generateStrongPassword(inputId) {
        const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%&*';
        let pass = '';
        for (let i = 0; i < 12; i++) {
            pass += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        const input = document.getElementById(inputId);
        if (input) {
            input.type = 'text';
            input.value = pass;
        }
    }

    // Copy Issued Credentials to Clipboard
    function copyIssuedCredentials() {
        const email = document.getElementById('credEmailText')?.innerText || '';
        const pass = document.getElementById('credPassText')?.innerText || '';
        const url = document.getElementById('credUrlText')?.innerText || '';
        const cardTitle = document.querySelector('#issuedCredsCard h4')?.innerText || 'Account Credentials';

        const textToCopy = `=== ${cardTitle} ===\nLogin URL: ${url}\nEmail: ${email}\nPassword: ${pass}\n`;

        navigator.clipboard.writeText(textToCopy).then(() => {
            alert('Credentials copied to clipboard successfully!\nYou can now paste and send these credentials to the admin/team lead.');
        }).catch(err => {
            alert('Failed to copy: ' + err);
        });
    }

    // Dynamic Role Preset Permissions Mapping
    const rolePermissionsMap = {
        "Associate Dean": ["manage_clubs", "approve_proposals", "reset_credentials", "manage_users", "audit_logs", "categories_manage"],
        "USC UIT Coordinator": ["manage_clubs", "approve_proposals", "audit_logs", "categories_manage"],
        "SAC Coordinator": ["manage_clubs", "approve_proposals", "audit_logs", "categories_manage"],
        "System Administrator": ["manage_clubs", "reset_credentials", "manage_users", "audit_logs"],
        "Faculty Advisor": ["approve_proposals"],
        "Institutional Admin": ["manage_clubs", "approve_proposals", "reset_credentials"]
    };

    const roleSelect = document.getElementById('adminRoleDesignationSelect');
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            const role = this.value;
            const allowed = rolePermissionsMap[role] || [];
            document.querySelectorAll('#permissionsContainer .perm-check').forEach(cb => {
                cb.checked = allowed.includes(cb.value);
            });
            const badge = document.getElementById('presetBadge');
            if (badge) badge.innerText = role + ' Preset Applied';
        });
    }

    // Live Instant Filter across both tables
    const searchInput = document.getElementById('userDirectorySearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function() {
            const query = this.value.toLowerCase().trim();
            document.querySelectorAll('tbody tr').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // Active Tab Memory & URL Parameter Activation Engine
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        const storedTab = localStorage.getItem('activeUserDirectoryTab');
        const targetTab = tabParam || storedTab;

        if (targetTab === 'club-leads') {
            const clubTabBtn = document.getElementById('club-leads-tab');
            if (clubTabBtn) {
                bootstrap.Tab.getOrCreateInstance(clubTabBtn).show();
            }
        } else if (targetTab === 'main-admins') {
            const mainTabBtn = document.getElementById('main-admins-tab');
            if (mainTabBtn) {
                bootstrap.Tab.getOrCreateInstance(mainTabBtn).show();
            }
        }

        // Store active tab whenever a tab is clicked
        document.querySelectorAll('#userTabs button[data-bs-toggle="pill"]').forEach(tabBtn => {
            tabBtn.addEventListener('shown.bs.tab', function(e) {
                const id = e.target.id;
                if (id === 'club-leads-tab') {
                    localStorage.setItem('activeUserDirectoryTab', 'club-leads');
                } else if (id === 'main-admins-tab') {
                    localStorage.setItem('activeUserDirectoryTab', 'main-admins');
                }
            });
        });
    });
</script>
</body>
</html>
