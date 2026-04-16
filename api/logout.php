<?php

declare(strict_types=1);

/**
 * API: Logout
 * Destroys the user session. Returns JSON for API clients or redirects for browser clients.
 */

require_once __DIR__ . '/../includes/init.php';

setSecurityHeaders();
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
