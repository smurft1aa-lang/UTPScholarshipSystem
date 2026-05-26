<?php
require_once __DIR__ . '/includes/init.php';

setSecurityHeaders();
initSession();

// Redirect already logged-in users to their dashboard
if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? '/admin/dashboard.php' : '/student/dashboard.php'));
    exit;
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Universiti Teknologi PETRONAS (UTP) Scholarship Portal — Check eligibility for scholarships, foundation programmes, and financial aid.">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <title>UTP Scholarship Portal — Universiti Teknologi PETRONAS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    <link rel="stylesheet" href="/assets/css/landing.css?v=11">
</head>
<body>
<div class="page-wrapper">

    <!-- Top Utility Bar -->
    <div class="top-bar">
        <div class="top-bar-left">
            <a href="mailto:scholarship@utp.edu.my">
                <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                Ask UTP
            </a>
        </div>
        <div class="top-bar-right">
            <a href="https://www.instagram.com/utpofficial/" aria-label="Instagram" target="_blank"><svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
            <a href="https://www.youtube.com/user/utpofficial" aria-label="YouTube" target="_blank"><svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 00-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 00.502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 002.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 002.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></a>
            <a href="https://www.facebook.com/UTPofficial" aria-label="Facebook" target="_blank"><svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
            <a href="https://www.x.com/utpofficial" aria-label="X" target="_blank"><svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg></a>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar" id="navbar">
        <a href="#" class="nav-logo">
            <!-- Replace with actual UTP logo: /assets/images/utp_logo.png -->
            <img src="/assets/images/utp_logo.png" alt="UTP Logo" onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
            <span class="nav-logo-text" style="display:none">UTP</span>
        </a>
        <ul class="nav-links" id="navLinks">
            <li><a href="#hero">Home</a></li>
            <li><a href="/scholarships.php">Scholarships</a></li>
            <li><a href="#programmes">Programmes</a></li>
            <li><a href="#stories">Success Stories</a></li>
            <li><a href="#why-utp">Why UTP</a></li>
        </ul>
        <div class="nav-actions">
            <a href="/auth/login.php" class="btn btn-outline-navy">Login</a>
            <a href="/auth/signup.php" class="btn btn-gold">Sign Up</a>
            <button class="hamburger" id="mobileNavToggle" aria-label="Toggle Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    <!-- Hero Banner -->
    <section class="hero" id="hero">
        <div class="hero-bg">
            <img src="/assets/images/hero_campus.jpg" alt="UTP Campus">
        </div>
        <div class="hero-overlay"></div>
        <div class="section-inner">
            <div class="hero-text animate-on-scroll">
                <h1>Transforming Minds,<br><span class="highlight">Advancing Industries</span></h1>
                <p>Transformative learning and mission-driven research in ASEAN's most dynamic ecosystem. Your scholarship journey starts here.</p>
            </div>
            <div class="hero-buttons">
                <a href="/auth/signup.php" class="btn btn-gold" style="padding:14px 36px">Check your eligibility</a>
                <a href="/scholarships.php" class="btn btn-white-outline" style="padding:14px 36px">Explore Scholarships</a>
            </div>
        </div>
    </section>

    <!-- Stats Bar -->
    <section class="stats-bar">
        <div class="stats-grid">
            <div class="stat-item animate-on-scroll">
                <div class="stat-number">7500+</div>
                <div class="stat-label">Students Enrolled</div>
            </div>
            <div class="stat-item animate-on-scroll">
                <div class="stat-number">60+</div>
                <div class="stat-label">Countries Represented</div>
            </div>
            <div class="stat-item animate-on-scroll">
                <div class="stat-number">93%</div>
                <div class="stat-label">Graduate Employability</div>
            </div>
            <div class="stat-item animate-on-scroll">
                <div class="stat-number">Top 18%</div>
                <div class="stat-label">QS World Ranking</div>
            </div>
        </div>
    </section>

    <!-- Scholarship Highlights -->
    <section class="scholarships" id="scholarships">
        <div class="section-inner">
            <h2 class="animate-on-scroll">Financial Aid & Scholarships</h2>
            <p class="section-subtitle animate-on-scroll">UTP offers a range of scholarships to support deserving students. Use our portal to check your eligibility and track your application status.</p>
            <div class="scholarship-grid">
                <div class="scholarship-card animate-on-scroll">
                    <div class="scholarship-icon"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87L18.18 21 12 17.27 5.82 21 7 14.14l-5-4.87 6.91-1.01L12 2z"/></svg></div>
                    <h3>Merit-Based Scholarship</h3>
                    <p>Awarded to students with outstanding academic achievements and leadership qualities. Covers tuition fees and provides a monthly stipend.</p>
                    <a href="/auth/signup.php" class="card-link">Check Eligibility →</a>
                </div>
                <div class="scholarship-card animate-on-scroll">
                    <div class="scholarship-icon"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 00-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 00-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 000-7.78z"/></svg></div>
                    <h3>Need-Based Financial Aid</h3>
                    <p>Supporting students from underprivileged backgrounds with financial assistance covering tuition, accommodation, and living expenses.</p>
                </div>
                <div class="scholarship-card animate-on-scroll">
                    <div class="scholarship-icon"><svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
                    <h3>PETRONAS Sponsorship</h3>
                    <p>Full sponsorship by PETRONAS covering tuition, accommodation, books, laptop, and monthly allowance for top-performing students.</p>
                </div>
            </div>
            <p class="section-disclaimer animate-on-scroll">* This portal helps you check eligibility and submit applications. Scholarships are managed and awarded by Universiti Teknologi PETRONAS (UTP) for internal funds, while external sponsorships are administered by separate corporate partners and government agencies.</p>
        </div>
    </section>

    <!-- Programmes -->
    <section class="programmes" id="programmes">
        <div class="section-inner">
            <h2 class="animate-on-scroll">Explore Our Programmes</h2>
            <p class="section-subtitle animate-on-scroll">World-class programmes designed to produce industry-ready graduates. Click each category to view all available programmes.</p>
            <div class="prog-accordion">

                <!-- Foundation -->
                <div class="prog-card animate-on-scroll">
                    <div class="prog-card-header">
                        <div class="prog-card-left">
                            <div class="prog-icon prog-icon-foundation">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg>
                            </div>
                            <div>
                                <h4>Foundation</h4>
                                <span class="prog-count">2 streams · 1 year (3 trimesters)</span>
                            </div>
                        </div>
                        <span class="prog-chevron"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></span>
                    </div>
                    <div class="prog-card-body">
                        <ul class="prog-list">
                            <li>
                                <strong>Foundation in Engineering & Science</strong>
                                <span>Pathway to engineering, applied chemistry, industrial physics, and petroleum geoscience degrees</span>
                            </li>
                            <li>
                                <strong>Foundation in Business Management & Computing</strong>
                                <span>Pathway to business management, computer science, information systems, and IT degrees</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Engineering -->
                <div class="prog-card animate-on-scroll">
                    <div class="prog-card-header">
                        <div class="prog-card-left">
                            <div class="prog-icon prog-icon-engineering">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M22 9V7h-2V5a2 2 0 00-2-2H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-2h2v-2h-2v-2h2v-2h-2V9h2zm-4 10H4V5h14v14zM6 13h5v4H6v-4zm6-6h4v3h-4V7zM6 7h5v5H6V7zm6 4h4v6h-4v-6z"/></svg>
                            </div>
                            <div>
                                <h4>Engineering</h4>
                                <span class="prog-count">8 programmes · 4 years</span>
                            </div>
                        </div>
                        <span class="prog-chevron"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></span>
                    </div>
                    <div class="prog-card-body">
                        <ul class="prog-list">
                            <li><strong>Bachelor of Chemical Engineering with Honours</strong></li>
                            <li><strong>Bachelor of Civil Engineering with Honours</strong></li>
                            <li><strong>Bachelor of Computer Engineering with Honours</strong></li>
                            <li><strong>Bachelor of Electrical and Electronics Engineering with Honours</strong></li>
                            <li><strong>Bachelor of Integrated Engineering with Honours <span class="badge-new">New!</span></strong></li>
                            <li><strong>Bachelor of Materials Engineering with Honours</strong></li>
                            <li><strong>Bachelor of Mechanical Engineering with Honours</strong></li>
                            <li><strong>Bachelor of Petroleum Engineering with Honours</strong></li>
                        </ul>
                    </div>
                </div>

                <!-- Computing -->
                <div class="prog-card animate-on-scroll">
                    <div class="prog-card-header">
                        <div class="prog-card-left">
                            <div class="prog-icon prog-icon-computing">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M9.4 16.6L4.8 12l4.6-4.6L8 6l-6 6 6 6 1.4-1.4zm5.2 0l4.6-4.6-4.6-4.6L16 6l6 6-6 6-1.4-1.4z"/></svg>
                            </div>
                            <div>
                                <h4>Computing</h4>
                                <span class="prog-count">3 programmes · 3.5 or 4 years depending on programme</span>
                            </div>
                        </div>
                        <span class="prog-chevron"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></span>
                    </div>
                    <div class="prog-card-body">
                        <ul class="prog-list">
                            <li><strong>Bachelor of Computer Science (Hons)</strong></li>
                            <li><strong>Bachelor of Information Systems (Hons)</strong></li>
                            <li><strong>Bachelor of Information Technology (Hons)</strong></li>
                        </ul>
                    </div>
                </div>

                <!-- Science -->
                <div class="prog-card animate-on-scroll">
                    <div class="prog-card-header">
                        <div class="prog-card-left">
                            <div class="prog-icon prog-icon-science">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M7 2v11h3v9l7-12h-4l4-8z"/></svg>
                            </div>
                            <div>
                                <h4>Science</h4>
                                <span class="prog-count">3 programmes · 3.5 years</span>
                            </div>
                        </div>
                        <span class="prog-chevron"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></span>
                    </div>
                    <div class="prog-card-body">
                        <ul class="prog-list">
                            <li><strong>Bachelor of Science (Hons) in Applied Chemistry</strong></li>
                            <li><strong>Bachelor of Science (Hons) in Petroleum Geoscience</strong></li>
                            <li><strong>Bachelor of Science in Industrial Physics</strong></li>
                        </ul>
                    </div>
                </div>

                <!-- Business Management -->
                <div class="prog-card animate-on-scroll">
                    <div class="prog-card-header">
                        <div class="prog-card-left">
                            <div class="prog-icon prog-icon-business">
                                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 7V3H2v18h20V7H12zM6 19H4v-2h2v2zm0-4H4v-2h2v2zm0-4H4V9h2v2zm0-4H4V5h2v2zm4 12H8v-2h2v2zm0-4H8v-2h2v2zm0-4H8V9h2v2zm0-4H8V5h2v2zm10 12h-8v-2h2v-2h-2v-2h2v-2h-2V9h8v10zm-2-8h-2v2h2v-2zm0 4h-2v2h2v-2z"/></svg>
                            </div>
                            <div>
                                <h4>Business Management</h4>
                                <span class="prog-count">1 programme · 3.5 years</span>
                            </div>
                        </div>
                        <span class="prog-chevron"><svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></span>
                    </div>
                    <div class="prog-card-body">
                        <ul class="prog-list">
                            <li><strong>Bachelor of Business Management (Hons)</strong></li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- Success Stories -->
    <section class="success-stories" id="stories">
        <div class="section-inner">
            <h2 class="animate-on-scroll">Success Stories</h2>
            <p class="section-subtitle animate-on-scroll">Hear from our scholarship recipients who have gone on to achieve great things.</p>
            <div class="stories-grid">
                <div class="story-card animate-on-scroll">
                    <div class="story-header">
                        <div class="story-avatar">AF</div>
                        <div class="story-info"><h4>Ahmad Farhan</h4><p>Foundation in Engineering</p></div>
                    </div>
                    <p class="story-text">My foundation year at UTP gave me the perfect headstart. The lecturers are incredibly supportive and the facilities are world-class.</p>
                </div>
                <div class="story-card animate-on-scroll">
                    <div class="story-header">
                        <div class="story-avatar">NR</div>
                        <div class="story-info"><h4>Nur Rashidah</h4><p>Mechanical Engineering</p></div>
                    </div>
                    <p class="story-text">The PETRONAS scholarship changed my life. I learned practical skills that prepared me for a global engineering career at a Fortune 500 company.</p>
                </div>
                <div class="story-card animate-on-scroll">
                    <div class="story-header">
                        <div class="story-avatar">DH</div>
                        <div class="story-info"><h4>Daniel Heng</h4><p>Computer Science</p></div>
                    </div>
                    <p class="story-text">UTP's CS programme is top-notch. The industry connections and internship placements really set this university apart from others.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose UTP -->
    <section class="why-utp" id="why-utp">
        <div class="section-inner">
            <div class="why-visual animate-on-scroll">
                <img src="/assets/images/small_hero.jpg" alt="UTP Campus Facilities">
            </div>
            <div class="why-content animate-on-scroll">
                <h2>Why Choose UTP?</h2>
                <ul class="why-list">
                    <li><span class="why-list-icon"><svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg></span>World-class facilities backed by PETRONAS — one of the largest corporations in Southeast Asia</li>
                    <li><span class="why-list-icon"><svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg></span>Strong industry connections with guaranteed internship placements at leading companies</li>
                    <li><span class="why-list-icon"><svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg></span>Global exposure through exchange programmes with 60+ partner universities worldwide</li>
                    <li><span class="why-list-icon"><svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41L9 16.17z"/></svg></span>93% graduate employability rate within 6 months of graduation</li>
                </ul>
                <a href="https://utpdec.microsoftcrmportals.com/admission/" class="btn btn-navy" style="padding:12px 32px">Start Your Application</a>
            </div>
        </div>
    </section>

    <!-- CTA Banner -->
    <section class="cta-banner">
        <h2 class="animate-on-scroll">Ready to Begin Your Journey?</h2>
        <p class="animate-on-scroll">Join thousands of students who have transformed their futures through UTP scholarships.</p>
        <a href="/auth/signup.php" class="btn btn-gold animate-on-scroll" style="padding:14px 40px;font-size:1rem">Check eligibility for Scholarship</a>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <div class="footer-brand">
                    <img src="/assets/images/utp_logo.png" alt="UTP" onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
                    <span class="footer-brand-text" style="display:none">UTP</span>
                </div>
                <p class="footer-desc">Empowering the next generation of engineers, technologists, and leaders through world-class education and research.</p>
                <p class="footer-address">Universiti Teknologi PETRONAS<br>32610 Seri Iskandar, Perak, Malaysia<br>Tel: 1-300-22-8887</p>
                <div class="social-icons">
                    <a href="https://www.facebook.com/UTPofficial" aria-label="Facebook" target="_blank"><svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></a>
                    <a href="https://www.instagram.com/utpofficial/" aria-label="Instagram" target="_blank"><svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg></a>
                    <a href="https://www.linkedin.com/school/universiti-teknologi-petronas/" aria-label="LinkedIn" target="_blank"><svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Admission</h4>
                <ul>
                    <li><a href="https://www.utp.edu.my/Pages/Admission/Home/Foundation.aspx" target="_blank">Foundation</a></li>
                    <li><a href="https://www.utp.edu.my/Pages/Admission/Home/Undergraduate.aspx" target="_blank">Undergraduate</a></li>
                    <li><a href="https://www.utp.edu.my/Pages/Admission/Home/Postgraduate.aspx" target="_blank">Postgraduate</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Academic</h4>
                <ul>
                    <li><a href="https://www.utp.edu.my" target="_blank">Engineering</a></li>
                    <li><a href="https://www.utp.edu.my" target="_blank">Computer Science</a></li>
                    <li><a href="https://www.utp.edu.my" target="_blank">Science</a></li>
                    <li><a href="https://www.utp.edu.my" target="_blank">Management</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Resources</h4>
                <ul>
                    <li><a href="/auth/login.php">Student Portal</a></li>
                    <li><a href="/scholarships.php">Scholarships</a></li>
                    <li><a href="#why-utp">Campus Life</a></li>
                    <li><a href="#stories">FAQs</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-copy">&copy; <?= date('Y') ?> Universiti Teknologi PETRONAS. All Rights Reserved.</div>
            <div class="footer-legal">
                <a href="#">Privacy Notice</a>
                <a href="#">Disclaimer</a>
            </div>
        </div>
    </footer>
</div>

<script nonce="<?= $GLOBALS['csp_nonce'] ?>">
    /* Mobile Nav */
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const navLinksList = document.getElementById('navLinks');
    if (mobileNavToggle && navLinksList) {
        mobileNavToggle.addEventListener('click', () => {
            navLinksList.classList.toggle('open');
        });
    }
    /* Close nav on link click */
    document.querySelectorAll('.nav-links a').forEach(a => {
        a.addEventListener('click', () => {
            document.getElementById('navLinks').classList.remove('open');
        });
    });
    /* Scroll-triggered animations */
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.15 });
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
    /* Programme accordion toggle */
    document.querySelectorAll('.prog-card-header').forEach(header => {
        header.addEventListener('click', () => {
            header.closest('.prog-card').classList.toggle('open');
        });
    });
</script>

<?php require_once __DIR__ . '/includes/chatbot.php'; ?>
</body>
</html>