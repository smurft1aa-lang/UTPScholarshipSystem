<?php
/**
 * Header partial for student-facing pages
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/security.php';

setSecurityHeaders();
initSession();

$currentUser = getCurrentUser();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UTP Scholarship & Course Eligibility System - Check your eligibility for foundation programmes and scholarships.">
    <title>UTP System</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php if ($currentUser): ?>
<nav class="navbar">
    <div class="navbar-inner">
        <a href="/student/dashboard.php" class="navbar-brand">
            <span class="brand-icon">U</span>
            UTP System
        </a>
        <div class="navbar-links">
            <a href="/student/dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="/student/check-eligibility.php" class="<?= $currentPage === 'check-eligibility' ? 'active' : '' ?>">Check Eligibility</a>
            <a href="/student/results.php" class="<?= $currentPage === 'results' ? 'active' : '' ?>">My Results</a>
        </div>
        <div class="navbar-right">
            <span class="navbar-user"><strong><?= htmlspecialchars($currentUser['full_name']) ?></strong></span>
            <a href="/api/logout.php" class="btn btn-signout">Sign Out</a>
        </div>
    </div>
</nav>
<?php endif; ?>
<main>
