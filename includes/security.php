<?php
/**
 * Security Functions
 * CSRF protection, rate limiting, input sanitization
 */

require_once __DIR__ . '/../config/database.php';

// ── CSRF Protection ──

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
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

// ── Rate Limiting ──

function checkRateLimit($ip, $maxAttempts = 5, $windowMinutes = 1) {
    $db = getDB();

    // Clean old attempts
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    $stmt->execute([$windowMinutes]);

    // Count recent attempts
    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
    $stmt->execute([$ip, $windowMinutes]);
    $count = $stmt->fetchColumn();

    return $count < $maxAttempts;
}

function recordLoginAttempt($ip) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
    $stmt->execute([$ip]);
}

function clearLoginAttempts($ip) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
}

// ── Input Sanitization ──

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password) {
    $errors = [];
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain an uppercase letter.';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Password must contain a lowercase letter.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain a number.';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'Password must contain a special character.';
    return $errors;
}

function validateICNumber($ic) {
    // Malaysian IC: 12 digits (YYMMDD-PB-XXXX) accepted with or without dashes
    $clean = preg_replace('/[-\s]/', '', $ic);
    return preg_match('/^\d{12}$/', $clean);
}

function validatePhone($phone) {
    $clean = preg_replace('/[-\s]/', '', $phone);
    return preg_match('/^(\+?6?01)[0-9]{8,9}$/', $clean) || preg_match('/^\d{10,11}$/', $clean);
}

// ── Security Headers ──

function setSecurityHeaders() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; form-action 'self'; frame-ancestors 'none'");
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
}

function getClientIP() {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}
