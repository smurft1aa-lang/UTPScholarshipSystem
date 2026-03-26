# UTP Scholarship & Course Eligibility System

A robust, AI-powered scholarship and course eligibility recommendation system built for Universiti Teknologi PETRONAS (UTP).

## Features
- **Student Portal:** Enter academic results (SPM/O-Level/IGCSE) to instantly view eligible programmes.
- **AI Recommendation Engine:** Calculates fit percentage, provides gap analysis, and recommends matching scholarships (with special bonuses for top-performing STEM students).
- **Document Management:** Securely upload and track required application documents (IC, certificates, photos).
- **Admin Dashboard:** Comprehensive overview with stats, application status tracking, and inline quick-review tools.
- **Audit Logging:** Logs all critical administrative and user actions for security and compliance.
- **Secure Authentication:** CSRF protection, rate limiting, secure session management, and password reset flows with email verification.

## Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Required PHP Extensions: `pdo`, `pdo_mysql`, `mbstring`, `fileinfo`
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

## Telemetry & Monitoring (Sentry)
This system utilizes Sentry for native tracking of silent exceptions, slow database queries (>200ms) and AI evaluation bottlenecks (>500ms).
1. Add your DSN to `.env`: `SENTRY_DSN=https://your-dsn@sentry.io/project`
2. Run `composer install` to load the `sentry/sentry` PHP SDK.
3. Ensure `APP_ENV=production` is set to enable breadcrumbs and error capture natively.

## Admin Credentials
After running the setup script, a default super-admin account is generated:
- **Email:** admin@utp.edu.my
- **Password:** Admin123@
> **⚠️ CRITICAL:** You must change this default password immediately after your first login!

## Security Notes
- Ensure your production environment runs via HTTPS (`APP_ENV=production` will force redirects).
- Directory indexing is disabled by default via `.htaccess` to protect source files.
- Upload directories actively block PHP execution to prevent malicious uploads.
- All database queries exclusively use Prepared Statements to prevent SQL Injection.

## API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| `POST` | `/api/check-eligibility.php` | Student | Submit grades and receive programme eligibility results with fit percentages, gap analysis, and scholarship matches |
| `POST` | `/api/submit-application.php` | Student | Submit a scholarship application with programme preferences and uploaded documents |
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

## Project Structure

```
├── admin/           # Admin dashboard, applications, reports, settings
├── api/             # REST API endpoints
├── assets/css/      # Stylesheets (style.css, landing.css, auth.css)
├── assets/js/       # Client-side JavaScript
├── auth/            # Login, signup, password reset, email verification
├── config/          # Database configuration
├── includes/        # Core PHP modules (auth, AI engine, CSRF, etc.)
├── sql/             # Database schema and migrations
├── student/         # Student dashboard, eligibility check, results
├── tests/           # PHPUnit test suite
└── uploads/         # User-uploaded documents
```

## Database Migrations

After initial setup, apply migrations chronologically:
```bash
mysql -u root -p utp_scholarship < sql/migration_001_improvements.sql
```

