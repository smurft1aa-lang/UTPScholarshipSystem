<?php
/**
 * CSRF Protection Component
 * - Generates and validates CSRF tokens
 * - Rotates tokens after each validated POST to prevent replay attacks
 */

function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_lifetime', 0);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_lifetime', 0);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
    if (empty($_SESSION['csrf_token']) || empty($token)) return false;
    $valid = hash_equals($_SESSION['csrf_token'], $token);

    // Rotate token after each validated POST to prevent replay attacks
    if ($valid) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $valid;
}

function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
