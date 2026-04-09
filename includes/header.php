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
    <title><?= htmlspecialchars($pageTitle ?? 'UTP Scholarship & Eligibility System') ?></title>
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <script nonce="<?= $GLOBALS['csp_nonce'] ?>">/* Restore theme before paint to prevent flash */
    (function(){var t=localStorage.getItem('theme');if(t)document.documentElement.setAttribute('data-theme',t);})();
    </script>
</head>
<body>
<a href="#main-content" class="skip-link">Skip to main content</a>
<?php if ($currentUser): ?>
<nav class="navbar" role="navigation" aria-label="Main navigation">
    <div class="navbar-inner">
        <a href="/student/dashboard.php" class="navbar-brand">
            <img src="https://www.utp.edu.my/SiteAssets/UTP-logo2.png" alt="UTP" class="brand-logo" style="height:32px;width:auto;">
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
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode" title="Toggle dark mode">
                <span class="icon-moon">🌙</span>
                <span class="icon-sun">☀️</span>
            </button>
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
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const html = document.documentElement;
            const current = html.getAttribute('data-theme');
            const next = current === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        });
    }
</script>
<?php endif; ?>
<main id="main-content" role="main">
