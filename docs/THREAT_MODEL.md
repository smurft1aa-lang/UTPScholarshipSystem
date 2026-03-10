# UTP System Adversarial Threat Model

## 1. Attack Surface Analysis
The UTP Scholarship System exposes a robust surface area typical of application portals:
- **Public Routes:** `/landing.html`, `/login.php`, `/signup.php`, `/forgot-password.php`
- **Authenticated Routes (Students):** `/student/*` (Profile, Document Upload, Form Submission)
- **Privileged Routes (Admins):** `/admin/*` (Dashboard mutations, System Audit Logs)
- **API Vectors:** `/api/*` (JSON payloads, state transits)

## 2. Injection Vectors

### 2.1 SQL Injection (SQLi)
- **Risk Element:** All inputs interacting with MySQL.
- **Controls Installed:** 
  1. Implemented strict `PDO` Prepared Statements system-wide.
  2. Forced `PDO::ATTR_EMULATE_PREPARES = false` within `config/database.php` delegating memory escape exclusively to MySQL.
- **Residual Risk:** Minimal.

### 2.2 Cross-Site Scripting (XSS)
- **Risk Element:** User fields (names), document payloads, admin notes.
- **Controls Installed:**
  1. `InputSanitizer.php` enforces `htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8')` prior to database insertion.
  2. Deeply enforced Content Security Policy (CSP) blocking `unsafe-inline` arrays via `setSecurityHeaders()`.
- **Residual Risk:** Minimal.

## 3. Authentication Vectors

### 3.1 Credential Mismatch & Brute Force
- **Risk Element:** Attempting massive payload spins against `/login.php`.
- **Controls Installed:**
  1. RateLimiter class restricts IPs bridging 5 login failures per 1-minute window locally into `login_attempts` metadata.
  2. `BCRYPT` implementation running native PHP limits set against `cost=12` throttling GPU hashes.

### 3.2 Session Hijacking & CSRF
- **Risk Element:** Adversary triggering application resubmission or password changes.
- **Controls Installed:**
  1. CSRF Token explicitly appended to every single `POST` transaction payload globally verified.
  2. Sessions are tightly locked to `httponly=1`, `samesite=Strict`, `cookie_lifetime=0`, and `use_strict_mode=1`.

## 4. Document Upload Exploitation
- **Risk Element:** Remote Code Execution (RCE) via malicious file drops masquerading as certificates.
- **Controls Installed:**
  1. `finfo_file` low-level MIME sniffing verifies physical byte structures against `.pdf` and `.jpg` arrays.
  2. Complete disregard of client-supplied names — randomly regenerated backend strings based on Unix epochs map physical paths.
  3. Strict `.htaccess` injected directly into `uploads/documents/` halting PHP rendering entirely. Document directories mapped strictly.
- **Residual Risk:** Minimal.

## 5. Business Logic Vectors
- **Risk Element:** Elevating student status or forcing high AI Grade fits.
- **Controls Installed:**
  1. Grade parameters forcefully hardcoded strictly at server level within `GradeMapper.php`.
  2. AI engine runs on strict server memory parsing independent of client JS tampering bounds.
  3. `verifyEmail` and RBAC (`RoleGuard.php`) physically redirect and kill script execution if logic transitions are anomalously flagged. 

---

## Hardening Checklist (COMPLETED)
- [x] BCrypt Cost = 12
- [x] Admin Defaults completely wiped out
- [x] X-Forwarded-IP Trust exclusively bound to local proxy ranges
- [x] Universal CSRF enforced
- [x] File Extensions explicitly decoupled from physical names natively
- [x] Directory Listing Disabled globally via `.htaccess`
- [x] Telemetry silently catching failed `exec` loops remotely via Sentry
