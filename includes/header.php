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
        <div class="navbar-links" id="navLinks">
            <div class="mobile-user-info">
                <strong><?= htmlspecialchars($currentUser['full_name']) ?></strong>
            </div>
            <a href="/student/dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
            <a href="/student/check-eligibility.php" class="<?= $currentPage === 'check-eligibility' ? 'active' : '' ?>">Check Eligibility</a>
            <a href="/student/results.php" class="<?= $currentPage === 'results' ? 'active' : '' ?>">My Results</a>
            <a href="/student/upload-documents.php" class="<?= $currentPage === 'upload-documents' ? 'active' : '' ?>">My Documents</a>
            <a href="/student/my-profile.php" class="<?= $currentPage === 'my-profile' ? 'active' : '' ?>">My Profile</a>
            <a href="/api/logout.php" class="mobile-signout">Sign Out</a>
        </div>
        <div class="navbar-right">
            <span class="navbar-user desktop-only"><strong><?= htmlspecialchars($currentUser['full_name']) ?></strong></span>
            <a href="/api/logout.php" class="btn btn-signout desktop-only">Sign Out</a>
            <button class="hamburger-btn" id="mobileMenuBtn" aria-label="Toggle Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </div>
</nav>

<script nonce="<?= $GLOBALS['csp_nonce'] ?>">
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');
    if (mobileBtn && navLinks) {
        mobileBtn.addEventListener('click', () => {
            navLinks.classList.toggle('nav-open');
        });
    }
</script>
<?php endif; ?>
<main>
