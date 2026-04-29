<?php
/**
 * Admin Sidebar Layout
 */
require_once __DIR__ . '/../includes/init.php';

setSecurityHeaders();
requireAdmin();

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$currentUser = getCurrentUser();
$initials = strtoupper(substr($currentUser['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<script>
    (function () { var t = localStorage.getItem('utp-theme'); if (t) document.documentElement.setAttribute('data-theme', t); })();
</script>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin — UTP Scholarship System') ?></title>
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    <link rel="stylesheet" href="/assets/css/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
</head>

<body>
    <a href="#admin-main-content" class="skip-link">Skip to main content</a>
    <div class="admin-layout">

        <!-- Admin Mobile Header -->
        <div class="admin-mobile-header">
            <div class="sidebar-brand" style="border:none; padding:0; height:auto;">
                UTP Admin
            </div>
            <button class="hamburger-btn admin-hamburger" id="adminMenuBtn" aria-label="Toggle Menu">
                <span></span><span></span><span></span>
            </button>
        </div>

        <!-- Sidebar Overlay -->
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <aside class="sidebar" id="adminSidebar" role="navigation" aria-label="Admin sidebar">
            <a href="/admin/dashboard.php" class="sidebar-brand">
                UTP
            </a>
            <nav class="sidebar-nav">
                <a href="/admin/dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="3" width="7" height="7" rx="1" />
                        <rect x="14" y="3" width="7" height="7" rx="1" />
                        <rect x="3" y="14" width="7" height="7" rx="1" />
                        <rect x="14" y="14" width="7" height="7" rx="1" />
                    </svg>
                    Overview
                </a>
                <a href="/admin/applications.php" class="<?= $currentPage === 'applications' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Applications
                </a>
                <a href="/admin/programmes.php" class="<?= $currentPage === 'programmes' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                    Programmes
                </a>
                <a href="/admin/scholarships.php" class="<?= $currentPage === 'scholarships' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Scholarships
                </a>
                <a href="/admin/reports.php" class="<?= $currentPage === 'reports' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Reports
                </a>
                <a href="/admin/generate-proposal.php" class="<?= $currentPage === 'generate-proposal' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
                    </svg>
                    AI Broadcast
                </a>
                <a href="/admin/marketing-templates.php" class="<?= $currentPage === 'marketing-templates' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z" />
                        <path d="M22 6l-10 7L2 6" />
                    </svg>
                    AI Marketing
                </a>
                <a href="/admin/audit-log.php" class="<?= $currentPage === 'audit-log' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                    Audit Log
                </a>

                <a href="/admin/bulk-upload.php" class="<?= $currentPage === 'bulk-upload' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4m17-8l-9-7-9 7m0 0v10a2 2 0 002 2h14a2 2 0 002-2V7z" />
                    </svg>
                    Bulk Upload
                </a>

                <a href="/admin/data-export.php" class="<?= $currentPage === 'data-export' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2v20m0 0l-7-7m7 7l7-7M3 12h18" />
                    </svg>
                    Data Export
                </a>

                <a href="/admin/settings.php" class="<?= $currentPage === 'settings' ? 'active' : '' ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    Settings
                </a>
            </nav>
            <div class="sidebar-bottom">
                <div style="display:flex;gap:8px;margin-bottom:10px;">
                    <a href="/api/logout.php" class="btn btn-outline btn-sm" style="flex:1;">Logout</a>
                    <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode"
                        title="Toggle Light/Dark Mode">
                        <span class="icon-sun">☀️</span>
                        <span class="icon-moon">🌙</span>
                    </button>
                </div>
                <div class="sidebar-user">
                    <div class="user-avatar"><?= $initials ?></div>
                    <div class="user-info">
                        <strong><?= htmlspecialchars($currentUser['full_name']) ?></strong>
                        <span>Administrator</span>
                    </div>
                </div>
            </div>
        </aside>
        <div class="admin-main" id="admin-main-content" role="main">

            <script nonce="<?= $GLOBALS['csp_nonce'] ?>">
                // Admin Sidebar Toggle Logic
                const adminMenuBtn = document.getElementById('adminMenuBtn');
                const adminSidebar = document.getElementById('adminSidebar');
                const sidebarOverlay = document.getElementById('sidebarOverlay');

                if (adminMenuBtn && adminSidebar && sidebarOverlay) {
                    function toggleSidebar() {
                        adminSidebar.classList.toggle('open');
                        sidebarOverlay.classList.toggle('active');
                        document.body.style.overflow = adminSidebar.classList.contains('open') ? 'hidden' : '';
                    }

                    adminMenuBtn.addEventListener('click', toggleSidebar);
                    sidebarOverlay.addEventListener('click', toggleSidebar);
                }

                // Theme Toggle Logic
                (function () {
                    const toggle = document.getElementById('themeToggle');
                    const html = document.documentElement;
                    const saved = localStorage.getItem('utp-theme');

                    // Apply saved theme immediately
                    if (saved) {
                        html.setAttribute('data-theme', saved);
                    }

                    if (toggle) {
                        toggle.addEventListener('click', function () {
                            const current = html.getAttribute('data-theme');
                            const next = current === 'dark' ? 'light' : 'dark';
                            html.setAttribute('data-theme', next);
                            localStorage.setItem('utp-theme', next);
                        });
                    }
                })();
            </script>