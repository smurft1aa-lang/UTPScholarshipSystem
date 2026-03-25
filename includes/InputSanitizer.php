<?php
/**
 * Input Sanitizer and Validation Component
 * 
 * IMPORTANT: sanitize() now trims input only — no HTML encoding at input time.
 * HTML escaping must be done at OUTPUT time using htmlspecialchars() in views.
 * This prevents double-encoding when data is stored then displayed.
 */

/**
 * Sanitize input: trim whitespace only.
 * DO NOT encode HTML entities here — that happens at output time.
 */
function sanitize($input)
{
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return trim($input);
}

/**
 * Escape output for safe HTML rendering.
 * Use this in views/templates when echoing user data.
 */
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePassword($password)
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

function validateICNumber($ic)
{
    $clean = preg_replace('/[-\s]/', '', $ic);
    return preg_match('/^\d{12}$/', $clean);
}

function validatePhone($phone)
{
    $clean = preg_replace('/[-\s]/', '', $phone);
    return preg_match('/^(\+?6?01)[0-9]{8,9}$/', $clean) || preg_match('/^\d{10,11}$/', $clean);
}

function setSecurityHeaders()
{
    if (!isset($GLOBALS['csp_nonce'])) {
        $GLOBALS['csp_nonce'] = bin2hex(random_bytes(16));
    }
    $nonce = $GLOBALS['csp_nonce'];

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-{$nonce}'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https://api.qrserver.com; font-src 'self' https://fonts.gstatic.com; form-action 'self'; frame-ancestors 'none'");
    header("Permissions-Policy: camera=(), microphone=(), geolocation=()");
}

function getClientIP()
{
    $trustedProxy = getenv('TRUSTED_PROXY');
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if ($trustedProxy && $remoteAddr === $trustedProxy && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    }
    return $remoteAddr;
}
