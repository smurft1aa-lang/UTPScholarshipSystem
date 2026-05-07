<?php

declare(strict_types=1);

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
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain an uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain a lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain a number.';
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain a special character.';
        }
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
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline' 'nonce-{$nonce}' https://fonts.googleapis.com; img-src 'self' data: https://api.qrserver.com; font-src 'self' https://fonts.gstatic.com; form-action 'self'; frame-ancestors 'none'");
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

    /**
     * Allowed HTML tags for sanitizeHtml().
     */
    private const ALLOWED_TAGS = [
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'br', 'ul', 'ol', 'li',
        'strong', 'em', 'b', 'i', 'u', 'a', 'table', 'thead', 'tbody',
        'tr', 'th', 'td', 'blockquote', 'div', 'span',
    ];

    /**
     * Allowed HTML attributes per tag. Only safe, non-scriptable attributes.
     */
    private const ALLOWED_ATTRS = [
        'a'     => ['href', 'title', 'target', 'rel'],
        'th'    => ['colspan', 'rowspan'],
        'td'    => ['colspan', 'rowspan'],
        'div'   => ['class'],
        'span'  => ['class'],
        'table' => ['class'],
    ];

    /**
     * Sanitize HTML to allow only safe tags and attributes.
     *
     * Unlike strip_tags(), this method also strips dangerous attributes
     * (onclick, onerror, etc.) and blocks javascript: URIs in href/src
     * to prevent XSS via allowed tags like <a>.
     */
    public static function sanitizeHtml(string $html): string
    {
        // Step 1: strip_tags to remove disallowed elements
        $allowedTagStr = '<' . implode('><', self::ALLOWED_TAGS) . '>';
        $html = strip_tags($html, $allowedTagStr);

        // Step 2: strip dangerous attributes from remaining tags
        $html = preg_replace_callback(
            '/<(\w+)([^>]*)>/i',
            function (array $matches): string {
                $tag = strtolower($matches[1]);
                $attrString = $matches[2];

                // If tag is not in allowed list (shouldn't happen after strip_tags, but defensive)
                if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                    return '';
                }

                // Parse and filter attributes
                $safeAttrs = self::filterAttributes($tag, $attrString);
                $safeAttrStr = $safeAttrs !== '' ? ' ' . $safeAttrs : '';
                return "<{$tag}{$safeAttrStr}>";
            },
            $html
        ) ?? $html;

        return $html;
    }

    /**
     * Filter attributes for a given tag, keeping only whitelisted ones
     * and blocking dangerous values (javascript: URIs, data: URIs in href).
     */
    private static function filterAttributes(string $tag, string $attrString): string
    {
        $allowed = self::ALLOWED_ATTRS[$tag] ?? [];

        // If no attributes are allowed for this tag, strip all
        if (empty($allowed)) {
            return '';
        }

        $safe = [];
        // Match attribute="value", attribute='value', or attribute=value
        if (preg_match_all('/(\w+)\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|(\S+))/', $attrString, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $attrName = strtolower($match[1]);
                $attrValue = $match[2] ?: ($match[3] ?: ($match[4] ?? ''));

                // Only keep whitelisted attributes
                if (!in_array($attrName, $allowed, true)) {
                    continue;
                }

                // Block dangerous URI schemes in href/src attributes
                if (in_array($attrName, ['href', 'src'], true)) {
                    $cleanValue = strtolower(trim(preg_replace('/[\x00-\x1f\x7f]/', '', $attrValue)));
                    if (preg_match('/^(javascript|data|vbscript)\s*:/i', $cleanValue)) {
                        continue;
                    }
                }

                $safe[] = $attrName . '="' . htmlspecialchars($attrValue, ENT_QUOTES, 'UTF-8') . '"';
            }
        }

        return implode(' ', $safe);
    }
}
