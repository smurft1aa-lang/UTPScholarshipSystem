<?php
namespace UTP\Core;

/**
 * Session Management Service
 * 
 * Handles PHP session lifecycle, timeout, cookie hardening,
 * and user logout with audit trail.
 */
class SessionManager
{
    private int $timeoutDuration;

    public function __construct(int $timeoutDuration = 1800)
    {
        $this->timeoutDuration = $timeoutDuration;
    }

    /**
     * Initialize a hardened PHP session with timeout enforcement.
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_lifetime', 0);
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
            ini_set('session.cookie_samesite', 'Strict');
            ini_set('session.use_strict_mode', 1);
            session_start();

            if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $this->timeoutDuration) {
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

    /**
     * Destroy the current session and clear cookies.
     */
    public function logout(): void
    {
        $this->start();
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
}
