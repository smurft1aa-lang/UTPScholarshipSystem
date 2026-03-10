<?php
/**
 * Entry Point — redirects to landing or dashboard
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

setSecurityHeaders();
initSession();

if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: /admin/dashboard.php');
    } else {
        header('Location: /student/dashboard.php');
    }
    exit;
} else {
    header('Location: /landing.php');
    exit;
}
