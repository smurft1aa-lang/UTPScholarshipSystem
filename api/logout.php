<?php
declare(strict_types=1);
/**
 * API: Logout
 * Destroys the user session and redirects to login page.
 */
require_once __DIR__ . '/../includes/init.php';

setSecurityHeaders();
logoutUser();
header('Location: /auth/login.php');
exit;
