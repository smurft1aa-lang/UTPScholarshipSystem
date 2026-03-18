<?php
/**
 * Role & Authorization Guard Component
 * - Checks user roles from session with periodic DB re-verification
 * - Guards pages based on required roles
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

/**
 * Re-verify role from database every 60 seconds to catch admin demotions.
 * If the DB role no longer matches the session, force logout.
 */
function reVerifyRole() {
    if (!isset($_SESSION['user_id'])) return;

    $now = time();
    $lastCheck = $_SESSION['role_verified_at'] ?? 0;

    // Re-check from DB every 60 seconds
    if ($now - $lastCheck < 60) return;

    try {
        $conn = getDB();
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $dbRole = $stmt->fetchColumn();

        if ($dbRole === false) {
            // User deleted from DB — force logout
            session_destroy();
            header('Location: /auth/login.php');
            exit;
        }

        if ($dbRole !== $_SESSION['role']) {
            // Role changed — update session
            $_SESSION['role'] = $dbRole;
        }

        $_SESSION['role_verified_at'] = $now;
    } catch (Exception $e) {
        // On DB error, don't block — just skip re-verification this cycle
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /auth/login.php');
        exit;
    }
    reVerifyRole();
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
    
    static $verified = null;
    if ($verified !== null) return $verified;
    
    try {
        $conn = getDB();
        $stmt = $conn->prepare("SELECT email_verified FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $verified = (int)$stmt->fetchColumn() === 1;
        return $verified;
    } catch (Exception $e) {
        return false;
    }
}

function requireVerified() {
    requireStudent();
    if (!isVerified()) {
        $_SESSION['error'] = 'Please verify your email to access this page.';
        header('Location: /student/dashboard.php');
        exit;
    }
}
