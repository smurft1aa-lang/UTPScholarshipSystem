<?php
/**
 * Backward-Compatible Bridge
 *
 * This file bridges the old procedural function calls to the new
 * namespaced OOP classes. Existing pages that `require_once 'init.php'`
 * or `require_once 'auth.php'` will continue to work without modification.
 *
 * Over time, pages should be migrated to use the namespaced classes directly
 * via Composer's PSR-4 autoloader, after which this bridge can be removed.
 */

// Load Composer autoloader (enables PSR-4 namespace resolution)
require_once __DIR__ . '/../vendor/autoload.php';

// Load database config (provides getDB())
require_once __DIR__ . '/../config/database.php';

// ─── Singleton Instances ────────────────────────────────────────────
$_app_session = new \UTP\Core\SessionManager();

// ─── SessionManager Bridge ──────────────────────────────────────────
if (!function_exists('initSession')) {
    function initSession() {
        global $_app_session;
        $_app_session->start();
    }
}

if (!function_exists('logoutUser')) {
    function logoutUser() {
        if (isset($_SESSION['user_id'])) {
            logAudit($_SESSION['user_id'], 'User Logged Out');
        }
        global $_app_session;
        $_app_session->logout();
    }
}

// ─── CSRF Bridge ────────────────────────────────────────────────────
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() { return \UTP\Security\CSRF::generateToken(); }
}
if (!function_exists('validateCSRFToken')) {
    function validateCSRFToken($token) { return \UTP\Security\CSRF::validateToken($token); }
}
if (!function_exists('csrfField')) {
    function csrfField() { return \UTP\Security\CSRF::field(); }
}

// ─── RoleGuard Bridge ───────────────────────────────────────────────
$_app_guard = new \UTP\Security\RoleGuard(getDB(), $_app_session);

if (!function_exists('isLoggedIn')) {
    function isLoggedIn() { global $_app_guard; return $_app_guard->isLoggedIn(); }
}
if (!function_exists('isAdmin')) {
    function isAdmin() { global $_app_guard; return $_app_guard->isAdmin(); }
}
if (!function_exists('isStudent')) {
    function isStudent() { global $_app_guard; return $_app_guard->isStudent(); }
}
if (!function_exists('reVerifyRole')) {
    function reVerifyRole() { global $_app_guard; $_app_guard->reVerifyRole(); }
}
if (!function_exists('requireLogin')) {
    function requireLogin() { global $_app_guard; $_app_guard->requireLogin(); }
}
if (!function_exists('requireAdmin')) {
    function requireAdmin() { global $_app_guard; $_app_guard->requireAdmin(); }
}
if (!function_exists('requireStudent')) {
    function requireStudent() { global $_app_guard; $_app_guard->requireStudent(); }
}
if (!function_exists('isVerified')) {
    function isVerified() { global $_app_guard; return $_app_guard->isVerified(); }
}
if (!function_exists('requireVerified')) {
    function requireVerified() { global $_app_guard; $_app_guard->requireVerified(); }
}

// ─── RateLimiter Bridge ─────────────────────────────────────────────
if (!function_exists('checkRateLimit')) {
    function checkRateLimit($ip, $maxAttempts = 5, $windowMinutes = 1) {
        $limiter = new \UTP\Security\RateLimiter(getDB());
        return $limiter->check($ip, $maxAttempts, $windowMinutes);
    }
}
if (!function_exists('recordLoginAttempt')) {
    function recordLoginAttempt($ip) {
        $limiter = new \UTP\Security\RateLimiter(getDB());
        $limiter->record($ip);
    }
}
if (!function_exists('clearLoginAttempts')) {
    function clearLoginAttempts($ip) {
        $limiter = new \UTP\Security\RateLimiter(getDB());
        $limiter->clear($ip);
    }
}

// ─── InputSanitizer Bridge ──────────────────────────────────────────
if (!function_exists('sanitize')) {
    function sanitize($input) { return \UTP\Security\InputSanitizer::sanitize($input); }
}
if (!function_exists('e')) {
    function e($value) { return \UTP\Security\InputSanitizer::escape($value); }
}
if (!function_exists('validateEmail')) {
    function validateEmail($email) { return \UTP\Security\InputSanitizer::validateEmail($email); }
}
if (!function_exists('validatePassword')) {
    function validatePassword($password) { return \UTP\Security\InputSanitizer::validatePassword($password); }
}
if (!function_exists('validateICNumber')) {
    function validateICNumber($ic) { return \UTP\Security\InputSanitizer::validateICNumber($ic); }
}
if (!function_exists('validatePhone')) {
    function validatePhone($phone) { return \UTP\Security\InputSanitizer::validatePhone($phone); }
}
if (!function_exists('setSecurityHeaders')) {
    function setSecurityHeaders() { \UTP\Security\InputSanitizer::setSecurityHeaders(); }
}
if (!function_exists('getClientIP')) {
    function getClientIP() { return \UTP\Security\InputSanitizer::getClientIP(); }
}

// ─── AuditLogger Bridge ────────────────────────────────────────────
if (!function_exists('logAudit')) {
    function logAudit($userId, $action, $targetType = null, $targetId = null, $details = null) {
        $logger = new \UTP\Services\AuditLogger(getDB());
        return $logger->log($userId, $action, $targetType, $targetId, $details);
    }
}

// ─── Telemetry Bridge ───────────────────────────────────────────────
if (!function_exists('initTelemetry')) {
    function initTelemetry() { \UTP\Services\Telemetry::init(); }
}
if (!function_exists('trackEvent')) {
    function trackEvent($eventName, $context = [], $level = 'INFO') {
        \UTP\Services\Telemetry::trackEvent($eventName, $context, $level);
    }
}
if (!function_exists('startTimer')) {
    function startTimer($label) { \UTP\Services\Telemetry::startTimer($label); }
}
if (!function_exists('endTimer')) {
    function endTimer($label) { return \UTP\Services\Telemetry::endTimer($label); }
}

// ─── UserAuth / Mailer (keep procedural for now) ────────────────────
require_once __DIR__ . '/UserAuth.php';
require_once __DIR__ . '/mailer.php';
