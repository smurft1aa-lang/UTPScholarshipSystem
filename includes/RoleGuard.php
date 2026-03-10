<?php
/**
 * Role & Authorization Guard Component
 */

function isLoggedIn() {
    initSession();
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    initSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStudent() {
    initSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /student/dashboard.php');
        exit;
    }
}

function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: /admin/dashboard.php');
        exit;
    }
}

function isVerified() {
    initSession();
    if (!isset($_SESSION['user_id'])) return false;
    
    // Check DB directly to avoid session desync loops
    $db = require __DIR__ . '/../config/database.php';
    if (!function_exists('getDB')) return false;
    
    $conn = getDB();
    $stmt = $conn->prepare("SELECT email_verified FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn() === 1;
}

function requireVerified() {
    requireStudent();
    if (!isVerified()) {
        $_SESSION['error'] = 'Please verify your email to access this page.';
        header('Location: /student/dashboard.php');
        exit;
    }
}
