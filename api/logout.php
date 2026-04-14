<?php
declare(strict_types=1);
/**
 * API: Logout
 * Destroys the user session and redirects to login page.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

setSecurityHeaders();
logoutUser();
header('Location: /auth/login.php');
exit;
