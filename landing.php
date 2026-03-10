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
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        /* ── Reset & Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { scroll-behavior: smooth; }
        body {
            font-family: 'Poppins', sans-serif;
            color: #ffffff;
            background: linear-gradient(135deg, #f26522 0%, #ff8c00 40%, #ff4500 100%);
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Halftone Dot Overlay */
        body::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.06) 1.5px, transparent 1.5px);
            background-size: 14px 14px;
            z-index: 0;
            pointer-events: none;
        }

        h1, h2, h3, h4 { font-family: 'Nunito', sans-serif; font-weight: 900; line-height: 1.25; }
        a { text-decoration: none; color: inherit; }
        img { max-width: 100%; }
        .highlight { color: #FFD700; }

        /* ── Page Card Wrapper (Removed card, now full canvas) ── */
        .page-wrapper {
            position: relative;
            z-index: 1;
            width: 100%;
        }

        /* ── CSS Blob Shapes ── */
        .blob {
            position: absolute;
            background: rgba(255, 180, 50, 0.22);
            filter: blur(3px);
            animation: blobFloat 6s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }
        @keyframes blobFloat {
            0%,100% { border-radius: 70% 30% 60% 40% / 50% 60% 40% 50%; transform: translateY(0px); }
            50% { border-radius: 40% 60% 30% 70% / 60% 40% 50% 50%; transform: translateY(-14px); }
        }

        /* ── Decorative Elements ── */
        .deco-diamond {
            width: 10px; height: 10px;
            border: 2px solid rgba(255, 215, 0, 0.55);
            transform: rotate(45deg);
            position: absolute;
            z-index: 0;
        }
        .deco-cross::before { 
            content: '×'; 
            color: rgba(255,215,0,0.5); 
            font-size: 1.4rem; 
            position: absolute;
            z-index: 0; 
        }
        .deco-dots {
            width: 60px; height: 60px;
            background-image: radial-gradient(rgba(255,215,0,0.4) 1.5px, transparent 1.5px);
            background-size: 10px 10px;
            position: absolute;
            z-index: 0;
        }
        .deco-line {
            width: 40px; height: 3px;
            background: rgba(255,215,0,0.5);
            transform: rotate(-30deg);
            position: absolute;
            z-index: 0;
        }

        /* ── Navbar ── */
        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: transparent;
            padding: 24px 48px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .nav-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 1.5rem;
            color: #ffffff;
        }
        .nav-logo .ph-fire {
            font-size: 1.8rem;
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
            color: rgba(255,255,255,0.85);
            transition: color 0.2s;
            position: relative;
        }
        .nav-links a:hover { color: #ffffff; }
        .nav-links a::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 0;
            width: 0;
            height: 2px;
            background: #FFD700;
            border-radius: 2px;
            transition: width 0.3s;
        }
        .nav-links a:hover::after { width: 100%; }
        
        .nav-actions { display: flex; gap: 14px; align-items: center; }
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
        .btn-outline {
            background: transparent;
            border: 2px solid rgba(255,255,255,0.8);
            color: #ffffff;
        }
        .btn-outline:hover {
            border-color: #ffffff;
            background: rgba(255,255,255,0.1);
        }
        .btn-white {
            background: #ffffff;
            color: #f26522;
        }
        .btn-white:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        
        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 4px;
            background: none;
            border: none;
            color: #fff;
            font-size: 1.8rem;
        }

        /* ── Sections Wrapper ── */
        .section-inner {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        
        section { padding: 80px 48px; position: relative; }
        .section-label {
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #FFD700;
            margin-bottom: 12px;
        }
        .section-title {
            font-size: 2.2rem;
            margin-bottom: 16px;
            color: #ffffff;
        }
        .section-subtitle {
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
            max-width: 600px;
            margin: 0 auto 48px;
        }
        .text-center { text-align: center; }

        /* ── Hero ── */
        .hero {
            display: flex;
            align-items: center;
            min-height: 85vh;
            padding-top: 20px;
        }
        .hero .section-inner {
            display: flex;
            align-items: center;
            gap: 48px;
            width: 100%;
        }
        .hero-text { flex: 1; position: relative; }
        .hero-accent-line {
            width: 60px;
            height: 4px;
            background: #FFD700;
            border-radius: 2px;
            margin-bottom: 24px;
        }
        .hero-text h1 {
            font-size: 3.5rem;
            margin-bottom: 24px;
            line-height: 1.1;
            text-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .hero-text p {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
            margin-bottom: 40px;
            max-width: 480px;
            line-height: 1.8;
        }
        
        .hero-visual {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            min-height: 400px;
        }
        .hero-visual .blob-large {
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.15);
            top: 50%;
            left: 50%;
            margin-top: -200px;
            margin-left: -200px;
        }

        /* ── What We Offer (Why UTP) ── */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
        }
        .service-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 24px;
            padding: 32px 24px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .service-card:hover {
            transform: translateY(-8px);
            background: rgba(255,255,255,0.2);
            box-shadow: 0 16px 40px rgba(0,0,0,0.1);
        }
        .service-card:hover .service-icon {
            background: rgba(255,255,255,0.35);
        }
        .service-icon {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: #ffffff;
            margin-bottom: 20px;
            transition: background 0.3s ease;
        }
        .service-card h3 {
            font-size: 1.1rem;
            margin-bottom: 12px;
            color: #ffffff;
        }
        .service-card p {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.8);
            line-height: 1.6;
        }

        /* ── How to Apply ── */
        .how-to-apply .section-inner {
            display: flex;
            align-items: center;
            gap: 60px;
        }
        .how-visual {
            flex: 1;
            position: relative;
            min-height: 400px;
        }
        .how-visual .blob-med {
            width: 300px;
            height: 300px;
            background: rgba(255, 215, 0, 0.15);
            top: 50%;
            left: 50%;
            margin-top: -150px;
            margin-left: -150px;
        }
        .how-content {
            flex: 1;
        }
        .how-content h2 {
            font-size: 2.2rem;
            margin-bottom: 16px;
        }
        .how-content > p {
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
            margin-bottom: 32px;
            max-width: 440px;
        }
        .steps-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-bottom: 40px;
        }
        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            background: rgba(255,255,255,0.1);
            padding: 16px;
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.15);
        }
        .step-num {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: #ffffff;
            color: #f26522;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 1.2rem;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .step-text {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .step-item-title {
            font-size: 1rem;
            font-weight: 700;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .step-icon {
            color: #FFD700;
            font-size: 1.2rem;
        }
        .step-item-desc {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.7);
        }
        
        .how-actions { display: flex; gap: 16px; flex-wrap: wrap; }

        /* ── About UTP / Testimonials ── */
        .about .section-inner {
            display: flex;
            align-items: center;
            gap: 60px;
        }
        .about-text { flex: 1; }
        .about-text h2 { font-size: 2.2rem; margin-bottom: 20px; }
        .about-text p {
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
            margin-bottom: 32px;
            line-height: 1.8;
        }
        .about-visual {
            flex: 1;
            position: relative;
        }
        
        /* Testimonial Card Style */
        .testimonial-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 20px;
            padding: 32px;
            position: relative;
            z-index: 2;
        }
        .testi-stars {
            color: #FFD700;
            font-size: 1.2rem;
            display: flex;
            gap: 4px;
            margin-bottom: 16px;
        }
        .testi-quote {
            font-size: 1.05rem;
            font-style: italic;
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .testi-author {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .testi-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: #ffffff;
            color: #f26522;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Nunito', sans-serif;
            font-weight: 900;
            font-size: 1.4rem;
        }
        .testi-info h4 {
            font-size: 1rem;
            margin-bottom: 2px;
        }
        .testi-info p {
            font-size: 0.8rem;
            color: rgba(255,255,255,0.7);
            margin: 0;
        }

        /* ── CTA Banner ── */
        .cta-banner {
            max-width: 1100px;
            margin: 40px auto 80px;
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 24px;
            padding: 48px 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 32px;
            position: relative;
            z-index: 2;
        }
        .cta-text { flex: 1; }
        .cta-banner h3 {
            color: #ffffff;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cta-icon {
            color: #FFD700;
            font-size: 2.2rem;
        }
        .cta-banner p {
            color: rgba(255,255,255,0.85);
            font-size: 1rem;
            margin-top: 8px;
        }

        /* ── Footer ── */
        footer {
            background: rgba(0,0,0,0.25);
            color: #fff;
            padding: 80px 48px 32px;
            position: relative;
            z-index: 2;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .footer-grid {
            max-width: 1200px;
            margin: 0 auto 48px;
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
            font-size: 1.4rem;
            margin-bottom: 16px;
        }
        .footer-brand .ph-fire { font-size: 1.6rem; color: #FFD700; }
        
        .footer-desc {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
            margin-bottom: 24px;
            line-height: 1.7;
            max-width: 320px;
        }
        .social-icons {
            display: flex;
            gap: 12px;
        }
        .social-icons a {
            display: inline-flex;
            font-size: 1.5rem;
            color: #ffffff;
            transition: color 0.2s, transform 0.2s;
        }
        .social-icons a:hover {
            color: #FFD700;
            transform: translateY(-3px);
        }
        .footer-col h4 {
            font-size: 1rem;
            margin-bottom: 20px;
            color: #ffffff;
        }
        .footer-col ul { list-style: none; }
        .footer-col li { margin-bottom: 12px; }
        .footer-col a {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.7);
            transition: color 0.2s;
        }
        .footer-col a:hover { color: #FFD700; }
        .footer-copy {
            text-align: center;
            padding-top: 32px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
            max-width: 1200px;
            margin: 0 auto;
        }

        /* ── Modal (Dark mode / Glassmorphism over Gradient) ── */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            background: rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal {
            background: #ffffff;
            border-radius: 24px;
            padding: 40px;
            width: 92%;
            max-width: 460px;
            position: relative;
            transform: translateY(24px) scale(0.96);
            transition: transform 0.35s ease;
            box-shadow: 0 24px 60px rgba(0,0,0,0.3);
            color: #111111; /* Modals are white internally for readability */
        }
        .modal-overlay.active .modal {
            transform: translateY(0) scale(1);
        }
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: transparent;
            font-size: 1.4rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
            transition: all 0.2s;
        }
        .modal-close:hover { background: #fee2e2; color: #ef4444; }
        .modal h2 {
            font-size: 1.8rem;
            margin-bottom: 8px;
            text-align: center;
            color: #111111;
        }
        .modal .modal-subtitle {
            text-align: center;
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 28px;
        }
        .form-group { margin-bottom: 18px; }
        .form-group label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            color: #111111;
            margin-bottom: 8px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 12px;
            font-family: inherit;
            font-size: 0.9rem;
            background: #ffffff;
            transition: all 0.2s;
            outline: none;
            color: #111111;
        }
        .form-group input:focus, .form-group select:focus {
            border-color: #f26522;
            box-shadow: 0 0 0 4px rgba(242, 101, 34, 0.1);
        }
        .form-group input.error, .form-group select.error {
            border-color: #ef4444;
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
        }
        .form-error {
            font-size: 0.75rem;
            color: #ef4444;
            margin-top: 6px;
            min-height: 18px;
        }
        .modal .btn-orange {
            width: 100%;
            background: linear-gradient(135deg, #f26522, #ff4500);
            color: #fff;
            padding: 14px;
            font-size: 1rem;
            margin-top: 8px;
        }
        .modal .btn-orange:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(242, 101, 34, 0.3);
        }
        .modal-switch {
            text-align: center;
            margin-top: 24px;
            font-size: 0.85rem;
            color: #6b7280;
        }
        .modal-switch a {
            color: #f26522;
            font-weight: 700;
            cursor: pointer;
        }
        .modal-switch a:hover { text-decoration: underline; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        /* ── Mobile ── */
        @media (max-width: 768px) {
            section { padding: 60px 24px; }
            .navbar { padding: 20px 24px; }
            .nav-links { display: none; }
            .hamburger { display: flex; }
            
            /* Add Mobile Menu Class Here if needed based on original */
            .nav-links.open {
                display: flex;
                flex-direction: column;
                position: absolute;
                top: 80px;
                left: 24px;
                right: 24px;
                background: rgba(255,255,255,0.95);
                backdrop-filter: blur(10px);
                border-radius: 16px;
                padding: 24px;
                box-shadow: 0 16px 40px rgba(0,0,0,0.15);
                gap: 16px;
            }
            .nav-links.open a { color: #111111; font-size: 1.1rem; }
            
            .services-grid { grid-template-columns: repeat(2, 1fr); }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
            .hero .section-inner, .how-to-apply .section-inner, .about .section-inner { flex-direction: column; }
        }
        @media (max-width: 600px) {
            .blob, .deco-diamond, .deco-cross, .deco-dots, .deco-line { display: none; } /* Hide heavy decos on small mobile */
            .hero .section-inner { text-align: center; gap: 32px; }
            .hero-accent-line { margin: 0 auto 24px; }
            .hero-text h1 { font-size: 2.4rem; }
            .hero-text p { margin-left: auto; margin-right: auto; }
            .hero-visual { display: none; }
            
            .how-visual, .about-visual { display: none; }
            .services-grid { grid-template-columns: 1fr; }
            
            .cta-banner { flex-direction: column; text-align: center; padding: 32px 24px; margin: 32px 24px; }
            .cta-banner h3 { flex-direction: column; font-size: 1.5rem; }
            .footer-grid { grid-template-columns: 1fr; text-align: center; }
            .social-icons { justify-content: center; }
            .form-row { grid-template-columns: 1fr; gap: 0; }
        }
    </style>
</head>
<body>

<!-- ═══════════════════ MAIN CONTENT ═══════════════════ -->
<div class="page-wrapper">

    <!-- ── Navbar ── -->
    <nav class="navbar" id="navbar">
        <a href="#" class="nav-logo">
            <i class="ph-bold ph-fire"></i> UTP
        </a>
        <ul class="nav-links" id="navLinks">
            <li><a href="#hero">Home</a></li>
            <li><a href="#services">About</a></li>
            <li><a href="#services">Programmes</a></li>
            <li><a href="#about">Contact</a></li>
            <li><a href="#about">Blog</a></li>
        </ul>
        <div class="nav-actions">
            <button class="btn btn-outline" onclick="openModal('login')">
                <i class="ph-bold ph-sign-in"></i> Login
            </button>
            <button class="btn btn-white" onclick="openModal('signup')">
                <i class="ph-bold ph-user-plus"></i> Sign Up
            </button>
        </div>
        <button class="hamburger" id="hamburgerBtn" onclick="toggleNav()" aria-label="Menu">
            <i class="ph-bold ph-list"></i>
        </button>
    </nav>

    <!-- ── Hero ── -->
    <section class="hero" id="hero">
        <div class="blob blob-large"></div>
        <div class="deco-diamond" style="top: 15%; left: 8%;"></div>
        <div class="deco-cross" style="bottom: 20%; left: 45%;"></div>
        <div class="deco-dots" style="top: 10%; right: 10%;"></div>
        
        <div class="section-inner">
            <div class="hero-text">
                <div class="hero-accent-line"></div>
                <h1>We Guide Your<br>Journey into <span class="highlight">UTP</span></h1>
                <p>Discover and apply for world-class foundation programmes at Universiti Teknologi PETRONAS. Your future in engineering, technology, and science starts here.</p>
                <div class="hero-actions">
                    <button class="btn btn-white" onclick="openModal('signup')" style="padding: 14px 32px; font-size: 1rem;">
                        Apply Now <i class="ph-bold ph-arrow-right"></i>
                    </button>
                </div>
            </div>
            <div class="hero-visual">
                <div class="deco-line" style="top: 30%; right: 20%;"></div>
                <div class="deco-diamond" style="bottom: 15%; right: 15%; width: 14px; height: 14px;"></div>
            </div>
        </div>
    </section>

    <!-- ── What We Offer (Why UTP) ── -->
    <section class="services text-center" id="services">
        <div class="deco-dots" style="top: 20%; left: 5%;"></div>
        <div class="deco-diamond" style="bottom: 15%; right: 10%;"></div>

        <div class="section-inner">
            <p class="section-label">Why Choose Us</p>
            <h2 class="section-title">World-Class <span class="highlight">Programmes</span></h2>
            <p class="section-subtitle">UTP offers a range of renowned foundation and undergraduate programmes backed by PETRONAS — preparing you for a global career.</p>
            <div class="services-grid">
                <div class="service-card">
                    <div class="service-icon"><i class="ph-bold ph-graduation-cap"></i></div>
                    <h3>Foundation</h3>
                    <p>A strong academic foundation in science, engineering, and technology to kickstart your journey.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon"><i class="ph-bold ph-flask"></i></div>
                    <h3>Engineering</h3>
                    <p>Top-tier chemical, mechanical, civil, and electrical engineering programmes recognised globally.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon"><i class="ph-bold ph-laptop"></i></div>
                    <h3>Computing & CS</h3>
                    <p>Cutting-edge computer science and information technology programmes for the digital age.</p>
                </div>
                <div class="service-card">
                    <div class="service-icon"><i class="ph-bold ph-atom"></i></div>
                    <h3>Science & Mgmt</h3>
                    <p>Applied sciences, geosciences, and business management — blending research with real skills.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ── How to Apply ── -->
    <section class="how-to-apply" id="how">
        <div class="blob blob-med"></div>
        <div class="deco-cross" style="top: 10%; right: 15%;"></div>
        <div class="deco-line" style="bottom: 20%; right: 5%;"></div>
        <div class="deco-dots" style="bottom: 10%; left: 10%;"></div>

        <div class="section-inner">
            <div class="how-visual"></div>
            <div class="how-content">
                <p class="section-label">Simple Solutions</p>
                <h2>Simple Application <span class="highlight">Steps!</span></h2>
                <p>Our streamlined application process makes it easy to apply for your desired programme in just a few steps.</p>
                
                <div class="steps-list">
                    <div class="step-item">
                        <div class="step-num">1</div>
                        <div class="step-text">
                            <div class="step-item-title"><i class="ph-bold ph-list-numbers step-icon"></i> Choose Programme</div>
                            <div class="step-item-desc">Find the course that fits your career goals.</div>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">2</div>
                        <div class="step-text">
                            <div class="step-item-title"><i class="ph-bold ph-folder-open step-icon"></i> Prepare Documents</div>
                            <div class="step-item-desc">Gather transcripts, certificates, and ID.</div>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">3</div>
                        <div class="step-text">
                            <div class="step-item-title"><i class="ph-bold ph-paper-plane-tilt step-icon"></i> Submit Application</div>
                            <div class="step-item-desc">Fill out the form and submit securely.</div>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-num">4</div>
                        <div class="step-text">
                            <div class="step-item-title"><i class="ph-bold ph-envelope-open step-icon"></i> Await Offer</div>
                            <div class="step-item-desc">Track your status and receive your offer letter.</div>
                        </div>
                    </div>
                </div>
                
                <div class="how-actions">
                    <button class="btn btn-white" onclick="openModal('signup')">
                        Apply Now <i class="ph-bold ph-arrow-right"></i>
                    </button>
                    <button class="btn btn-outline" style="padding: 10px 24px;">Read More</button>
                </div>
            </div>
        </div>
    </section>

    <!-- ── About UTP / Testimonials ── -->
    <section class="about" id="about">
        <div class="deco-diamond" style="top: 20%; left: 40%; width: 12px; height: 12px;"></div>
        <div class="deco-dots" style="bottom: 15%; right: 5%;"></div>
        
        <div class="section-inner">
            <div class="about-text">
                <p class="section-label">Discover UTP</p>
                <h2>A Campus Designed for <span class="highlight">Innovation</span></h2>
                <p>Located in Seri Iskandar, Perak, our award-winning campus integrates nature with state-of-the-art facilities. We empower students to become well-rounded graduates ready to lead the future of energy, engineering, and technology.</p>
                
                <div class="cta-banner" style="margin: 32px 0 0 0; padding: 24px 32px; flex-direction: column; align-items: flex-start; gap: 16px;">
                    <div style="display: flex; gap: 16px; align-items: center;">
                        <i class="ph-bold ph-certificate cta-icon" style="font-size: 2rem;"></i>
                        <div>
                            <h3 style="font-size: 1.2rem; margin-bottom: 0;">Top 300 Global University</h3>
                            <p style="font-size: 0.85rem; margin: 0;">Consistently ranked among the best in Asia and the world.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="about-visual">
                <div class="testimonial-card">
                    <div class="testi-stars">
                        <i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i><i class="ph-fill ph-star"></i>
                    </div>
                    <p class="testi-quote">"My foundation year at UTP gave me the perfect headstart. The lecturers are incredibly supportive, and the facilities are world-class. It truly feels like a second home."</p>
                    <div class="testi-author">
                        <div class="testi-avatar">A</div>
                        <div class="testi-info">
                            <h4>Ahmad Farhan</h4>
                            <p>Foundation in Engineering</p>
                        </div>
                    </div>
                </div>
                <div class="deco-cross" style="top: -20px; right: -20px;"></div>
                <div class="deco-diamond" style="bottom: -20px; left: -20px;"></div>
            </div>
        </div>
    </section>

    <!-- ── CTA Banner ── -->
    <div class="cta-banner">
        <div class="cta-text">
            <h3><i class="ph-bold ph-rocket-launch cta-icon"></i> Ready to start your journey?</h3>
            <p>Join thousands of students building their future at Universiti Teknologi PETRONAS.</p>
        </div>
        <button class="btn btn-white" onclick="openModal('signup')" style="padding: 14px 36px; font-size: 1rem;">
            Create Account
        </button>
    </div>

    <!-- ── Footer ── -->
    <footer>
        <div class="deco-line" style="top: 40px; right: 10%;"></div>
        <div class="footer-grid">
            <div class="footer-col">
                <a href="#" class="footer-brand">
                    <i class="ph-bold ph-fire"></i> UTP
                </a>
                <p class="footer-desc">Empowering the next generation of engineers, technologists, and leaders through world-class education and research.</p>
                <div class="social-icons">
                    <a href="#"><i class="ph-bold ph-facebook-logo"></i></a>
                    <a href="#"><i class="ph-bold ph-instagram-logo"></i></a>
                    <a href="#"><i class="ph-bold ph-twitter-logo"></i></a>
                    <a href="#"><i class="ph-bold ph-linkedin-logo"></i></a>
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

<!-- ═══════════════════ LOGIN MODAL ═══════════════════ -->
<div class="modal-overlay" id="loginModal" onclick="handleOverlayClick(event, 'loginModal')">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('loginModal')">✕</button>
        <h2>Welcome Back</h2>
        <p class="modal-subtitle">Log in to your UTP student account</p>
        <form id="loginForm" onsubmit="return handleLogin(event)">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="form-group">
                <label for="loginEmail">Email Address</label>
                <input type="email" id="loginEmail" placeholder="your@email.com" required>
                <div class="form-error" id="loginEmailError"></div>
            </div>
            <div class="form-group">
                <label for="loginPassword">Password</label>
                <input type="password" id="loginPassword" placeholder="Enter your password" required>
                <div class="form-error" id="loginPasswordError"></div>
            </div>
            <button type="submit" class="btn btn-orange">Login</button>
        </form>
        <p class="modal-switch">Don't have an account? <a onclick="switchModal('loginModal','signupModal')">Sign Up</a></p>
    </div>
</div>

<!-- ═══════════════════ SIGNUP MODAL ═══════════════════ -->
<div class="modal-overlay" id="signupModal" onclick="handleOverlayClick(event, 'signupModal')">
    <div class="modal">
        <button class="modal-close" onclick="closeModal('signupModal')">✕</button>
        <h2>Create Account</h2>
        <p class="modal-subtitle">Sign up to apply for UTP programmes</p>
        <form id="signupForm" onsubmit="return handleSignup(event)">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <div class="form-group">
                <label for="signupName">Full Name</label>
                <input type="text" id="signupName" placeholder="As per IC" required>
                <div class="form-error" id="signupNameError"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="signupEmail">Email</label>
                    <input type="email" id="signupEmail" placeholder="your@email.com" required>
                    <div class="form-error" id="signupEmailError"></div>
                </div>
                <div class="form-group">
                    <label for="signupIC">IC / Passport No.</label>
                    <input type="text" id="signupIC" placeholder="XXXXXX-XX-XXXX" required>
                    <div class="form-error" id="signupICError"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="signupPassword">Password</label>
                    <input type="password" id="signupPassword" placeholder="Min 8 characters" required>
                    <div class="form-error" id="signupPasswordError"></div>
                </div>
                <div class="form-group">
                    <label for="signupConfirm">Confirm Password</label>
                    <input type="password" id="signupConfirm" placeholder="Re-enter password" required>
                    <div class="form-error" id="signupConfirmError"></div>
                </div>
            </div>
            <div class="form-group">
                <label for="signupProgramme">Programme Interest</label>
                <select id="signupProgramme" required>
                    <option value="" disabled selected>Select programme level</option>
                    <option value="foundation">Foundation</option>
                    <option value="undergraduate">Undergraduate</option>
                    <option value="postgraduate">Postgraduate</option>
                </select>
                <div class="form-error" id="signupProgrammeError"></div>
            </div>
            <button type="submit" class="btn btn-orange">Create Account</button>
        </form>
        <p class="modal-switch">Already have an account? <a onclick="switchModal('signupModal','loginModal')">Login</a></p>
    </div>
</div>

<script>
    /* ── System URL — your live app ── */
    const SYSTEM_LOGIN_URL = '/auth/login.php';
    const SYSTEM_SIGNUP_URL = '/auth/signup.php';

    /* ── Modal Logic ── */
    function openModal(type) {
        if (type === 'login') {
            window.location.href = 'http://localhost:8000/auth/login.php';
        } else if (type === 'signup') {
            window.location.href = 'http://localhost:8000/auth/signup.php'; // or '/signup.php' depending on the app structure, but forcing localhost:8000 to match user's explicit request
        }
    }
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
        document.body.style.overflow = '';
        clearErrors(id);
    }
    function switchModal(fromId, toId) {
        document.getElementById(fromId).classList.remove('active');
        clearErrors(fromId);
        setTimeout(() => {
            document.getElementById(toId).classList.add('active');
        }, 80);
    }
    function handleOverlayClick(e, id) {
        if (e.target === e.currentTarget) closeModal(id);
    }
    function clearErrors(modalId) {
        document.querySelectorAll('#' + modalId + ' .form-error').forEach(el => el.textContent = '');
        document.querySelectorAll('#' + modalId + ' .error').forEach(el => el.classList.remove('error'));
    }

    /* ── Validation ── */
    function setError(inputId, errorId, msg) {
        document.getElementById(inputId).classList.add('error');
        document.getElementById(errorId).textContent = msg;
    }
    function clearError(inputId, errorId) {
        document.getElementById(inputId).classList.remove('error');
        document.getElementById(errorId).textContent = '';
    }

    function handleLogin(e) {
        e.preventDefault();
        let valid = true;
        const email = document.getElementById('loginEmail').value.trim();
        const password = document.getElementById('loginPassword').value;

        clearError('loginEmail', 'loginEmailError');
        clearError('loginPassword', 'loginPasswordError');

        if (!email) { setError('loginEmail', 'loginEmailError', 'Email is required.'); valid = false; }
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { setError('loginEmail', 'loginEmailError', 'Enter a valid email.'); valid = false; }
        if (!password) { setError('loginPassword', 'loginPasswordError', 'Password is required.'); valid = false; }

        if (valid) window.location.href = SYSTEM_LOGIN_URL;
        return false;
    }

    function handleSignup(e) {
        e.preventDefault();
        let valid = true;
        const fields = {
            name: document.getElementById('signupName').value.trim(),
            email: document.getElementById('signupEmail').value.trim(),
            ic: document.getElementById('signupIC').value.trim(),
            password: document.getElementById('signupPassword').value,
            confirm: document.getElementById('signupConfirm').value,
            programme: document.getElementById('signupProgramme').value,
        };

        /* Clear */
        ['signupName','signupEmail','signupIC','signupPassword','signupConfirm','signupProgramme'].forEach(id => {
            clearError(id, id + 'Error');
        });

        if (!fields.name) { setError('signupName', 'signupNameError', 'Full name is required.'); valid = false; }
        if (!fields.email) { setError('signupEmail', 'signupEmailError', 'Email is required.'); valid = false; }
        else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(fields.email)) { setError('signupEmail', 'signupEmailError', 'Enter a valid email.'); valid = false; }
        if (!fields.ic) { setError('signupIC', 'signupICError', 'IC / Passport is required.'); valid = false; }
        if (!fields.password) { setError('signupPassword', 'signupPasswordError', 'Password is required.'); valid = false; }
        else if (fields.password.length < 8) { setError('signupPassword', 'signupPasswordError', 'Min 8 characters.'); valid = false; }
        if (!fields.confirm) { setError('signupConfirm', 'signupConfirmError', 'Confirm your password.'); valid = false; }
        else if (fields.password !== fields.confirm) { setError('signupConfirm', 'signupConfirmError', 'Passwords do not match.'); valid = false; }
        if (!fields.programme) { setError('signupProgramme', 'signupProgrammeError', 'Please select a programme.'); valid = false; }

        if (valid) window.location.href = SYSTEM_SIGNUP_URL;
        return false;
    }

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

    /* ── Escape key closes modals ── */
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeModal('loginModal');
            closeModal('signupModal');
        }
    });

    /* ── Interactive Parallax Animation ── */
    const parallaxElements = document.querySelectorAll('.blob, .deco-diamond, .deco-cross, .deco-dots, .deco-line');
    document.addEventListener('mousemove', (e) => {
        // Calculate offset from center of viewport
        const x = (window.innerWidth / 2 - e.clientX) / 35;
        const y = (window.innerHeight / 2 - e.clientY) / 35;

        parallaxElements.forEach((el, index) => {
            // Vary speed and direction slightly per element
            const speed = (index % 4 + 1) * (index % 2 === 0 ? 1 : -0.7);
            
            // Modern CSS translate property separates translation from transform (rotations/keyframes)
            el.style.translate = `${x * speed}px ${y * speed}px`;
            el.style.transition = 'translate 0.1s ease-out'; // slight smoothing
        });
    });
</script>
</body>
</html>
