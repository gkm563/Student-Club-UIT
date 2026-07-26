<?php
/**
 * Authentication & Security Management Engine for ClubHub UIT
 * Enforces strict role-based access control, CSRF tokens, CAPTCHA verification, and Security Headers.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
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

function require_login(string $redirectUrl = '/admin/login.php'): void {
    if (!is_logged_in()) {
        header("Location: $redirectUrl");
        exit;
    }
}

function require_super_admin(): void {
    require_login('/admin/login.php');
    if (get_current_user_role() !== 'super_admin') {
        header("Location: /admin/login.php?error=" . urlencode("Access Denied: Dean Sir Super Admin privileges required."));
        exit;
    }
}

function require_club_admin(): void {
    require_login('/club-login.php');
    $role = get_current_user_role();
    if ($role !== 'club_admin' && $role !== 'super_admin') {
        header("Location: /club-login.php?error=" . urlencode("Access Denied: Club Leadership privileges required."));
        exit;
    }
}
