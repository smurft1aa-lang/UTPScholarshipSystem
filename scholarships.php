<?php
require_once __DIR__ . '/includes/init.php';

setSecurityHeaders();
initSession();

$csrfToken = generateCSRFToken();

// Scholarship data array — all real, verified scholarships & sponsorships
$scholarships = [
    [
        'name' => 'PETRONAS Education Sponsorship Programme (PESP)',
        'type' => 'corporate',
        'badge' => 'Corporate',
        'desc' => 'The premier PETRONAS sponsorship covering full tuition fees, accommodation, books, laptop, and monthly allowance for Foundation and Undergraduate studies at UTP and other approved universities.',
        'requirements' => ['Malaysian citizen', 'Current year SPM with minimum 8A', 'Strong co-curricular & leadership involvement', 'Pass PETRONAS selection assessment & interview'],
        'coverage' => ['Full tuition fees', 'Accommodation & living allowance', 'Books & laptop allowance', 'Monthly stipend'],
        'website' => ['url' => 'https://www.petronasedup.com.my', 'label' => 'petronasedup.com.my'],
        'email' => 'edup@petronas.com',
        'phone' => null,
    ],
    [
        'name' => 'Yayasan UTP Scholarship (Undergraduate)',
        'type' => 'internal',
        'badge' => 'UTP Internal',
        'desc' => 'Awarded to deserving Malaysian students with excellent academic results and active extracurricular involvement. Supports students from low-income households to pursue undergraduate studies at UTP.',
        'requirements' => ['Malaysian citizen', 'Minimum 8A in SPM', 'Monthly household income ≤ RM4,000', 'Active in extracurricular activities', 'Not a holder of any other scholarship'],
        'coverage' => ['Full tuition fee waiver', 'Monthly living allowance', 'Book & material allowance'],
        'website' => ['url' => 'https://www.utp.edu.my', 'label' => 'utp.edu.my'],
        'email' => 'yayasan_utp@utp.edu.my',
        'phone' => '05-368 8000',
    ],
    [
        'name' => 'YUTP Education Grant',
        'type' => 'internal',
        'badge' => 'UTP Internal',
        'desc' => 'Partial financial assistance for outstanding active UTP students from low to middle-income families. Designed to bridge the gap for students who demonstrate strong academic performance but face financial constraints.',
        'requirements' => ['Malaysian citizen', 'Active UTP student', 'Monthly household income ≤ RM10,000', 'Min CGPA 3.50 (Foundation/Matriculation/STPM)', 'Not a holder of any other scholarship'],
        'coverage' => ['Partial tuition fee assistance', 'Covers study-related expenses'],
        'website' => ['url' => 'https://www.utp.edu.my', 'label' => 'utp.edu.my'],
        'email' => 'yayasan_utp@utp.edu.my',
        'phone' => '05-368 8000',
    ],
    [
        'name' => 'YUTP Student Support Fund (Bursary)',
        'type' => 'internal',
        'badge' => 'UTP Internal',
        'desc' => 'One-off financial assistance for UTP students facing temporary financial difficulties. Ensures that financial pressure does not disrupt academic performance. Available for PTPTN loan holders and self-sponsored students.',
        'requirements' => ['Malaysian citizen', 'PTPTN or self-sponsored student only', 'Monthly household income ≤ RM6,000', 'Minimum semester GPA of 2.75'],
        'coverage' => ['One-time financial grant', 'Covers immediate academic-related expenses'],
        'website' => null,
        'email' => 'nursakinah.salim@utp.edu.my',
        'phone' => '05-368 8000',
    ],
    [
        'name' => 'Yayasan UTP Prestigious Scholarship (Postgraduate)',
        'type' => 'internal',
        'badge' => 'UTP Internal',
        'desc' => 'A highly competitive scholarship for top-tier postgraduate students at UTP. Targets candidates with strong academic records and demonstrated commitment to extracurricular and community service.',
        'requirements' => ['Malaysian citizen', 'Min CGPA 3.25 in Bachelor\'s Degree', 'For Master\'s: CGPA 3.25 (coursework) or 1 published journal article (research)', 'Active in extracurricular activities & community service', 'Not a holder of any other scholarship'],
        'coverage' => ['Full tuition fee waiver', 'Monthly stipend', 'Research allowance'],
        'website' => ['url' => 'https://www.utp.edu.my', 'label' => 'utp.edu.my'],
        'email' => 'yayasan_utp@utp.edu.my',
        'phone' => '05-368 8000',
    ],
    [
        'name' => 'Tabung Amanah Zakat UTP (TAZU)',
        'type' => 'internal',
        'badge' => 'UTP Internal',
        'desc' => 'Zakat-funded financial assistance specifically for Malaysian Muslim students at UTP. Provides academic grants and bursaries to help with tuition and living expenses through zakat (alms) distribution.',
        'requirements' => ['Malaysian Muslim', 'Active full-time UTP student', 'No disciplinary or crime records', 'Min GPA/CGPA 2.00 – 3.00 (varies by scheme)', 'Per capita income below RM3,243'],
        'coverage' => ['Academic grant', 'Living expense support', 'Bursary for immediate needs'],
        'website' => ['url' => 'https://www.utp.edu.my', 'label' => 'utp.edu.my'],
        'email' => 'zakat@utp.edu.my',
        'phone' => '05-368 8027',
    ],
    [
        'name' => 'JPA Scholarship (Jabatan Perkhidmatan Awam)',
        'type' => 'government',
        'badge' => 'Government',
        'desc' => 'The Public Service Department (JPA) sponsors high-achieving SPM leavers for foundation and undergraduate studies at approved institutions including UTP. Offered as a convertible loan — graduates who fulfill service obligations may have it converted to a full scholarship.',
        'requirements' => ['Malaysian citizen', 'Minimum 9A+ in SPM', 'Age below 20 years old', 'Pass JPA selection assessment & interview'],
        'coverage' => ['Full tuition fees', 'Living allowance', 'Book & material allowance', 'Convertible loan scheme'],
        'website' => ['url' => 'https://esilav2.jpa.gov.my', 'label' => 'esilav2.jpa.gov.my'],
        'email' => null,
        'phone' => '03-8885 3000',
    ],
    [
        'name' => 'MARA Sponsorship (Majlis Amanah Rakyat)',
        'type' => 'government',
        'badge' => 'Government',
        'desc' => 'MARA provides educational sponsorship for Bumiputera students through the Tertiary Education Sponsorship Programme (TESP) and other collaborative programmes. Covers local tertiary studies at approved institutions including UTP.',
        'requirements' => ['Bumiputera status (applicant & at least one parent)', 'Malaysian citizen', 'Meet academic requirements for chosen programme', 'Not blacklisted by MARA / no concurrent sponsorship', 'Medically fit'],
        'coverage' => ['Tuition fees', 'Living allowance', 'Study materials'],
        'website' => ['url' => 'https://www.mara.gov.my', 'label' => 'mara.gov.my'],
        'email' => null,
        'phone' => '03-2691 5111',
    ],
    [
        'name' => 'PTPTN Study Loan (Perbadanan Tabung Pendidikan Tinggi Nasional)',
        'type' => 'government',
        'badge' => 'Government',
        'desc' => 'The National Higher Education Fund provides study loans for Malaysian students pursuing diploma or degree programmes at accredited institutions. Graduates who obtain First-Class Honours may apply for full loan repayment exemption.',
        'requirements' => ['Malaysian citizen, aged 45 or below', 'Official offer letter from UTP', 'Course accredited by MQA', 'Active Simpan SSPN (Prime or Plus) account', 'At least one semester of study remaining'],
        'coverage' => ['Tuition fees (needs-based amount)', 'Living expenses allowance', 'First-Class Honours loan exemption eligible'],
        'website' => ['url' => 'https://www.ptptn.gov.my', 'label' => 'ptptn.gov.my'],
        'email' => null,
        'phone' => '03-2193 3000',
    ],
    [
        'name' => 'Khazanah Watan Scholarship',
        'type' => 'corporate',
        'badge' => 'Corporate',
        'desc' => 'A prestigious scholarship by Yayasan Khazanah for high-achieving Malaysian students pursuing undergraduate studies at approved local universities. Emphasises academic excellence, leadership, and community impact.',
        'requirements' => ['Malaysian citizen', 'Minimum 8A in SPM or equivalent', 'Strong leadership & extracurricular record', 'Age below 21 years old'],
        'coverage' => ['Full tuition fees', 'Living allowance & stipend', 'Leadership development programmes'],
        'website' => ['url' => 'https://www.yayasankhazanah.com.my', 'label' => 'yayasankhazanah.com.my'],
        'email' => 'scholarship@yayasankhazanah.com.my',
        'phone' => null,
    ],
    [
        'name' => 'Yayasan Tenaga Nasional (YTN) Scholarship',
        'type' => 'corporate',
        'badge' => 'Corporate',
        'desc' => 'The TNB Prime Scholarship by Yayasan Tenaga Nasional supports Malaysian students pursuing foundation, undergraduate, or postgraduate studies in engineering, science, and technology fields at approved institutions including UTP.',
        'requirements' => ['Malaysian citizen', 'Outstanding academic results (SPM/STPM or equivalent)', 'Active in extracurricular & leadership activities', 'Age below 25 years old'],
        'coverage' => ['Full tuition fees', 'Monthly living allowance', 'Book & material allowance', 'Industrial training placement'],
        'website' => ['url' => 'https://www.tnb.com.my', 'label' => 'tnb.com.my'],
        'email' => null,
        'phone' => '03-2296 5566',
    ],
    [
        'name' => 'Yayasan Sime Darby Scholarship',
        'type' => 'corporate',
        'badge' => 'Corporate',
        'desc' => 'Yayasan Sime Darby offers scholarships for Malaysian students pursuing undergraduate studies in engineering, science, and related fields at approved local universities. Focuses on developing future leaders for the plantation, industrial, and property sectors.',
        'requirements' => ['Malaysian citizen', 'Age 25 or below', 'Min CGPA 3.30 (pre-university qualification)', 'Monthly household income ≤ RM11,000', 'Strong leadership qualities'],
        'coverage' => ['Full tuition fees', 'Living allowance', 'Book & material allowance', 'Mentorship programme'],
        'website' => ['url' => 'https://www.yayasansimedarby.com', 'label' => 'yayasansimedarby.com'],
        'email' => 'scholarships@sime.com',
        'phone' => null,
    ],
    [
        'name' => 'Yayasan Gamuda Scholarship',
        'type' => 'corporate',
        'badge' => 'Corporate',
        'desc' => 'Yayasan Gamuda provides scholarships for high-achieving Malaysian students pursuing engineering and related degrees at approved institutions. Emphasises financial need and leadership potential alongside academic excellence.',
        'requirements' => ['Malaysian citizen', 'Min CGPA 3.40 (STPM or equivalent)', 'Demonstrate financial need', 'Active in leadership & community activities'],
        'coverage' => ['Full tuition fees', 'Living allowance', 'Internship opportunities with Gamuda Group'],
        'website' => ['url' => 'https://gamuda.com.my/yayasan-gamuda/scholarship/', 'label' => 'gamuda.com.my'],
        'email' => null,
        'phone' => '03-7491 8288',
    ],
    [
        'name' => 'Shell Malaysia Scholarship',
        'type' => 'corporate',
        'badge' => 'Corporate',
        'desc' => 'Shell Malaysia sponsors high-achieving students in engineering and science fields. One of the oldest and most prestigious corporate scholarship programmes in Malaysia, providing a pathway to a career in the energy sector.',
        'requirements' => ['Malaysian citizen', 'Excellent SPM/O-Level/UEC results', 'Strong leadership & extracurricular involvement', 'Not holding another scholarship'],
        'coverage' => ['Full tuition fees', 'Monthly allowance', 'Book & material allowance', 'Career placement opportunities'],
        'website' => ['url' => 'https://www.shell.com.my', 'label' => 'shell.com.my'],
        'email' => null,
        'phone' => '03-2381 1000',
    ],
    [
        'name' => 'Velesto Energy Education Sponsorship',
        'type' => 'corporate',
        'badge' => 'Corporate',
        'desc' => 'Velesto Energy Berhad (formerly UMW Oil & Gas) offers education sponsorship for UTP students pursuing degrees in Petroleum, Mechanical, Electrical, or Chemical Engineering. Includes industry exposure and leadership development.',
        'requirements' => ['Malaysian citizen', 'Pursuing engineering degree at UTP', 'Strong academic performance', 'Active in extracurricular activities'],
        'coverage' => ['Tuition fees', 'Living allowance', 'Industry training & exposure', 'Leadership development'],
        'website' => ['url' => 'https://www.velesto.com', 'label' => 'velesto.com'],
        'email' => null,
        'phone' => '03-2785 5800',
    ],
];

$totalCount = count($scholarships);

// SVG icon helpers
$iconCheck = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>';
$iconDollar = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>';
$iconGlobe = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>';
$iconMail = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>';
$iconPhone = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>';
$iconChevron = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Browse all scholarships, sponsorships, and financial aid available for UTP students. View requirements and contact details.">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <title>Scholarships & Sponsorships — UTP Scholarship Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    <link rel="stylesheet" href="/assets/css/landing.css?v=9">
</head>
<body>
<div class="page-wrapper">

    <!-- Top Utility Bar -->
    <div class="top-bar">
        <div class="top-bar-left">
            <a href="mailto:info@utp.edu.my">
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
        <a href="/landing.php" class="nav-logo">
            <img src="/assets/images/utp_logo.png" alt="UTP Logo" onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
            <span class="nav-logo-text" style="display:none">UTP</span>
        </a>
        <ul class="nav-links" id="navLinks">
            <li><a href="/landing.php">Home</a></li>
            <li><a href="/scholarships.php" class="active-link">Scholarships</a></li>
            <li><a href="/landing.php#programmes">Programmes</a></li>
            <li><a href="/landing.php#stories">Success Stories</a></li>
            <li><a href="/landing.php#why-utp">Why UTP</a></li>
        </ul>
        <div class="nav-actions">
            <a href="/auth/login.php" class="btn btn-outline-navy">Login</a>
            <a href="/auth/signup.php" class="btn btn-gold">Sign Up</a>
            <button class="hamburger" id="mobileNavToggle" aria-label="Toggle Menu">
                <span></span><span></span><span></span>
            </button>
        </div>
    </nav>

    <!-- Page Hero -->
    <section class="sch-hero">
        <div class="sch-breadcrumb">
            <a href="/landing.php">Home</a>
            <span>›</span>
            <span class="current">Scholarships & Sponsorships</span>
        </div>
        <h1>Scholarships & <span>Sponsorships</span></h1>
        <p>Explore all available financial aid, scholarships, and sponsorships for studying at Universiti Teknologi PETRONAS. Click any card to expand or collapse details.</p>
    </section>

    <!-- Filter Tabs -->
    <div class="sch-filters">
        <div class="sch-filter-inner">
            <button class="sch-filter-btn active" data-filter="all" id="filterAll">All (<?= $totalCount ?>)</button>
            <button class="sch-filter-btn" data-filter="internal" id="filterInternal">UTP Internal</button>
            <button class="sch-filter-btn" data-filter="government" id="filterGovernment">Government</button>
            <button class="sch-filter-btn" data-filter="corporate" id="filterCorporate">Corporate / External</button>
        </div>
    </div>

    <!-- Scholarship Listings -->
    <section class="sch-listings">
        <div class="sch-listings-inner">
            <p class="sch-count" id="schCount">Showing <strong><?= $totalCount ?></strong> scholarships & sponsorships</p>

            <?php foreach ($scholarships as $i => $sch): ?>
            <div class="sch-card animate-on-scroll collapsed" data-type="<?= htmlspecialchars($sch['type']) ?>">
                <div class="sch-card-header" onclick="toggleCard(this)">
                    <div class="sch-card-header-left">
                        <h3 class="sch-card-title"><?= htmlspecialchars($sch['name']) ?></h3>
                        <span class="sch-badge sch-badge-<?= htmlspecialchars($sch['type']) ?>"><?= htmlspecialchars($sch['badge']) ?></span>
                    </div>
                    <button class="sch-card-toggle" aria-label="Toggle details"><?= $iconChevron ?></button>
                </div>
                <div class="sch-card-body">
                    <p class="sch-card-desc"><?= htmlspecialchars($sch['desc']) ?></p>
                    <div class="sch-details-grid">
                        <div class="sch-detail-box">
                            <div class="sch-detail-label"><?= $iconCheck ?> Minimum Requirements</div>
                            <ul>
                                <?php foreach ($sch['requirements'] as $req): ?>
                                <li><?= htmlspecialchars($req) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="sch-detail-box">
                            <div class="sch-detail-label"><?= $iconDollar ?> Coverage</div>
                            <ul>
                                <?php foreach ($sch['coverage'] as $cov): ?>
                                <li><?= htmlspecialchars($cov) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="sch-card-contact">
                        <?php if ($sch['website']): ?>
                        <a href="<?= htmlspecialchars($sch['website']['url']) ?>" target="_blank" class="sch-contact-item"><?= $iconGlobe ?> <?= htmlspecialchars($sch['website']['label']) ?></a>
                        <?php endif; ?>
                        <?php if ($sch['email']): ?>
                        <a href="mailto:<?= htmlspecialchars($sch['email']) ?>" class="sch-contact-item"><?= $iconMail ?> <?= htmlspecialchars($sch['email']) ?></a>
                        <?php endif; ?>
                        <?php if ($sch['phone']): ?>
                        <span class="sch-contact-item"><?= $iconPhone ?> <?= htmlspecialchars($sch['phone']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <p class="sch-disclaimer">* Scholarship criteria, deadlines, and availability are subject to change. Please verify requirements directly with the respective organisations. This portal is not affiliated with the external scholarship providers listed above.</p>
        </div>
    </section>

    <!-- CTA: Check Eligibility -->
    <section class="sch-cta">
        <h2>Want to Check Your Eligibility?</h2>
        <p>Sign in to our portal to check which scholarships you qualify for based on your academic profile and personal details.</p>
        <div class="sch-cta-buttons">
            <a href="/auth/login.php" class="btn btn-gold" style="padding:14px 36px;font-size:1rem">Login to Check Eligibility</a>
            <a href="/auth/signup.php" class="btn btn-white-outline" style="padding:14px 36px;font-size:1rem">Create an Account</a>
        </div>
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
                    <li><a href="/landing.php#why-utp">Campus Life</a></li>
                    <li><a href="/landing.php#stories">FAQs</a></li>
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
    /* Toggle card expand/collapse */
    function toggleCard(header) {
        const card = header.closest('.sch-card');
        card.classList.toggle('collapsed');
    }

    /* Expand / Collapse All buttons */
    function expandAll() {
        document.querySelectorAll('.sch-card').forEach(c => c.classList.remove('collapsed'));
    }
    function collapseAll() {
        document.querySelectorAll('.sch-card').forEach(c => c.classList.add('collapsed'));
    }

    /* Mobile Nav */
    const mobileNavToggle = document.getElementById('mobileNavToggle');
    const navLinksList = document.getElementById('navLinks');
    if (mobileNavToggle && navLinksList) {
        mobileNavToggle.addEventListener('click', () => {
            navLinksList.classList.toggle('open');
        });
    }
    document.querySelectorAll('.nav-links a').forEach(a => {
        a.addEventListener('click', () => {
            document.getElementById('navLinks').classList.remove('open');
        });
    });

    /* Filter Tabs */
    const filterBtns = document.querySelectorAll('.sch-filter-btn');
    const schCards = document.querySelectorAll('.sch-card');
    const schCount = document.getElementById('schCount');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filter = btn.dataset.filter;
            let visible = 0;
            schCards.forEach(card => {
                if (filter === 'all' || card.dataset.type === filter) {
                    card.style.display = '';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });
            schCount.innerHTML = 'Showing <strong>' + visible + '</strong> scholarship' + (visible !== 1 ? 's & sponsorships' : '');
        });
    });

    /* Scroll-triggered animations */
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('visible');
            }
        });
    }, { threshold: 0.1 });
    document.querySelectorAll('.animate-on-scroll').forEach(el => {
        observer.observe(el);
    });
</script>

<?php require_once __DIR__ . '/includes/chatbot.php'; ?>
</body>
</html>
