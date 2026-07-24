<?php
/**
 * Authentication & Session Management for ClubHub UIT
 * Enforces strict role-based access control for Dean Sir and Club Leads.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    session_start();
}

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function get_csrf_token(): string {
    return $_SESSION['csrf_token'] ?? '';
}

function verify_csrf_token(?string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

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
    require_login('/admin/dean-login.php');
    if (get_current_user_role() !== 'super_admin') {
        header("Location: /admin/dean-login.php?error=" . urlencode("Access Denied: Dean Sir Super Admin privileges required."));
        exit;
    }
}

function require_club_admin(): void {
    require_login('/admin/club-login.php');
    $role = get_current_user_role();
    if ($role !== 'club_admin' && $role !== 'super_admin') {
        header("Location: /admin/club-login.php?error=" . urlencode("Access Denied: Club Leadership privileges required."));
        exit;
    }
}
