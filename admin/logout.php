<?php
require_once __DIR__ . '/../includes/auth.php';

// Detect user role BEFORE clearing session data
$role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? '';

$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

if ($role === 'super_admin') {
    // Dean Sir -> Dean Login Portal
    header("Location: dean-login.php");
    exit;
} else {
    // Club Lead -> Dedicated Root Club Login Portal
    header("Location: ../club-login.php");
    exit;
}
