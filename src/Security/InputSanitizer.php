<?php
namespace UTP\Security;

/**
 * Input Sanitizer & Validation Service
 *
 * Provides input sanitization (trim only — NO HTML encoding at input time),
 * output escaping, security headers, and field validators.
 */
class InputSanitizer
{
    /**
     * Sanitize input: trim whitespace only.
     * DO NOT encode HTML entities here — that happens at output time using e().
     *
     * @param string|array $input
     * @return string|array
     */
    public static function sanitize(string|array $input): string|array
    {
        if (is_array($input)) {
            return array_map([self::class , 'sanitize'], $input);
        }
        return trim($input);
    }

    /**
     * Escape output for safe HTML rendering.
     * Use this in views/templates when echoing user data.
     */
    public static function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate an email address.
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate a password against strength rules.
     *
     * @return string[] Array of error messages (empty if valid)
     */
    public static function validatePassword(string $password): array
    {
        $errors = [];
        if (strlen($password) < 8)
            $errors[] = 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password))
            $errors[] = 'Password must contain an uppercase letter.';
        if (!preg_match('/[a-z]/', $password))
            $errors[] = 'Password must contain a lowercase letter.';
        if (!preg_match('/[0-9]/', $password))
            $errors[] = 'Password must contain a number.';
        if (!preg_match('/[^A-Za-z0-9]/', $password))
            $errors[] = 'Password must contain a special character.';
        return $errors;
    }

    /**
     * Validate a Malaysian IC number (12 digits).
     */
    public static function validateICNumber(string $ic): bool
    {
        $clean = preg_replace('/[-\s]/', '', $ic);
        return (bool)preg_match('/^\d{12}$/', $clean);
    }

    /**
     * Validate a Malaysian phone number.
     */
    public static function validatePhone(string $phone): bool
    {
        $clean = preg_replace('/[-\s]/', '', $phone);
        return (bool)(preg_match('/^(\+?6?01)[0-9]{8,9}$/', $clean) || preg_match('/^\d{10,11}$/', $clean));
    }

    /**
     * Set all standard security response headers (CSP, X-Frame-Options, etc.).
     */
    public static function setSecurityHeaders(): void
    {
        if (!isset($GLOBALS['csp_nonce'])) {
            $GLOBALS['csp_nonce'] = bin2hex(random_bytes(16));
        }
        $nonce = $GLOBALS['csp_nonce'];

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com; img-src 'self' data: https://api.qrserver.com; font-src 'self' https://fonts.gstatic.com; form-action 'self'; frame-ancestors 'none'");
        header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
    }

    /**
     * Get the client's real IP address, accounting for trusted proxies.
     */
    public static function getClientIP(): string
    {
        $trustedProxy = getenv('TRUSTED_PROXY');
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if ($trustedProxy && $remoteAddr === $trustedProxy && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        return $remoteAddr;
    }
}
