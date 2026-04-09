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
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    <link rel="stylesheet" href="/assets/css/landing.css">

</head>
<body>

<div class="page-wrapper">

    <!-- ── Navbar ── -->
    <nav class="navbar" id="navbar">
        <a href="#" class="nav-logo">
            <img src="https://www.utp.edu.my/SiteAssets/UTP-logo2.png" alt="UTP" class="brand-logo" style="height:36px;width:auto;"> UTP
        </a>
        <ul class="nav-links" id="navLinks">
            <li><a href="#hero">Home</a></li>
            <li><a href="#testimonials">Testimonials</a></li>
            <li><a href="#services">Programmes</a></li>
            <li><a href="#success">Success Stories</a></li>
            <li><a href="#about">Community</a></li>
        </ul>
        <div class="nav-actions">
            <a href="/auth/login.php" class="btn btn-outline-dark">Login</a>
            <a href="/auth/signup.php" class="btn btn-orange">Sign Up</a>
            <!-- Mobile Hamburger -->
            <button class="hamburger" id="mobileNavToggle" aria-label="Toggle Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
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
                <p>Our students consistently achieve outstanding results, winning national and international awards. From securing positions at Fortune 500 companies to launching innovative startups, UTP graduates are making their mark globally.</p>
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
                <p>Connect with over 10,000 students from 60+ countries. Build lifelong friendships, explore diverse perspectives, and grow both personally and professionally through vibrant campus activities and student organisations.</p>
                <a href="/auth/signup.php" class="btn btn-orange" style="padding: 12px 32px;">
                    Join Us
                </a>
            </div>
        </div>
    </section>

    <!-- ── Footer ── -->
    <footer>
        <div class="footer-grid">
            <div class="footer-col">
                <a href="#" class="footer-brand">
                    <img src="https://www.utp.edu.my/SiteAssets/UTP-logo2.png" alt="UTP" class="brand-logo" style="height:30px;width:auto;"> UTP
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
                <li><a href="/auth/signup.php">Foundation</a></li>
                    <li><a href="/auth/signup.php">Undergraduate</a></li>
                    <li><a href="/auth/signup.php">Postgraduate</a></li>
                    <li><a href="/auth/signup.php">International</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Academic</h4>
                <ul>
                    <li><a href="/auth/signup.php">Engineering</a></li>
                    <li><a href="/auth/signup.php">Computer Science</a></li>
                    <li><a href="/auth/signup.php">Science</a></li>
                    <li><a href="/auth/signup.php">Management</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Resources</h4>
                <ul>
                    <li><a href="/auth/login.php">Student Portal</a></li>
                    <li><a href="/auth/signup.php">Scholarships</a></li>
                    <li><a href="#about">Campus Life</a></li>
                    <li><a href="#testimonials">FAQs</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-copy">
            &copy; <?= date('Y') ?> Universiti Teknologi PETRONAS. All Rights Reserved.
        </div>
    </footer>
</div>

<script nonce="<?= $GLOBALS['csp_nonce'] ?>">
    /* ── Mobile Nav ── */
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const navLinksList = document.getElementById('navLinks');
    
    if (mobileNavToggle && navLinksList) {
        mobileNavToggle.addEventListener('click', () => {
            navLinksList.classList.toggle('open');
        });
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
