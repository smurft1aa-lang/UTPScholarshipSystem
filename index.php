<?php
/**
 * Landing Page
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
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="UTP Scholarship & Course Eligibility System - Discover which foundation programmes and scholarships match your qualifications.">
    <title>UTP System - Course Eligibility & Scholarships</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-inner">
            <a href="/" class="navbar-brand">
                <span class="brand-icon">U</span>
                UTP System
            </a>
            <div class="navbar-right">
                <a href="/auth/login.php" class="btn btn-outline btn-sm">Log In</a>
                <a href="/auth/signup.php" class="btn btn-orange btn-sm">Sign Up</a>
            </div>
        </div>
    </nav>

    <section class="hero">
        <h1>Find Your Perfect Programme & Scholarship</h1>
        <p>Enter your SPM, O-Level, or IGCSE results and instantly discover which UTP foundation programmes you qualify for, along with matching scholarships and sponsorships.</p>
        <div class="hero-actions">
            <a href="/auth/signup.php" class="btn btn-orange btn-lg">Get Started</a>
            <a href="/auth/login.php" class="btn btn-outline btn-lg">Log In</a>
        </div>
    </section>

    <section class="features-grid">
        <div class="feature-card">
            <div class="feature-icon">
                <svg width="24" height="24" fill="none" stroke="#e8630a" stroke-width="2" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3>Smart Eligibility Check</h3>
            <p>AI-powered analysis of your results against official UTP entry requirements for all foundation programmes.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <svg width="24" height="24" fill="none" stroke="#e8630a" stroke-width="2" viewBox="0 0 24 24"><path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <h3>Course Matching</h3>
            <p>Get ranked recommendations for Engineering, Science, Technology, Computer Science, and Business Management.</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon">
                <svg width="24" height="24" fill="none" stroke="#e8630a" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <h3>Scholarship Finder</h3>
            <p>Discover available scholarships and sponsorships that match your academic profile and programme eligibility.</p>
        </div>
    </section>

    <script src="/assets/js/main.js"></script>
</body>
</html>
