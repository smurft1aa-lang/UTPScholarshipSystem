<?php

declare(strict_types=1);

/**
 * API: Logout
 * Destroys the user session. Requires POST with a valid CSRF token
 * to prevent logout-CSRF attacks (e.g. <img src="/api/logout.php">).
 */

require_once __DIR__ . '/../includes/init.php';

setSecurityHeaders();
initSession();

// Only allow POST to prevent CSRF-via-GET (e.g. <img src="/api/logout.php">)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // For backwards-compatible GET requests from logged-in users,
    // redirect to login instead of erroring out
    header('Location: /auth/login.php');
    exit;
}

// Validate CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($acceptHeader, 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token.']);
    } else {
        header('Location: /auth/login.php');
    }
    exit;
}

logoutUser();

// If client accepts JSON (API call), return JSON response
$acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
if (str_contains($acceptHeader, 'application/json')) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => true, 'redirect' => '/auth/login.php']);
    exit;
}

// Browser navigation — redirect to login
header('Location: /auth/login.php');
exit;
