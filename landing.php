<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

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
    <meta name="description" content="Universiti Teknologi PETRONAS (UTP) — Apply for foundation programmes, scholarships, and more.">
    <title>UTP Student Application Portal — Universiti Teknologi PETRONAS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style nonce="<?= $GLOBALS['csp_nonce'] ?>">
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Poppins', sans-serif;
            color: #1a1a2e;
            background: #ffffff;
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }
        h1, h2, h3, h4 { font-family: 'Nunito', sans-serif; font-weight: 900; line-height: 1.25; }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; display: block; }

        /* ── Navbar ── */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: #ffffff;
            padding: 0 48px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid #f0f0f0;
            box-shadow: 0 1px 8px rgba(0,0,0,0.04);
        }
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 1.4rem;
            color: #1a1a2e;
        }
        .brand-icon {
            width: 14px;
            height: 14px;
            background-color: #f26522;
            display: inline-block;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
            list-style: none;
        }
        .nav-links a {
            font-size: 0.9rem;
            font-weight: 500;
            color: #555;
            transition: color 0.2s;
            position: relative;
        }
        .nav-links a:hover { color: #f26522; }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background: #f26522;
            border-radius: 2px;
            transition: width 0.3s;
        }
        .nav-links a:hover::after { width: 100%; }

        .nav-actions { display: flex; gap: 12px; align-items: center; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 24px;
            border-radius: 50px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            gap: 8px;
            text-decoration: none;
        }
        .btn-outline-dark {
            background: transparent;
            border: 2px solid #1a1a2e;
            color: #1a1a2e;
        }
        .btn-outline-dark:hover {
            background: #1a1a2e;
            color: #ffffff;
        }
        .btn-orange {
            background: #f26522;
            color: #ffffff;
            border: 2px solid #f26522;
        }
        .btn-orange:hover {
            background: #d9551a;
            border-color: #d9551a;
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(242,101,34,0.25);
        }
        .btn-orange-outline {
            background: transparent;
            border: 2px solid #f26522;
            color: #f26522;
        }
        .btn-orange-outline:hover {
            background: #f26522;
            color: #ffffff;
            transform: translateY(-2px);
        }

        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 4px;
            background: none;
            border: none;
            color: #1a1a2e;
            font-size: 1.6rem;
        }
        .hamburger span {
            display: block;
            width: 24px;
            height: 2px;
            background: #1a1a2e;
            transition: all 0.3s;
        }

        /* ── Section Utilities ── */
        .section-inner {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }
        section { padding: 80px 48px; position: relative; }

        /* ── Hero ── */
        .hero {
            background: #fef9f4;
            padding: 60px 48px 80px;
        }
        .hero .section-inner {
            display: flex;
            align-items: center;
            gap: 48px;
        }
        .hero-text {
            flex: 1;
        }
        .hero-text h1 {
            font-size: 3rem;
            margin-bottom: 20px;
            line-height: 1.15;
            color: #1a1a2e;
        }
        .hero-text h1 .highlight {
            color: #f26522;
        }
        .hero-text p {
            color: #666;
            font-size: 1rem;
            margin-bottom: 32px;
            max-width: 440px;
            line-height: 1.7;
        }
        .hero-visual {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-visual img {
            max-width: 480px;
            width: 100%;
            height: auto;
            border-radius: 16px;
        }

        /* ── Testimonials ── */
        .testimonials {
            background: #ffffff;
            padding: 80px 48px;
        }
        .testimonials .section-inner > h2 {
            font-size: 2rem;
            margin-bottom: 40px;
            color: #1a1a2e;
        }
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }
        .testimonial-card {
            background: #fff8f2;
            border-radius: 16px;
            padding: 24px;
            border: 1px solid #fde8d8;
            transition: all 0.3s ease;
        }
        .testimonial-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(242,101,34,0.1);
        }
        .testi-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 14px;
        }
        .testi-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f26522, #ff8c00);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .testi-info h4 {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 2px;
        }
        .testi-info p {
            font-size: 0.75rem;
            color: #888;
            margin: 0;
        }
        .testi-text {
            font-size: 0.85rem;
            color: #555;
            line-height: 1.6;
        }

        /* ── Explore Courses ── */
        .explore-courses {
            background: #fef9f4;
            padding: 80px 48px;
        }
        .explore-courses .section-inner {
            display: flex;
            align-items: center;
            gap: 60px;
        }
        .explore-visual {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .explore-visual img {
            max-width: 420px;
            width: 100%;
            border-radius: 16px;
        }
        .explore-content {
            flex: 1;
        }
        .explore-content h2 {
            font-size: 2.2rem;
            margin-bottom: 20px;
            color: #1a1a2e;
            line-height: 1.2;
        }
        .explore-content p {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 28px;
            line-height: 1.7;
        }

        /* ── Student Success Stories ── */
        .success-stories {
            background: #ffffff;
            padding: 80px 48px;
        }
        .success-stories .section-inner {
            display: flex;
            align-items: center;
            gap: 60px;
        }
        .success-content {
            flex: 1;
        }
        .success-content h2 {
            font-size: 2.2rem;
            margin-bottom: 20px;
            color: #1a1a2e;
            line-height: 1.2;
        }
        .success-content p {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 28px;
            line-height: 1.7;
        }
        .success-visual {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .success-visual img {
            max-width: 420px;
            width: 100%;
            border-radius: 16px;
        }

        /* ── Join Community ── */
        .community {
            background: #fef9f4;
            padding: 80px 48px;
        }
        .community .section-inner {
            display: flex;
            align-items: center;
            gap: 60px;
        }
        .community-visual {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .community-visual img {
            max-width: 420px;
            width: 100%;
            border-radius: 16px;
        }
        .community-content {
            flex: 1;
        }
        .community-content h2 {
            font-size: 2.2rem;
            margin-bottom: 20px;
            color: #1a1a2e;
            line-height: 1.2;
        }
        .community-content p {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 28px;
            line-height: 1.7;
        }

        /* ── Footer ── */
        footer {
            background: #1a1a2e;
            color: #fff;
            padding: 60px 48px 32px;
        }
        .footer-grid {
            max-width: 1200px;
            margin: 0 auto 40px;
            display: grid;
            grid-template-columns: 1.5fr 1fr 1fr 1fr;
            gap: 40px;
        }
        .footer-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 1.3rem;
            margin-bottom: 14px;
            color: #ffffff;
        }
        .footer-brand .brand-icon-footer {
            width: 14px;
            height: 14px;
            background-color: #f26522;
            display: inline-block;
        }
        .footer-desc {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
            margin-bottom: 20px;
            line-height: 1.7;
            max-width: 300px;
        }
        .social-icons {
            display: flex;
            gap: 14px;
        }
        .social-icons a {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .social-icons a:hover {
            background: #f26522;
            transform: translateY(-3px);
        }
        .footer-col h4 {
            font-size: 0.95rem;
            margin-bottom: 18px;
            color: #ffffff;
            font-weight: 700;
        }
        .footer-col ul { list-style: none; }
        .footer-col li { margin-bottom: 10px; }
        .footer-col a {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
            transition: color 0.2s;
        }
        .footer-col a:hover { color: #f26522; }
        .footer-copy {
            text-align: center;
            padding-top: 28px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.8rem;
            color: rgba(255,255,255,0.4);
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ── Animations ── */
        @keyframes fadeSlideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.7s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* ── Responsive ── */
        @media (max-width: 1024px) {
            .testimonials-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 768px) {
            section, .hero, .testimonials, .explore-courses, .success-stories, .community { padding: 60px 24px; }
            .navbar { padding: 0 24px; }
            .nav-links { display: none; }
            .hamburger { display: flex; margin-left: 14px; }
            
            /* Keep Sign Up button but hide Login on mobile navbar to save space */
            .nav-actions .btn-outline-dark { display: none; }
            .nav-actions .btn-orange { 
                padding: 6px 16px; 
                font-size: 0.85rem; 
            }
            .nav-logo { flex: 1; }

            .nav-links.open {
                display: flex;
                flex-direction: column;
                position: absolute;
                top: 72px;
                left: 0;
                right: 0;
                background: #ffffff;
                border-bottom: 1px solid #f0f0f0;
                padding: 20px 24px;
                box-shadow: 0 16px 40px rgba(0,0,0,0.08);
                gap: 14px;
                z-index: 99;
            }
            .nav-links.open a { color: #1a1a2e; font-size: 1rem; }

            .hero .section-inner,
            .explore-courses .section-inner,
            .success-stories .section-inner,
            .community .section-inner {
                flex-direction: column;
                text-align: center;
            }
            .hero-text p { margin-left: auto; margin-right: auto; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
        }
        @media (max-width: 600px) {
            .hero-text h1 { font-size: 2.2rem; }
            .testimonials-grid { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .social-icons { justify-content: center; }
            .hero-visual img, .explore-visual img, .success-visual img, .community-visual img {
                max-width: 300px;
            }
        }
    </style>
</head>
<body>

<div class="page-wrapper">

    <!-- ── Navbar ── -->
    <nav class="navbar" id="navbar">
        <a href="#" class="nav-logo">
            <span class="brand-icon"></span> UTP
        </a>
        <ul class="nav-links" id="navLinks">
            <li><a href="#hero">Home</a></li>
            <li><a href="#services">About</a></li>
            <li><a href="#services">Programmes</a></li>
            <li><a href="#about">Contact</a></li>
            <li><a href="#about">Blog</a></li>
        </ul>
        <div class="nav-actions">
            <a href="/auth/login.php" class="btn btn-outline-dark">Login</a>
            <a href="/auth/signup.php" class="btn btn-orange">Sign Up</a>
        </div>
        <button class="hamburger" id="hamburgerBtn" onclick="toggleNav()" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
    </nav>

    <!-- ── Hero ── -->
    <section class="hero" id="hero">
        <div class="section-inner">
            <div class="hero-text animate-on-scroll">
                <h1>Unlock Your<br>Potential with<br><span class="highlight">Quality Education</span></h1>
                <p>Empowering learners with knowledge, skills, and opportunities for a brighter future.</p>
                <a href="/auth/signup.php" class="btn btn-orange" style="padding: 14px 36px; font-size: 1rem;">
                    Register
                </a>
            </div>
            <div class="hero-visual animate-on-scroll">
                <img src="/assets/images/hero_illustration.png" alt="Students studying together">
            </div>
        </div>
    </section>

    <!-- ── Testimonials ── -->
    <section class="testimonials" id="testimonials">
        <div class="section-inner">
            <h2 class="animate-on-scroll">Testimonials</h2>
            <div class="testimonials-grid">
                <div class="testimonial-card animate-on-scroll">
                    <div class="testi-header">
                        <div class="testi-avatar">AF</div>
                        <div class="testi-info">
                            <h4>Ahmad Farhan</h4>
                            <p>Foundation in Engineering</p>
                        </div>
                    </div>
                    <p class="testi-text">My foundation year at UTP gave me the perfect headstart. The lecturers are incredibly supportive and the facilities are world-class.</p>
                </div>
                <div class="testimonial-card animate-on-scroll">
                    <div class="testi-header">
                        <div class="testi-avatar">NR</div>
                        <div class="testi-info">
                            <h4>Nur Rashidah</h4>
                            <p>Mechanical Engineering</p>
                        </div>
                    </div>
                    <p class="testi-text">The scholarship opportunity at UTP changed my life. I learned practical skills that prepared me for a global engineering career.</p>
                </div>
                <div class="testimonial-card animate-on-scroll">
                    <div class="testi-header">
                        <div class="testi-avatar">DH</div>
                        <div class="testi-info">
                            <h4>Daniel Heng</h4>
                            <p>Computer Science</p>
                        </div>
                    </div>
                    <p class="testi-text">UTP's CS programme is top-notch. The industry connections and internship placements really set this university apart.</p>
                </div>
                <div class="testimonial-card animate-on-scroll">
                    <div class="testi-header">
                        <div class="testi-avatar">SA</div>
                        <div class="testi-info">
                            <h4>Siti Aminah</h4>
                            <p>Chemical Engineering</p>
                        </div>
                    </div>
                    <p class="testi-text">From day one, UTP made me feel like I belong. The campus, the community, and the learning environment are truly exceptional.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ── Explore Courses ── -->
    <section class="explore-courses" id="services">
        <div class="section-inner">
            <div class="explore-visual animate-on-scroll">
                <img src="/assets/images/courses_illustration.png" alt="Online courses illustration">
            </div>
            <div class="explore-content animate-on-scroll">
                <h2>Explore a wide<br>range of courses</h2>
                <p>Study at your own pace with online, in-person, or hybrid classes. Personalised guidance and support to help you achieve your aspirations. Gain practical, real-world knowledge to excel in your chosen field.</p>
                <a href="/auth/signup.php" class="btn btn-orange-outline" style="padding: 12px 32px;">
                    Register Today
                </a>
            </div>
        </div>
    </section>

    <!-- ── Student Success Stories ── -->
    <section class="success-stories" id="success">
        <div class="section-inner">
            <div class="success-content animate-on-scroll">
                <h2>Student Success Stories</h2>
                <p>Study at your own pace with online, in-person, or hybrid classes. Personalised guidance and support to help you achieve your aspirations. Gain practical, real-world knowledge to excel in your chosen field.</p>
                <a href="/auth/signup.php" class="btn btn-orange-outline" style="padding: 12px 32px;">
                    Register Today
                </a>
            </div>
            <div class="success-visual animate-on-scroll">
                <img src="/assets/images/success_illustration.png" alt="Student success illustration">
            </div>
        </div>
    </section>

    <!-- ── Join a Global Community ── -->
    <section class="community" id="about">
        <div class="section-inner">
            <div class="community-visual animate-on-scroll">
                <img src="/assets/images/community_illustration.png" alt="Global community illustration">
            </div>
            <div class="community-content animate-on-scroll">
                <h2>Join a Global Community</h2>
                <p>Study at your own pace with online, in-person, or hybrid classes. Personalised guidance and support to help you achieve your aspirations. Gain practical, real-world knowledge to excel in your chosen field.</p>
                <a href="#about" class="btn btn-orange" style="padding: 12px 32px;">
                    Learn More
                </a>
            </div>
        </div>
    </section>

    <!-- ── Footer ── -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <a href="#" class="footer-brand">
                    <span class="brand-icon-footer"></span> UTP
                </a>
                <p class="footer-desc">Empowering the next generation of engineers, technologists, and leaders through world-class education and research.</p>
                <div class="social-icons">
                    <a href="#" aria-label="Facebook">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                    </a>
                    <a href="#" aria-label="Instagram">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                    </a>
                    <a href="#" aria-label="Twitter">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/></svg>
                    </a>
                    <a href="#" aria-label="LinkedIn">
                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                    </a>
                </div>
            </div>
            <div class="footer-col">
                <h4>Admission</h4>
                <ul>
                    <li><a href="#">Foundation</a></li>
                    <li><a href="#">Undergraduate</a></li>
                    <li><a href="#">Postgraduate</a></li>
                    <li><a href="#">International</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Academic</h4>
                <ul>
                    <li><a href="#">Engineering</a></li>
                    <li><a href="#">Computer Science</a></li>
                    <li><a href="#">Science</a></li>
                    <li><a href="#">Management</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Resources</h4>
                <ul>
                    <li><a href="#">Student Portal</a></li>
                    <li><a href="#">Scholarships</a></li>
                    <li><a href="#">Campus Life</a></li>
                    <li><a href="#">FAQs</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-copy">
            &copy; 2024 Universiti Teknologi PETRONAS. All Rights Reserved.
        </div>
    </footer>
</div>

<script nonce="<?= $GLOBALS['csp_nonce'] ?>">
    /* ── Mobile Nav ── */
    function toggleNav() {
        document.getElementById('navLinks').classList.toggle('open');
    }

    /* ── Close nav on link click ── */
    document.querySelectorAll('.nav-links a').forEach(a => {
        a.addEventListener('click', () => {
            document.getElementById('navLinks').classList.remove('open');
        });
    });

    /* ── Scroll-triggered animations ── */
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
</script>
</body>
</html>
