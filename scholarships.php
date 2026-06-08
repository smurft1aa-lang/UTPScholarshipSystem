<?php
require_once __DIR__ . '/includes/init.php';

setSecurityHeaders();
initSession();

$csrfToken = generateCSRFToken();

// ─── UTP Financial Aid (Yayasan UTP) ───
$utpFinancialAid = [
    'tazu' => [
        'name' => 'TABUNG AMANAH ZAKAT UTP (TAZU)',
        'subtitle' => 'For UTP Malaysian Muslim Students',
        'desc' => '',
        'points' => [
            'Types of Aids, Criteria & Eligibility',
            'Follow us: Facebook, Instagram',
            'Open for Foundation & Undergraduate students every semester.'
        ],
        'semesters' => ['January semester', 'May semester', 'September semester'],
        'email' => '',
        'phone' => '',
    ],
    'yutp' => [
        'name' => 'YAYASAN UNIVERSITI TEKNOLOGI PETRONAS (YUTP)',
        'subtitle' => null,
        'desc' => '',
        'points' => [
            'Types of Fund, Criteria & Eligibilty',
            'Follow us: Facebook, Instagram',
            'Open for Foundation & Undergraduate active students every semester'
        ],
        'sub_items' => [
            [
                'name' => 'YUTP Scholarship',
                'note' => 'required to apply PESP Application. Shorlisted candidates will be contacted',
            ],
            [
                'name' => 'YUTP Education Grant',
                'semesters' => [
                    ['sem' => 'January semester', 'date' => 'Open 26 January 2026'],
                    ['sem' => 'May semester', 'date' => 'Open 18 May 2026'],
                    ['sem' => 'September semester', 'date' => ''],
                ],
            ],
            [
                'name' => 'YUTP Student Support Fund Bursary',
                'semesters' => [
                    ['sem' => 'January semester', 'date' => null],
                    ['sem' => 'May semester', 'date' => null],
                    ['sem' => 'September semester', 'date' => null],
                ],
            ],
        ],
        'email' => '',
        'phone' => '',
    ],
];

// ─── External Financial Aid ───
$externalAid = [
    'ptptn' => [
        'name' => 'PTPTN',
        'desc' => '',
        'status' => 'Open every semester:',
        'semesters' => [
            [
                'sem' => 'January semester',
                'date' => 'Open: 1 January 2026 - 29 February 2026',
                'guide_url' => '#',
                'guide_label' => 'Slide MyPTPTN _JAN 2026.pdf',
            ],
            [
                'sem' => 'May semester',
                'date' => 'Open: 1 May 2026 - 30 June 2026',
                'guide_url' => '#',
                'guide_label' => 'Slide MyPTPTN_SEM. MAY 2026.pdf',
            ],
            [
                'sem' => 'September semester',
                'date' => 'Open: 1 September 2026 - 31 October 2026',
                'guide_url' => null,
                'guide_label' => null,
            ],
        ],
        'apply_url' => 'https://www.ptptn.gov.my/gateway/',
        'note' => 'Foundation students can apply only in the first semester as the remaining study period should be one (1) year',
        'phone' => '',
    ],
];

// ─── External Scholarships (Undergraduate) ───
$externalScholarships = [
];

// ─── For SPM Leavers ───
$spmLeavers = [

    'YAYASAN UTP FULL SCHOLARSHIP *Closed',
    'PETRONAS EDUCATION SPONSORSHIP PROGRAMME (PESP)',
    'YAYASAN TM - FUTURE LEADERS SCHOLARSHIP',
    'JABATAN PERKHIDMATAN AWAM (JPA) - LSPM',
    'JABATAN PERKHIDMATAN AWAM: PROGRAM KHAS JPA-MARA (PKJM)',
    'YAYASAN DAYA DIRI SCHOLARSHIP',
    'TAZU - YAYASAN TERENGGANU (For Anak Terengganu)',
    'LEMBAGA ZAKAT SELANGOR (For Asnaf Selangor)',
    'MAJLIS AGAMA ISLAM DAN ADAT MELAYU PERAK (For Asnaf Perak)',
    'YAYASAN PENDIDIKAN MAIDAM (For Anak Terengganu)',
    'YAYASAN SABAH (For Anak Sabah)',
    'ANTARA STEEL MILL SCHOLARSHIP (For Anak Sabah & Labuan)',
    'YUTP - PTTEP SCHOLARSHIP (For Sabahan & Sarawakian)',
];

// ─── Yayasan UTP Scholarship Details (expanded for Hero section) ───
// Application windows: status is computed automatically from current date.
// Add/edit open_periods each year to keep this up-to-date.
$yutpScholarships = [
    [
        'name' => 'Yayasan UTP Scholarship',
        'open_periods' => [
            ['start' => '2026-05-15', 'end' => '2026-05-31'],
            ['start' => '2026-09-01', 'end' => '2026-09-30'],
            ['start' => '2027-01-15', 'end' => '2027-01-31'],
        ],
    ],
    [
        'name' => 'Yayasan UTP UEM Scholarship',
        'open_periods' => [
            ['start' => '2026-05-15', 'end' => '2026-05-31'],
            ['start' => '2026-09-01', 'end' => '2026-09-30'],
            ['start' => '2027-01-15', 'end' => '2027-01-31'],
        ],
    ],
    [
        'name' => 'Yayasan UTP Talent Scholarship',
        'open_periods' => [
            ['start' => '2026-05-15', 'end' => '2026-05-31'],
            ['start' => '2026-09-01', 'end' => '2026-09-30'],
            ['start' => '2027-01-15', 'end' => '2027-01-31'],
        ],
    ],
    [
        'name' => 'Yayasan UTP B40 Scholarship',
        'open_periods' => [
            ['start' => '2026-05-15', 'end' => '2026-05-31'],
            ['start' => '2026-09-01', 'end' => '2026-09-30'],
            ['start' => '2027-01-15', 'end' => '2027-01-31'],
        ],
    ],
];

// Compute status for each scholarship based on today's date
$today = date('Y-m-d');
foreach ($yutpScholarships as &$ys) {
    $ys['status'] = 'Closed';
    foreach ($ys['open_periods'] as $period) {
        if ($today >= $period['start'] && $today <= $period['end']) {
            $ys['status'] = 'Open';
            break;
        }
    }
}
unset($ys); // break reference

// ─── Others (External Sponsorships) ───
$otherSponsors = [

    'Yayasan Telekom Malaysia',
    'MIDF Education Scholarship Programme',
    'MCMC Scholarship',
    'Perak State Government',
    'Yayasan Bank Rakyat',
    'Yayasan Terengganu',
    'Yayasan Pahang',
    'Yayasan Sabah',
    'Sarawak Energy Berhad',
    'Sarawak Shell Berhad',
    'Yayasan Tenaga Nasional',
    'Intel Technologies Sdn.Bhd',
    'UWC Scholarship',
    'Gadang Scholarship',
    'Indus Education Fund (IEF)',
    'P Ganendra Scholarship',
    'Shopcoupons',
    'Majlis Amanah Rakyat (MARA) TESP: General Criteria & Apply (Application Guidelines)',
    'Generali Malaysia Volare Scholarship',
];

// ─── Government & Corporate Scholarships (retained from original) ───
$majorScholarships = [
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

// ─── Inquiries Contact ───
$inquiryContact = [
    'name' => 'Tajul Ariffin Bin Shamsuddin',
    'role' => 'Manager, Marketing & Sponsorship',
    'department' => 'Business Planning, Marketing & Performance Department',
    'email' => 'tajul.ariffin@utp.edu.my',
    'phone' => '05-368 8000',
];

// Total count for display
$totalAidCount = count($utpFinancialAid) + count($externalAid) + count($externalScholarships) + count($spmLeavers) + count($otherSponsors) + count($majorScholarships);

// SVG icon helpers
$iconCheck = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>';
$iconDollar = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>';
$iconGlobe = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>';
$iconMail = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/></svg>';
$iconPhone = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>';
$iconChevron = '<svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg>';
$iconCalendar = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
$iconUser = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>';
$iconExternalLink = '<svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/></svg>';
$iconGrad = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M22 10l-10-6L2 10l10 6 10-6z"/><path d="M6 12v5c0 0 3 3 6 3s6-3 6-3v-5"/></svg>';
$iconInfo = '<svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Complete financial aid information for UTP students — scholarships, sponsorships, PTPTN loans, bursaries, zakat assistance, and external sponsorship programmes.">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
    <title>Financial Aid — UTP Scholarship Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
    <link rel="stylesheet" href="/assets/css/landing.css?v=10">
    <style nonce="<?= $GLOBALS['csp_nonce'] ?>">
        /* ── Financial Aid Accordion Sections ── */
        .fa-section {
            max-width: 1200px;
            margin: 0 auto 24px;
        }
        .fa-accordion-header {
            background: linear-gradient(135deg, #005594 0%, #006bb3 100%);
            padding: 18px 28px;
            border-radius: 12px;
            color: #fff;
            font-family: 'Nunito', sans-serif;
            font-size: 1.25rem;
            font-weight: 800;
            cursor: pointer;
            user-select: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .fa-accordion-header:hover {
            background: linear-gradient(135deg, #004a99 0%, #005594 100%);
            box-shadow: 0 6px 24px rgba(0, 74, 153, 0.2);
        }
        .fa-accordion-header .fa-toggle-icon {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            transition: transform 0.3s ease, background 0.3s ease;
        }
        .fa-accordion-header .fa-toggle-icon svg {
            width: 16px;
            height: 16px;
            stroke: #fff;
            fill: none;
            stroke-width: 2;
        }
        .fa-section.open .fa-accordion-header .fa-toggle-icon {
            transform: rotate(180deg);
            background: rgba(255,255,255,0.35);
        }
        .fa-accordion-body {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s cubic-bezier(0.16, 1, 0.3, 1), padding 0.3s ease;
            background: #fff;
            border-radius: 0 0 12px 12px;
            padding: 0 28px;
            border: 1px solid #e8edf2;
            border-top: none;
            margin-top: -8px;
        }
        .fa-section.open .fa-accordion-body {
            max-height: 5000px;
            padding: 28px;
        }

        /* ── Sub-items inside accordion ── */
        .fa-sub-block {
            background: var(--utp-light);
            border-radius: 10px;
            padding: 22px 24px;
            margin-bottom: 20px;
            border-left: 4px solid var(--utp-navy);
        }
        .fa-sub-block:last-child { margin-bottom: 0; }
        .fa-sub-block h4 {
            font-family: 'Nunito', sans-serif;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--utp-dark);
            margin-bottom: 8px;
        }
        .fa-sub-block p {
            font-size: 0.88rem;
            color: var(--utp-muted);
            line-height: 1.65;
            margin-bottom: 10px;
        }
        .fa-sub-block ul {
            list-style: none;
            padding: 0;
            margin: 0 0 8px;
        }
        .fa-sub-block li {
            font-size: 0.85rem;
            color: var(--utp-text);
            padding: 4px 0 4px 18px;
            position: relative;
            line-height: 1.6;
        }
        .fa-sub-block li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--utp-navy);
            font-weight: 700;
        }
        .fa-sub-block li.sub-item {
            padding-left: 36px;
        }
        .fa-sub-block li.sub-item::before {
            content: '○';
            left: 18px;
            color: var(--utp-teal);
        }
        .fa-sub-block a {
            color: var(--utp-navy);
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s;
        }
        .fa-sub-block a:hover {
            color: var(--utp-teal);
            text-decoration: underline;
        }

        /* ── Status badges ── */
        .fa-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .fa-status-open {
            background: rgba(46,125,50,0.1);
            color: #2e7d32;
        }
        .fa-status-closed {
            background: rgba(198,40,40,0.1);
            color: #c62828;
        }
        .fa-status-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            display: inline-block;
        }
        .fa-status-open .fa-status-dot { background: #2e7d32; }
        .fa-status-closed .fa-status-dot { background: #c62828; }

        /* ── Application period info ── */
        .fa-app-period {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.82rem;
            color: var(--utp-muted);
            margin-top: 6px;
        }
        .fa-app-period svg { flex-shrink: 0; color: var(--utp-navy); }

        /* ── SPM Leavers Grid ── */
        .fa-spm-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        @media (max-width: 768px) {
            .fa-spm-grid { grid-template-columns: 1fr; }
        }

        /* ── Others list ── */
        .fa-others-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .fa-others-list li {
            padding: 14px 0;
            border-bottom: 1px solid #eef1f5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .fa-others-list li:last-child { border-bottom: none; }
        .fa-others-list .fa-sponsor-name {
            font-weight: 600;
            color: var(--utp-dark);
            font-size: 0.92rem;
        }
        .fa-others-list .fa-sponsor-link {
            font-size: 0.82rem;
            color: var(--utp-navy);
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s;
        }
        .fa-others-list .fa-sponsor-link:hover {
            color: var(--utp-teal);
        }

        /* ── Inquiry card ── */
        .fa-inquiry-card {
            background: linear-gradient(135deg, var(--utp-light) 0%, #e8f0fe 100%);
            border-radius: 12px;
            padding: 28px;
            display: flex;
            align-items: flex-start;
            gap: 20px;
            border: 1px solid #d4e0f0;
        }
        .fa-inquiry-avatar {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--utp-navy), var(--utp-teal));
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .fa-inquiry-info h4 {
            font-family: 'Nunito', sans-serif;
            font-size: 1.05rem;
            font-weight: 800;
            color: var(--utp-dark);
            margin-bottom: 4px;
        }
        .fa-inquiry-info p {
            font-size: 0.82rem;
            color: var(--utp-muted);
            margin-bottom: 12px;
            line-height: 1.5;
        }
        .fa-inquiry-info .fa-contact-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: var(--utp-text);
            margin-bottom: 6px;
        }
        .fa-inquiry-info .fa-contact-row svg { flex-shrink: 0; color: var(--utp-navy); }
        .fa-inquiry-info a { color: var(--utp-navy); font-weight: 600; transition: color 0.2s; text-decoration: none; }
        .fa-inquiry-info a:hover { color: var(--utp-teal); }

        /* ── Section divider ── */
        .fa-divider {
            max-width: 1200px;
            margin: 40px auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .fa-divider::before,
        .fa-divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #dce3ec;
        }
        .fa-divider span {
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 1.1rem;
            color: var(--utp-dark);
            white-space: nowrap;
        }

        /* ── Postgraduate block ── */
        .fa-postgrad-block {
            background: linear-gradient(135deg, #f0f4ff 0%, var(--utp-light) 100%);
            border-radius: 12px;
            padding: 28px;
            border: 1px solid #d4e0f0;
        }
        .fa-postgrad-block h4 {
            font-family: 'Nunito', sans-serif;
            font-weight: 800;
            font-size: 1.05rem;
            color: var(--utp-dark);
            margin-bottom: 10px;
        }
        .fa-postgrad-block p {
            font-size: 0.88rem;
            color: var(--utp-muted);
            line-height: 1.65;
        }
        .fa-postgrad-block ul {
            list-style: none;
            padding: 0;
            margin: 12px 0 0;
        }
        .fa-postgrad-block li {
            font-size: 0.85rem;
            color: var(--utp-text);
            padding: 4px 0 4px 18px;
            position: relative;
            line-height: 1.6;
        }
        .fa-postgrad-block li::before {
            content: '•';
            position: absolute;
            left: 0;
            color: var(--utp-navy);
            font-weight: 700;
        }

        /* ── Updated As Of ── */
        .fa-updated {
            font-size: 0.82rem;
            color: var(--utp-muted);
            font-style: italic;
            text-align: center;
            margin-bottom: 32px;
        }

        /* ── YUTP Scholarship Mini Cards ── */
        .fa-mini-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 20px;
        }
        .fa-mini-card {
            background: var(--utp-light);
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
            transition: all 0.25s ease;
        }
        .fa-mini-card:hover {
            border-color: var(--utp-navy);
            box-shadow: 0 4px 16px rgba(0,74,153,0.08);
            transform: translateY(-2px);
        }
        .fa-mini-card h5 {
            font-family: 'Nunito', sans-serif;
            font-weight: 700;
            font-size: 0.88rem;
            color: var(--utp-dark);
            margin-bottom: 6px;
        }
    </style>
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
            <li><a href="/scholarships.php" class="active-link">Financial Aid</a></li>
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
            <a href="/landing.php">Students</a>
            <span>›</span>
            <span class="current">Financial Aid</span>
        </div>
        <h1>Financial <span>Aid</span></h1>
        <p style="margin-bottom: 18px;">Sponsorship / Loan</p>
        <p>The university provides guidance and support to students in applying for financial aid, sponsorships, and educational loans throughout their studies. Assistance is available for opportunities offered by the government agencies, state foundations, corporate organisations and the university itself.</p>
    </section>

    <!-- Open for Application Banner -->
    <div style="background: #fff; padding: 32px 48px; text-align: center; border-bottom: 1px solid #e8edf2;">
        <div style="max-width: 1200px; margin: 0 auto;">
            <h2 style="font-family: 'Nunito', sans-serif; font-size: 1.6rem; font-weight: 900; color: var(--utp-dark); margin-bottom: 4px;">Open for Application</h2>
            <p class="fa-updated">*Updated as of <?= date('j F Y') ?></p>

            <!-- YUTP Scholarship Mini Cards -->
            <div class="fa-mini-cards">
                <?php foreach ($yutpScholarships as $ys): ?>
                <div class="fa-mini-card">
                    <h5><?= htmlspecialchars($ys['name']) ?></h5>
                    <span class="fa-status fa-status-<?= $ys['status'] === 'Open' ? 'open' : 'closed' ?>">
                        <span class="fa-status-dot"></span>
                        <?= htmlspecialchars($ys['status']) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <p style="font-size: 0.82rem; color: var(--utp-muted);">Application Period: 15 May 2026 – 31 May 2026 &nbsp;|&nbsp; <a href="https://utpdec.microsoftcrmportals.com/admission/" target="_blank" style="color: var(--utp-navy); font-weight: 600;">Apply to UTP →</a></p>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="sch-filters">
        <div class="sch-filter-inner">
            <button class="sch-filter-btn active" data-filter="all" id="filterAll">All Financial Aid</button>
            <button class="sch-filter-btn" data-filter="utp" id="filterUTP">UTP Internal</button>
            <button class="sch-filter-btn" data-filter="external" id="filterExternal">External & PTPTN</button>
            <button class="sch-filter-btn" data-filter="spm" id="filterSPM">SPM Leavers</button>
            <button class="sch-filter-btn" data-filter="corporate" id="filterCorporate">Corporate & Government</button>
        </div>
    </div>

    <!-- Financial Aid Listings -->
    <section class="sch-listings">
        <div class="sch-listings-inner">

            <!-- ═══ SECTION 1: UTP Financial Aid ═══ -->
            <div class="fa-section open animate-on-scroll" data-category="utp" id="secUTPAid">
                <div class="fa-accordion-header">
                    <span><?= $iconGrad ?> &nbsp;UTP Financial Aid</span>
                    <span class="fa-toggle-icon"><svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></span>
                </div>
                <div class="fa-accordion-body">
                    <!-- TAZU -->
                    <div class="fa-sub-block">
                        <h4>TABUNG AMANAH ZAKAT UTP (TAZU)</h4>
                        <p style="font-weight: 600; color: var(--utp-dark); margin-bottom: 6px;">For UTP Malaysian Muslim Students</p>
                        <ul>
                            <?php foreach ($utpFinancialAid['tazu']['points'] as $pt): ?>
                            <li><?= $pt ?></li>
                            <?php endforeach; ?>
                            <?php foreach ($utpFinancialAid['tazu']['semesters'] as $sem): ?>
                            <li class="sub-item"><?= htmlspecialchars($sem) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- YUTP -->
                    <div class="fa-sub-block">
                        <h4>YAYASAN UNIVERSITI TEKNOLOGI PETRONAS (YUTP)</h4>
                        <ul>
                            <?php foreach ($utpFinancialAid['yutp']['points'] as $pt): ?>
                            <li><?= $pt ?></li>
                            <?php endforeach; ?>
                        </ul>

                        <?php foreach ($utpFinancialAid['yutp']['sub_items'] as $sub): ?>
                        <div style="margin-top: 16px; padding-left: 12px; border-left: 2px solid var(--utp-teal); padding-top: 8px; padding-bottom: 4px;">
                            <p style="font-weight: 700; color: var(--utp-dark); margin-bottom: 4px; font-size: 0.9rem;"><?= htmlspecialchars($sub['name']) ?></p>
                            <?php if (isset($sub['note'])): ?>
                            <p style="font-size: 0.82rem; color: var(--utp-muted);"><?= $sub['note'] ?></p>
                            <?php endif; ?>
                            <?php if (isset($sub['semesters'])): ?>
                            <ul>
                                <?php foreach ($sub['semesters'] as $sem): ?>
                                <li class="sub-item"><?= htmlspecialchars($sem['sem']) ?><?= $sem['date'] ? ' — ' . htmlspecialchars($sem['date']) : '' ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- ═══ SECTION 2: External Financial Aid (PTPTN) ═══ -->
            <div class="fa-section animate-on-scroll" data-category="external" id="secExternal">
                <div class="fa-accordion-header">
                    <span><?= $iconDollar ?> &nbsp;External Financial Aid</span>
                    <span class="fa-toggle-icon"><svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></span>
                </div>
                <div class="fa-accordion-body">
                    <?php $pt = $externalAid['ptptn']; ?>
                    <div class="fa-sub-block">
                        <h4><?= htmlspecialchars($pt['name']) ?></h4>
                        <p><?= htmlspecialchars($pt['desc']) ?></p>
                        <p style="margin-bottom: 12px;">
                            <span class="fa-status fa-status-open"><span class="fa-status-dot"></span> <?= htmlspecialchars($pt['status']) ?></span>
                        </p>

                        <?php foreach ($pt['semesters'] as $sem): ?>
                        <div style="margin-bottom: 10px; padding: 10px 14px; background: #fff; border-radius: 8px; border: 1px solid #eef1f5;">
                            <div style="font-weight: 600; color: var(--utp-dark); font-size: 0.88rem; margin-bottom: 2px;"><?= htmlspecialchars($sem['sem']) ?></div>
                            <div style="font-size: 0.82rem; color: var(--utp-muted);"><?= htmlspecialchars($sem['date']) ?></div>
                            <?php if ($sem['guide_url']): ?>
                            <a href="<?= htmlspecialchars($sem['guide_url']) ?>" target="_blank" style="font-size: 0.8rem; color: var(--utp-navy); font-weight: 600; display: inline-flex; align-items: center; gap: 4px; margin-top: 4px;"><?= $iconExternalLink ?> <?= htmlspecialchars($sem['guide_label']) ?></a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>

                        <p style="font-size: 0.82rem; color: #c62828; font-style: italic; margin-top: 10px;">* <?= htmlspecialchars($pt['note']) ?></p>
                        <div style="margin-top: 10px;">
                            <a href="<?= htmlspecialchars($pt['apply_url']) ?>" target="_blank" class="btn btn-navy" style="padding: 10px 24px; font-size: 0.85rem;">Apply for PTPTN Loan →</a>
                        </div>
                    </div>
                </div>
            </div>



            
            <!-- ═══ SECTION 3: For SPM Leavers ═══ -->
            <div class="fa-section animate-on-scroll" data-category="spm" id="secSPM">
                <div class="fa-accordion-header">
                    <span><?= $iconGrad ?> &nbsp;For SPM Leavers</span>
                    <span class="fa-toggle-icon"><svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></span>
                </div>
                <div class="fa-accordion-body">
                    <ul class="fa-others-list">
                        <?php foreach ($spmLeavers as $spm): ?>
                        <li>
                            <span class="fa-sponsor-name"><?= htmlspecialchars($spm) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            
            <!-- ═══ SECTION 4: Others ═══ -->
            <div class="fa-section animate-on-scroll" data-category="external" id="secOthers">
                <div class="fa-accordion-header">
                    <span><?= $iconGlobe ?> &nbsp;Others</span>
                    <span class="fa-toggle-icon"><svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></span>
                </div>
                <div class="fa-accordion-body">
                    <ul class="fa-others-list">
                        <?php foreach ($otherSponsors as $os): ?>
                        <li>
                            <span class="fa-sponsor-name"><?= htmlspecialchars($os) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- ═══ SECTION 6: Inquiries ═══ -->
            <div class="fa-section animate-on-scroll" data-category="all" id="secInquiries">
                <div class="fa-accordion-header">
                    <span><?= $iconInfo ?> &nbsp;Inquiries</span>
                    <span class="fa-toggle-icon"><svg viewBox="0 0 24 24"><path d="M6 9l6 6 6-6"/></svg></span>
                </div>
                <div class="fa-accordion-body">
                    <div class="fa-inquiry-card">
                        <div class="fa-inquiry-avatar"><?= $iconUser ?></div>
                        <div class="fa-inquiry-info">
                            <h4><?= htmlspecialchars($inquiryContact['name']) ?></h4>
                            <p><?= htmlspecialchars($inquiryContact['role']) ?><br><?= htmlspecialchars($inquiryContact['department']) ?></p>
                            <div class="fa-contact-row"><?= $iconMail ?> <a href="mailto:<?= htmlspecialchars($inquiryContact['email']) ?>"><?= htmlspecialchars($inquiryContact['email']) ?></a></div>
                            <div class="fa-contact-row"><?= $iconPhone ?> <?= htmlspecialchars($inquiryContact['phone']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══ Divider ═══ -->
            <div class="fa-divider">
                <span>Government & Corporate Scholarships</span>
            </div>

            <!-- ═══ Major Scholarships (JPA, MARA, Corporate) ═══ -->
            <?php foreach ($majorScholarships as $i => $sch): ?>
            <div class="sch-card animate-on-scroll collapsed" data-type="<?= htmlspecialchars($sch['type']) ?>" data-category="corporate">
                <div class="sch-card-header">
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
                    <li><a href="/scholarships.php">Financial Aid</a></li>
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
    /* Toggle accordion section */
    document.querySelectorAll('.fa-accordion-header').forEach(header => {
        header.addEventListener('click', () => {
            const section = header.closest('.fa-section');
            section.classList.toggle('open');
        });
    });

    /* Toggle card expand/collapse */
    document.querySelectorAll('.sch-card-header').forEach(header => {
        header.addEventListener('click', () => {
            const card = header.closest('.sch-card');
            card.classList.toggle('collapsed');
        });
    });

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
    const faSections = document.querySelectorAll('.fa-section');
    const schCards = document.querySelectorAll('.sch-card');
    const faDivider = document.querySelector('.fa-divider');

    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            const filter = btn.dataset.filter;

            // Show/hide accordion sections
            faSections.forEach(sec => {
                const cat = sec.dataset.category;
                if (filter === 'all' || cat === filter || cat === 'all') {
                    sec.style.display = '';
                } else {
                    sec.style.display = 'none';
                }
            });

            // Show/hide scholarship cards
            schCards.forEach(card => {
                const cat = card.dataset.category;
                if (filter === 'all' || filter === 'corporate' || cat === filter) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // Show/hide divider
            if (faDivider) {
                faDivider.style.display = (filter === 'all' || filter === 'corporate') ? '' : 'none';
            }
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
