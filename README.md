# UTP Scholarship & Course Eligibility System

## User Manual & System Documentation

---

### Table of Contents
1. [System Overview](#system-overview)
2. [System Requirements](#system-requirements)
3. [Installation Guide](#installation-guide)
4. [Default Accounts](#default-accounts)
5. [Student Guide](#student-guide)
6. [Admin Guide](#admin-guide)
7. [Entry Requirements](#entry-requirements)
8. [Sponsoring Agencies](#sponsoring-agencies)
9. [Fee Structure](#fee-structure)
10. [AI Recommendation Engine](#ai-recommendation-engine)
11. [Security Features](#security-features)
12. [Troubleshooting](#troubleshooting)

---

## System Overview

The UTP Scholarship & Course Eligibility System is a web application that helps high school graduates check their eligibility for **Universiti Teknologi PETRONAS (UTP)** foundation programmes and discover matching scholarships. Students enter their exam results (SPM, O-Level, or IGCSE), and the system's AI-driven engine evaluates them against official UTP entry requirements, providing ranked recommendations with fit percentages.

**Two user roles:**
- **Student** (Orange theme) — Sign up, enter grades, check eligibility, view results and proposals
- **Admin** (Purple theme) — Manage applications, programmes, scholarships, and generate reports

---

## System Requirements

| Requirement | Details |
|---|---|
| Web Server | Apache (XAMPP recommended) |
| PHP | 7.4 or later |
| MySQL | 5.7 or later |
| MySQL Port | 3308 (configured to avoid conflicts) |
| Browser | Chrome, Firefox, Edge, or Safari (latest version) |
| OS | Windows 10/11 (XAMPP), Linux, or macOS |

---

## Installation Guide

### Step 1: Install XAMPP
Download and install [XAMPP](https://www.apachefriends.org/) if not already installed.

### Step 2: Configure MySQL Port
The system uses **port 3308** to avoid conflicting with other MySQL instances. The `my.ini` file in `C:\xampp\mysql\bin\my.ini` should have:
```
port=3308
```

### Step 3: Copy Project Files
Copy the entire project folder to a location on your drive (e.g., `E:\UTP Scholarship system\`).

### Step 4: Configure Apache DocumentRoot
Open XAMPP Control Panel, click **Config** next to Apache > **httpd.conf**. Update:
```apache
DocumentRoot "E:/UTP Scholarship system"
<Directory "E:/UTP Scholarship system">
    Options Indexes FollowSymLinks Includes ExecCGI
    AllowOverride All
    Require all granted
</Directory>
```

### Step 5: Start Services
Open XAMPP Control Panel and start:
- **Apache** (web server)
- **MySQL** (database)

### Step 6: Set Up Database
Open a terminal and run:
```bash
C:\xampp\php\php.exe "E:\UTP Scholarship system\setup_db.php"
```

This command:
- Creates the `utp_scholarship` database
- Creates all required tables
- Seeds 14 foundation programmes with real UTP fees
- Seeds 25 official sponsoring agencies
- Seeds complete entry requirements for SPM, O-Level, and IGCSE
- Seeds the fee structure table
- Creates the default admin account

### Step 7: Starting the Server (Nodemon / Dev Mode)
If you experience Apache crashes after heavy usage, you can run the built-in PHP server wrapped with Nodemon. This will automatically restart the server if it stops or if you edit code.

1. Open a terminal in the project folder
2. Run:
```bash
npm install
npm run dev
```
3. Access the system at:
```
http://localhost:8000/
```

Alternatively, you can access via XAMPP Apache at `http://localhost/`.

---

## Default Accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@utp.edu.my | Admin@1234 |
| Student | _Register via sign-up form_ | — |

> **Important:** Change the default admin password after first login via **Admin > Settings**.

---

## Student Guide

### Registration
1. Click **Sign Up** on the landing page
2. Fill in your details: Full Name, Email, Phone, IC Number
3. Create a strong password (min 8 characters, uppercase, lowercase, number, special character)
4. Click **Create Account**

### Checking Eligibility
1. From the **Dashboard**, click **Check Now** or go to **Check Eligibility**
2. **Step 1 — Qualification:** Select your exam type:
   - **SPM** — Sijil Pelajaran Malaysia
   - **O-Level** — GCE Ordinary Level
   - **IGCSE** — International GCSE
3. **Step 2 — Grades:** Select the grade you achieved for each subject
4. Click **Check My Eligibility** — the AI engine processes your results

### Viewing Results
The results page shows:
- **Eligible Programmes** — Ranked by fit percentage (highest first)
- **Fit Percentage** — How well your grades match (colour-coded: green = high, yellow = medium, red = low)
- **Fee Information** — Foundation fee and undergraduate tuition fee per programme
- **AI Recommendations** — Natural-language advice for each programme
- **Not Eligible** — Programmes you don't qualify for (collapsed section with improvement suggestions)
- **Matching Scholarships** — Scholarships and sponsorships you may qualify for

### Viewing Proposal
- Click **View Proposal** from the results page
- The proposal is a printable, auto-generated document containing:
  - Your student information
  - Academic results
  - Eligible programmes with fees
  - Complete fee structure
- Click **Print / Save PDF** to download

---

## Admin Guide

### Dashboard
The admin dashboard shows:
- **Stat Cards** — Total programmes, applications, pending approvals, active scholarships
- **Application Status** — Visual bar chart of submitted/processing/approved/rejected
- **Calendar** — Current month view
- **Recent Applications** — Latest 5 student applications

### Managing Applications
1. Go to **Applications**
2. Filter by status (Submitted, Processing, Approved, Rejected) or search by student name/email
3. Click **Review** on any application to:
   - View student details and qualification
   - Add admin notes
   - Update status: **Processing** → **Approve** → **Reject**

**Application Workflow:**
```
Submitted → Processing → Approved
                      → Rejected
```

### Managing Programmes
1. Go to **Programmes**
2. View all programmes with their requirement count
3. Click **Edit** to:
   - Update programme name, category, description
   - Toggle active/inactive status
   - Add/remove entry requirements (subject, minimum grade, weight per qualification type)
4. Click **Add Programme** to create a new programme

### Managing Scholarships
1. Go to **Scholarships**
2. View all scholarships with budget ranges and linked programmes
3. Click **Edit** to update scholarship details:
   - Name, description, budget range
   - Minimum fit percentage (students below this threshold won't see it)
   - Start/end dates
   - Linked programmes
4. Click **Add Scholarship** to create a new one

### Generating Reports
1. Go to **Reports**
2. Set the date range (defaults to current year)
3. Click **Generate** to view:
   - **Application Statistics** — Total, approved, pending, rejected with rates
   - **Monthly Trend** — Applications per month with visual bars
   - **Programme Popularity** — Which programmes get the most applications
   - **Scholarship Distribution** — How many students qualify per scholarship
   - **Grade Trends** — Most common grades per subject per qualification type
4. Click **Print Report** for a print-friendly version

### Settings
- **Change Password** — Update your admin password
- **Add Admin** — Create additional admin accounts
- **System Information** — View registered students, total applications, PHP version, MySQL port

---

## Entry Requirements

All foundation programmes require a **minimum Grade C** in the listed subjects.

### SPM Requirements

| Programme | Required Subjects |
|---|---|
| **Integrated Engineering** | Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry |
| **Chemical Engineering** | Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry |
| **Mechanical Engineering** | Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry |
| **Petroleum Engineering** | Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry |
| **Applied Physics** | Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry |
| **Civil Engineering** | Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry |
| **Computer Engineering** | Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry |
| **Electrical & Electronics Engineering** | Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry |
| **Applied Chemistry** | Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry |
| **Geoscience** | Bahasa Melayu, English, Mathematics, Additional Mathematics, Physics, Chemistry |
| **Information System** | Bahasa Melayu, English, Mathematics, Other Non-Language Subject, Other Non-Language Subject II |
| **Information Technology** | Bahasa Melayu, English, Mathematics, Other Non-Language Subject, Other Non-Language Subject II |
| **Computer Science** | Bahasa Melayu, English, Mathematics, Additional Mathematics, Other Subject I |
| **Business Management** | Bahasa Melayu, English, Mathematics, Other Subject I, Other Subject II |

### O-Level / IGCSE Requirements

| Programme | Required Subjects |
|---|---|
| **Engineering programmes** (Integrated, Chemical, Mechanical, Petroleum, Applied Physics, Civil, Computer, EE, Applied Chemistry, Geoscience) | Mathematics, Physics, Chemistry, Additional Mathematics, Other Subject I |
| **Information System / Information Technology** | Mathematics, Other Non-Language Subject, Other Non-Language Subject II, Other Subject I, Other Subject II |
| **Computer Science** | Mathematics, Additional Mathematics, Other Non-Language Subject I, Other Non-Language Subject II, Other Subject I |
| **Business Management** | Mathematics, Other Subject I–IV, Other Non-Language Subject III–IV |

---

## Sponsoring Agencies

The following are the official UTP sponsoring agencies, all pre-loaded in the system:

| # | Sponsoring Agency | Type |
|---|---|---|
| 1 | PETRONAS Scholarship Loan Fund | Loan |
| 2 | Tabung Amanah Zakat UTP (TAZU) | Financial Aid |
| 3 | Yayasan Universiti Teknologi PETRONAS (YUTP) | Scholarship |
| 4 | Perbadanan Tabung Pendidikan Tinggi Nasional (PTPTN) | Loan |
| 5 | Jabatan Perkhidmatan Awam (JPA) | Scholarship |
| 6 | Majlis Amanah Rakyat (MARA) | Sponsorship |
| 7 | Yayasan Peneraju Pendidikan Bumiputera | Scholarship |
| 8 | Malaysia Rubber Export Promotion Council | Sponsorship |
| 9 | Lembaga Zakat Selangor | Financial Aid |
| 10 | Biasiswa Kerajaan Negeri Sabah | Scholarship |
| 11 | Yayasan Sime Darby | Scholarship |
| 12 | Yayasan Gamuda | Scholarship |
| 13 | Technip Geoproduction (M) Sdn Bhd | Sponsorship |
| 14 | Permodalan Nasional Berhad (PNB) | Scholarship |
| 15 | Sarawak Energy Berhad | Sponsorship |
| 16 | Penang Future Foundation | Scholarship |
| 17 | YTL Foundation | Scholarship |
| 18 | Velesto Energy Berhad | Sponsorship |
| 19 | Baker Hughes | Sponsorship |
| 20 | Yayasan UTP - Yayasan Tuanku Abdul Rahman Joint Scholarship | Scholarship |
| 21 | Sapura Energy Sdn Bhd | Sponsorship |
| 22 | Technip Energies Sdn Bhd | Sponsorship |
| 23 | Halliburton Energy Sdn Bhd | Sponsorship |
| 24 | Schlumberger WTA (M) Sdn Bhd | Sponsorship |
| 25 | Murata Electronics (Malaysia) Sdn Bhd | Sponsorship |

> Together with more than 100 sponsoring agencies. Additional sponsors can be added via **Admin > Scholarships**.

---

## Fee Structure

Fees are effective **May 2026** and are subject to change. All fees are in Malaysian Ringgit (RM).

### Academic Fees

| Programme | Duration | Foundation Fee | Undergraduate Fee |
|---|---|---|---|
| All Foundation Programmes | 1 Year | RM 21,000 | — |
| Integrated Engineering | 4 Years | — | RM 160,000 |
| Chemical / EE / Mechanical / Petroleum Engineering | 4 Years | — | RM 110,000 |
| Civil / Computer Engineering | 4 Years | — | RM 104,500 |
| Geoscience | 4 Years | — | RM 95,200 |
| Applied Physics / Applied Chemistry / CS / IT / IS | 3 Years 4 Months | — | RM 82,500 |
| Business Management | 3 Years 4 Months | — | RM 73,500 |

### Other Fees

| Fee Type | Amount | Frequency |
|---|---|---|
| Registration Fee | RM 1,300 | One-time |
| Hostel Fee | RM 280 – RM 1,000 | Monthly (subject to room type) |

> **Notes:**
> - Fees are correct at the time of publication and apply to the current intake year.
> - The management of Universiti Teknologi PETRONAS (UTP) reserves the right to revise fees without prior notice.

---

## AI Recommendation Engine

The system uses a weighted scoring algorithm to evaluate student eligibility:

1. **Grade-to-Points Conversion**
   - SPM: A+ = 10, A = 9, A- = 8, B+ = 7, B = 6, B- = 5, C+ = 4, C = 3, D = 2, E = 1, G = 0
   - O-Level/IGCSE: A* = 10, A = 9, B = 7, C = 5, D = 3, E = 2, F = 1, G/U = 0

2. **Weighted Scoring** — Core subjects (Mathematics, Physics, Chemistry, Additional Mathematics) carry weight 1.0; supporting subjects (Bahasa Melayu, English) carry weight 0.8–0.9

3. **Fit Percentage** — Calculated as `(total weighted score / max possible score) × 100`

4. **Gap Analysis** — Identifies specific subjects where improvement is needed

5. **Natural Language Recommendations** — Generated based on fit percentage:
   - ≥80%: "Excellent match"
   - 60–79%: "Good match"
   - <60% but eligible: "Meets minimum requirements"
   - Not eligible: Specific improvement suggestions

6. **Scholarship Matching** — Based on fit percentage thresholds set per scholarship

---

## Security Features

| Feature | Details |
|---|---|
| Password Hashing | bcrypt with cost factor 12 |
| CSRF Protection | Token-based on every form |
| SQL Injection | PDO prepared statements |
| XSS Prevention | `htmlspecialchars()` on all output |
| Rate Limiting | 5 login attempts per minute per IP |
| Session Security | HttpOnly cookies, strict mode, regeneration on login |
| Access Control | Role-based route protection (student/admin) |
| Security Headers | X-Frame-Options, X-Content-Type-Options, X-XSS-Protection, Referrer-Policy |

---

## Troubleshooting

| Issue | Solution |
|---|---|
| "Connection refused" on port 3308 | Ensure MySQL is running in XAMPP and `my.ini` has `port=3308` |
| Blank page / 500 error | Check Apache error log: `C:\xampp\apache\logs\error.log` |
| "Access denied for user 'root'" | Ensure MySQL root has no password, or update `config/database.php` |
| CSS not loading | Ensure Apache DocumentRoot points to the project folder |
| "Table doesn't exist" | Run `setup_db.php` again to create the database |
| Login not working | Clear browser cookies, ensure MySQL is running |

---

## Project Structure

```
UTP Scholarship system/
├── config/database.php           # Database connection (PDO, port 3308)
├── sql/setup.sql                 # Schema, seed data, entry requirements
├── setup_db.php                  # Database initializer script
├── README.md                     # This file
├── includes/
│   ├── auth.php                  # Authentication & session management
│   ├── security.php              # CSRF, rate limiting, input validation
│   ├── ai_engine.php             # AI recommendation engine
│   ├── header.php                # Student layout (navbar)
│   └── footer.php                # Student layout (closing tags)
├── assets/
│   ├── css/style.css             # Complete design system
│   └── js/main.js                # Client-side validation & interactions
├── api/
│   ├── check-eligibility.php     # Eligibility check API
│   └── logout.php                # Logout handler
├── index.php                     # Landing page
├── login.php                     # Student/Admin login
├── signup.php                    # Student registration
├── student/
│   ├── dashboard.php             # Student overview
│   ├── check-eligibility.php     # Grade input form
│   ├── results.php               # AI results with fees & scholarships
│   └── my-proposal.php           # Auto-generated proposal document
└── admin/
    ├── admin_header.php          # Admin sidebar layout
    ├── admin_footer.php          # Admin closing tags
    ├── dashboard.php             # Admin overview with stats & calendar
    ├── applications.php          # Application management & workflow
    ├── programmes.php            # Programme & requirements CRUD
    ├── scholarships.php          # Scholarship CRUD
    ├── reports.php               # Structured performance reports
    └── settings.php              # Password, admin accounts, system info
```

---

## License

This system is developed for educational purposes for Universiti Teknologi PETRONAS.

**Contact:** UTP, 32610 Seri Iskandar, Perak Darul Ridzuan, Malaysia
**Tel:** 1-300-22-8887
**Web:** [www.utp.edu.my](https://www.utp.edu.my)
