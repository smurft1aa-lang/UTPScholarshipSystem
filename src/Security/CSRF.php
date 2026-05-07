<?php

declare(strict_types=1);

namespace UTP\Security;

/**
 * CSRF Protection Service
 *
 * Generates and validates CSRF tokens with automatic rotation
 * after each successful POST to prevent replay attacks.
 *
 * NOTE: This class assumes sessions are already initialized by
 * SessionManager. It ensures session is active before accessing
 * $_SESSION but delegates session configuration to SessionManager.
 */
class CSRF
{
    /**
     * Ensure session is active (defensive check).
     * Does NOT configure session parameters — that's SessionManager's job.
     */
    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Generate or retrieve the current CSRF token.
     */
    public static function generateToken(): string
    {
        self::ensureSession();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate a submitted CSRF token and rotate on success.
     */
    public static function validateToken(?string $token): bool
    {
        self::ensureSession();
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }

        $valid = hash_equals($_SESSION['csrf_token'], $token);

        // Rotate token after each validated POST to prevent replay attacks
        if ($valid) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $valid;
    }

    /**
     * Generate a hidden HTML input field containing the CSRF token.
     */
    public static function field(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
}
