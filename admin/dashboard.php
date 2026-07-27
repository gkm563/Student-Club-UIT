<?php
session_start();
require_once __DIR__ . '/../includes/auth.php';

// Redirect Club Lead to Club Dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'club_admin') {
    header('Location: ../club/dashboard.php');
    exit;
}

// Redirect Dean Sir (Super Admin) to Super Admin Portal Overview
if (isset($_SESSION['role']) && $_SESSION['role'] === 'super_admin') {
    header('Location: super/index.php');
    exit;
}

// If unauthenticated, send to Dean login
header('Location: dean-login.php');
exit;
