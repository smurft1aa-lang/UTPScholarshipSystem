<?php
/**
 * Session Management Component
 */

function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_lifetime', 0);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        session_start();
        
        $timeout_duration = 1800; // 30 minutes
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
        
        if (function_exists('initTelemetry')) {
            initTelemetry();
        }
    }
}

function logoutUser() {
    initSession();
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/audit.php';
        logAudit($_SESSION['user_id'], 'User Logged Out');
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}
