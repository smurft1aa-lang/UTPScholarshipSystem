# UTP Scholarship & Course Eligibility System

A robust, AI-powered scholarship and course eligibility recommendation system built for Universiti Teknologi PETRONAS (UTP).

## Features

### Student Portal
- **AI OCR Result Scanning:** Upload a photo of your SPM/O-Level/IGCSE result slip and let Gemini 3.1 Flash-Lite AI automatically extract subjects and grades — no manual typing needed.
- **Smart Subject Classification:** Unrecognised subjects are automatically categorised as "Other Subject" (language) or "Other Non-Language Subject" based on AI keyword analysis.
- **Manual Grade Entry:** Alternatively, enter grades subject-by-subject with real-time dropdown validation.
- **AI Eligibility Engine:** Calculates fit percentage, provides gap analysis, confidence labels, and natural-language recommendations for each programme.
- **STEM Bonus Detection:** Students excelling in Physics and Chemistry receive an automatic 5% fit boost for STEM programmes.
- **AI Assistant Chatbot:** Real-time conversational support for students to ask questions about eligibility, scholarships, and the application process.
- **Sponsorship Proposal Generation:** Automatically generate a professional, printable sponsorship proposal document based on eligibility results.
- **Document Management:** Securely upload IC/Passport scans, passport photos, and academic certificates. OCR-scanned result slips are auto-saved as certificates.
- **Single Submission Policy:** Students may only have one active application at a time. A new submission is allowed only after admin review (approved/rejected).
- **PDF Export:** Download a printable AI Eligibility Report from the results page.

### Admin Dashboard
- **Application Management:** Review, approve, or reject student applications with inline notes and email notifications.
- **Secure Document Access:** Download student-uploaded documents (IC, Photo, Certificate) directly from the application review modal with full audit logging.
- **Document Completeness Badges:** Instantly see which documents each applicant has uploaded, with green download badges and red "Missing" indicators.
- **Reports & Analytics:** Comprehensive statistics, filterable application lists, and paginated data export.
- **Audit Logging:** Every critical action (admin reviews, document downloads, eligibility checks) is permanently recorded.

### Security & Infrastructure
- **CSRF Protection:** Token-based with automatic rotation after each POST request. AJAX endpoints return fresh tokens to prevent desync.
- **Rate Limiting:** Configurable login attempt throttling per IP address.
- **Secure Sessions:** HTTP-only cookies, strict same-site policy, and role-based access guards.
- **Upload Security:** MIME validation, structural image checks, dangerous-extension filtering, and double-extension blocking.
- **Content Security Policy:** Nonce-based CSP headers on all pages.

## Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Required PHP Extensions: `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`, `curl`
- Apache/Nginx with URL rewriting enabled (`mod_rewrite` for Apache)

## Setup Instructions
1. **Clone the repository:**
   ```bash
   git clone https://github.com/smurft1aa-lang/UTPScholarshipSystem.git
   ```

### Option A: Docker Compose Deployment (Recommended)
You can boot the entire system automatically mapping the LAMP stack.
1. Make sure Docker is running.
2. Build and start the container orchestration context:
   ```bash
   make up
   ```
3. Open your browser and navigate to `http://localhost:8080/landing.php` or `http://localhost:8080/auth/login.php`

### Option B: Manual Host Deployment
2. **Configure Environment:**
   Copy `.env.example` to `.env` and fill in your database and application details.
   ```bash
   cp .env.example .env
   ```
3. **Database Setup:**
   Create an empty MySQL database matching your `.env` value.
   Run the setup script in your browser to seed the database schemas and sample data:
   `http://yourdomain.com/setup_db.php`
4. **Permissions:**
   Ensure the `uploads/documents` and `logs` folders have write permissions for the web server.

## Environment Variables

| Variable | Required | Description |
|----------|----------|-------------|
| `DB_HOST` | Yes | Database host (`db` for Docker, `127.0.0.1` for local) |
| `DB_NAME` | Yes | Database name |
| `DB_USER` | Yes | Database username |
| `DB_PASS` | Yes | Database password |
| `GEMINI_API_KEY` | Yes | Google Gemini 3.1 Flash-Lite API key for OCR and Chatbot |
| `ADMIN_EMAIL` | Yes | Admin login email (defaults to `admin@utp.edu.my`) |
| `ADMIN_PASSWORD` | Yes | Admin password — set before first run |
| `APP_ENV` | No | `production` to enable HTTPS redirects and Sentry |
| `SENTRY_DSN` | No | Sentry DSN for error tracking |

## Telemetry & Monitoring (Sentry)
This system utilizes Sentry for native tracking of silent exceptions, slow database queries (>200ms) and AI evaluation bottlenecks (>500ms).
1. Add your DSN to `.env`: `SENTRY_DSN=https://your-dsn@sentry.io/project`
2. Run `composer install` to load the `sentry/sentry` PHP SDK.
3. Ensure `APP_ENV=production` is set to enable breadcrumbs and error capture natively.

## Admin Setup
After running the setup script, a default super-admin account is created.
Credentials are loaded from environment variables:
- `ADMIN_EMAIL` — admin login email (defaults to `admin@utp.edu.my`)
- `ADMIN_PASSWORD` — **must** be set in your `.env` file before first run

> **⚠️ CRITICAL:** Never commit credentials to version control. Copy `.env.example` to `.env` and set a strong password before deploying.

## Security Notes
- Ensure your production environment runs via HTTPS (`APP_ENV=production` will force redirects).
- Directory indexing is disabled by default via `.htaccess` to protect source files.
- Upload directories actively block PHP execution to prevent malicious uploads.
- All database queries exclusively use Prepared Statements to prevent SQL Injection.
- Document uploads enforce IC and Passport Photo prerequisites before eligibility access.

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/check-eligibility.php` | Student | Submit grades and receive programme eligibility results with fit percentages, gap analysis, and scholarship matches |
| `POST` | `/api/ocr-result.php` | Student | Upload a result slip image for AI-powered OCR grade extraction via Gemini 3.1 Flash-Lite |
| `POST` | `/api/submit-application.php` | Student | Submit a scholarship application with programme preferences and uploaded documents |
| `POST` | `/api/chat.php` | Student | AI-powered chatbot assistant for real-time support and queries |
| `GET`  | `/admin/download-document.php` | Admin | Securely download a student's uploaded document (IC, photo, certificate) |
| `POST` | `/api/logout.php` | Any | Terminate the current session and redirect to login |

### Example: Check Eligibility

**Request:**
```json
{
  "csrf_token": "<token>",
  "qual_type": "SPM",
  "subjects": ["Mathematics", "Physics", "Chemistry"],
  "grades": ["A+", "A", "A"]
}
```

**Response:**
```json
{
  "success": true,
  "results": [
    {
      "programme_id": 1,
      "programme_name": "Mechanical Engineering",
      "eligible": true,
      "fit_percentage": 85.0,
      "confidence_label": "Strong Match",
      "recommendation": "...",
      "gaps": []
    }
  ],
  "scholarships": [...]
}
```

### Example: OCR Result Scan

**Request:** `multipart/form-data` with `result_slip` (image/PDF), `qual_type`, and `csrf_token`. (Powered by Gemini 3.1 Flash-Lite)

**Response:**
```json
{
  "success": true,
  "grades": [
    { "subject": "Matematik", "matched_key": "Mathematics", "grade": "A+", "confidence": "high" },
    { "subject": "Fizik", "matched_key": "Physics", "grade": "A", "confidence": "high" },
    { "subject": "Reka Cipta", "matched_key": "Other Non-Language Subject", "grade": "B+", "confidence": "medium" }
  ],
  "raw_text": "...",
  "count": 3,
  "new_csrf_token": "<rotated-token>"
}
```

## Project Structure

```
├── admin/           # Admin dashboard, applications, reports, document downloads
├── api/             # REST API endpoints (eligibility, OCR, applications)
├── assets/css/      # Stylesheets (style.css, landing.css, auth.css, dark-mode.css)
├── assets/js/       # Client-side JavaScript (main.js)
├── auth/            # Login, signup, password reset, email verification
├── config/          # Database configuration
├── includes/        # Core PHP bridge modules (init.php, header, footer)
├── scripts/         # CI helper scripts (coverage gate)
├── sql/             # Database schema, migrations, and indexes
├── src/Services/    # OOP service classes (AIEngine, OcrService, AuditLogger, etc.)
├── student/         # Student dashboard, eligibility check, results, documents
├── tests/           # PHPUnit test suite
└── uploads/         # User-uploaded documents (secured, no PHP execution)
```

## CI Pipeline

The GitHub Actions CI pipeline runs on every push to `main`/`newf` and on pull requests:

| Job | Description |
|-----|-------------|
| **Lint & Static Analysis** | PHP CodeSniffer (PSR-12) + PHPStan Level 5 |
| **Tests & Coverage** | PHPUnit with 80% minimum coverage gate |
| **Security Scan** | Grep-based SQL injection & XSS pattern detection |
| **Docker Build** | Validates production Docker image builds and starts |

## Database Migrations

After initial setup, apply migrations chronologically:
```bash
mysql -u root -p utp_scholarship < sql/migration_001_improvements.sql
```
