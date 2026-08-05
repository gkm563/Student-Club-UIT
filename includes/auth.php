<?php
/**
 * Authentication & Security Management Engine for ClubHub UIT
 * Enforces strict role-based access control, CSRF tokens, CAPTCHA verification, and Security Headers.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Strict');
    session_start();
}

// Session Inactivity Timeout Guard (60 minutes)
if (isset($_SESSION['user_id'])) {
    $maxInactiveSeconds = 3600;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > $maxInactiveSeconds)) {
        session_unset();
        session_destroy();
        session_start();
    } else {
        $_SESSION['last_activity'] = time();
    }
}

// Load Campus Cyber Security Firewall WAF Defense Engine
require_once __DIR__ . '/security_firewall.php';

// 1. Security Headers Execution
function apply_security_headers(): void {
    if (!headers_sent()) {
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("X-XSS-Protection: 1; mode=block");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
}
apply_security_headers();

// 2. CSRF Token Engine
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function get_csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function verify_csrf_token(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

// 3. Dynamic CAPTCHA Verification Engine
function generate_captcha_code(): string {
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'; // Exclude easily confused chars (0, O, 1, I)
    $code = '';
    for ($i = 0; $i < 5; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    $_SESSION['captcha_code'] = $code;
    return $code;
}

function get_captcha_code(): string {
    if (empty($_SESSION['captcha_code'])) {
        return generate_captcha_code();
    }
    return $_SESSION['captcha_code'];
}

function verify_captcha_code(?string $inputCode): bool {
    if (empty($_SESSION['captcha_code']) || empty($inputCode)) {
        return false;
    }
    $sessionCode = trim((string)$_SESSION['captcha_code']);
    $userCode    = trim((string)$inputCode);
    return (strcasecmp($sessionCode, $userCode) === 0);
}

// 4. Role & Auth Status Engine
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function get_current_user_role(): string {
    return $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'guest';
}

function get_current_user_id(): ?string {
    return $_SESSION['user_id'] ?? null;
}

function get_current_user_name(): string {
    return $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'Guest';
}

function get_assigned_club_id(): ?string {
    return $_SESSION['assigned_club_id'] ?? null;
}

function require_login(string $redirectUrl = 'club-login.php'): void {
    if (!is_logged_in()) {
        header("Location: $redirectUrl");
        exit;
    }

    // Live Account Status Enforcement Check
    if (class_exists('Database')) {
        try {
            $dbCheck = Database::getConnection();
            $chkStmt = $dbCheck->prepare("SELECT status FROM users WHERE id = ?");
            $chkStmt->execute([$_SESSION['user_id']]);
            $uStatus = $chkStmt->fetchColumn();
            
            if ($uStatus && $uStatus !== 'active') {
                session_unset();
                session_destroy();
                header("Location: $redirectUrl?error=" . urlencode("Account Suspended: Your access has been suspended by the Dean of Student Affairs. Contact USC UIT governance."));
                exit;
            }
        } catch (Exception $e) {
            // Fail safe on database check
        }
    }
}

function require_super_admin(): void {
    require_login('login.php');
    if (get_current_user_role() !== 'super_admin') {
        header("Location: login.php?error=" . urlencode("Access Denied: Dean Sir Super Admin privileges required."));
        exit;
    }
}

function get_user_permissions(): array {
    if (!isset($_SESSION['user_permissions']) && isset($_SESSION['user_id'])) {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT permissions FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $raw = $stmt->fetchColumn();
            $_SESSION['user_permissions'] = (!empty($raw) && $raw !== 'null') ? json_decode($raw, true) : ['ALL'];
        } catch (Exception $e) {
            $_SESSION['user_permissions'] = ['ALL'];
        }
    }
    return $_SESSION['user_permissions'] ?? ['ALL'];
}

function has_permission(string $permKey): bool {
    if (get_current_user_role() === 'super_admin') {
        $perms = get_user_permissions();
        if (in_array('ALL', $perms) || empty($perms)) {
            return true;
        }
        return in_array($permKey, $perms);
    }
    return false;
}

function require_permission(string $permKey): void {
    if (!has_permission($permKey)) {
        header("Location: index.php?error=" . urlencode("Access Denied: You do not have permission ($permKey) granted to your admin role."));
        exit;
    }
}

function require_club_admin(): void {
    require_login('club-login.php');
    $role = get_current_user_role();
    if ($role !== 'club_admin' && $role !== 'super_admin') {
        header("Location: club-login.php?error=" . urlencode("Access Denied: Club Leadership privileges required."));
        exit;
    }
}

/**
 * Enforce Strict Anti-IDOR Authorization Check for Club Resources
 */
function verify_club_ownership(?string $targetClubId): void {
    if (get_current_user_role() === 'super_admin') {
        return; // Institutional Super Admin has global oversight
    }

    $assignedClubId = get_assigned_club_id();
    if (empty($assignedClubId) || empty($targetClubId) || $assignedClubId !== $targetClubId) {
        http_response_code(403);
        if (class_exists('Database')) {
            try {
                $db = Database::getConnection();
                log_audit($db, get_current_user_id() ?? '0', get_current_user_name(), 'IDOR_VIOLATION_BLOCKED', 'security', $targetClubId ?? '0', "Unauthorized access attempt to club resource ID '$targetClubId'");
            } catch (Exception $e) {}
        }
        die("403 Forbidden: Security Violation. You do not have authorization for this chapter's data.");
    }
}
