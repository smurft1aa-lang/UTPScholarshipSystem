<?php

/**
 * PHPUnit Bootstrap — Self-contained SQLite test environment
 */

define('APP_ENV', 'testing');
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
// Override getDB() to use SQLite in-memory for all tests
function getDB(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
// Create all required tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            full_name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            ic_number TEXT NOT NULL UNIQUE,
            phone TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT 'student',
            email_verified INTEGER DEFAULT 0,
            totp_secret TEXT DEFAULT NULL,
            totp_enabled INTEGER DEFAULT 0,
            created_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS qualifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            qual_type TEXT NOT NULL,
            created_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS grades (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            qualification_id INTEGER NOT NULL,
            subject TEXT NOT NULL,
            grade TEXT NOT NULL
        );

        CREATE TABLE IF NOT EXISTS programmes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            category TEXT NOT NULL,
            description TEXT,
            duration TEXT,
            foundation_fee REAL DEFAULT 0,
            undergraduate_fee REAL DEFAULT 0,
            stem_bonus INTEGER DEFAULT 0,
            is_active INTEGER DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS entry_requirements (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            programme_id INTEGER NOT NULL,
            qual_type TEXT NOT NULL,
            subject TEXT NOT NULL,
            min_grade TEXT NOT NULL,
            weight REAL DEFAULT 1.00
        );

        CREATE TABLE IF NOT EXISTS scholarships (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            description TEXT,
            type TEXT DEFAULT 'scholarship',
            budget_min REAL DEFAULT 0,
            budget_max REAL DEFAULT 0,
            min_fit_percentage INTEGER DEFAULT 50,
            start_date TEXT,
            end_date TEXT,
            is_active INTEGER DEFAULT 1
        );

        CREATE TABLE IF NOT EXISTS scholarship_programme (
            scholarship_id INTEGER NOT NULL,
            programme_id INTEGER NOT NULL,
            PRIMARY KEY (scholarship_id, programme_id)
        );

        CREATE TABLE IF NOT EXISTS applications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            qualification_id INTEGER NOT NULL,
            programme_id_1 INTEGER,
            programme_id_2 INTEGER,
            programme_id_3 INTEGER,
            scholarship_id INTEGER,
            status TEXT NOT NULL DEFAULT 'submitted',
            admin_notes TEXT,
            reviewed_by INTEGER,
            created_at TEXT DEFAULT (datetime('now')),
            updated_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS eligibility_results (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            application_id INTEGER NOT NULL,
            programme_id INTEGER NOT NULL,
            eligible INTEGER NOT NULL DEFAULT 0,
            fit_percentage REAL DEFAULT 0,
            recommendation_text TEXT
        );

        CREATE TABLE IF NOT EXISTS login_attempts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            ip_address TEXT NOT NULL,
            attempted_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS email_verifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token TEXT NOT NULL UNIQUE,
            expires_at TEXT NOT NULL,
            created_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS password_resets (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            token TEXT NOT NULL UNIQUE,
            expires_at TEXT NOT NULL,
            created_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS audit_log (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER,
            action TEXT NOT NULL,
            target_type TEXT,
            target_id INTEGER,
            details TEXT,
            ip_address TEXT,
            created_at TEXT DEFAULT (datetime('now'))
        );

        CREATE TABLE IF NOT EXISTS documents (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            doc_type TEXT NOT NULL,
            filename TEXT NOT NULL,
            original_name TEXT NOT NULL,
            file_size INTEGER NOT NULL,
            uploaded_at TEXT DEFAULT (datetime('now'))
        );
    ");
// Seed: Admin user (password: Admin@1234)
    $adminHash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 10]);
    $pdo->exec("INSERT OR IGNORE INTO users
        (id, full_name, email, password_hash, ic_number, phone, role, email_verified)
        VALUES (1, 'System Admin', 'admin@test.com', '$adminHash', '000000000000', '0000000000', 'admin', 1)");
// Seed: Student user (password: Valid@1234)
    $studentHash = password_hash('Valid@1234', PASSWORD_BCRYPT, ['cost' => 10]);
    $pdo->exec("INSERT OR IGNORE INTO users
        (id, full_name, email, password_hash, ic_number, phone, role, email_verified)
        VALUES (2, 'Test Student', 'student@test.com', '$studentHash', '111111111111', '0123456789', 'student', 1)");
// Seed: 2 test programmes
    $pdo->exec("INSERT OR IGNORE INTO programmes
        (id, name, category, description, duration, foundation_fee, undergraduate_fee, is_active)
        VALUES
        (1, 'Computer Science', 'Technology', 'Test CS programme', '3 Years', 21000, 82500, 1),
        (2, 'Mechanical Engineering', 'Engineering', 'Test ME programme', '4 Years', 21000, 110000, 1)");
// Seed: Entry requirements for both programmes (SPM)
    $pdo->exec("INSERT OR IGNORE INTO entry_requirements
        (programme_id, qual_type, subject, min_grade, weight)
        VALUES
        (1, 'SPM', 'Mathematics', 'C', 1.00),
        (1, 'SPM', 'English', 'C', 0.90),
        (2, 'SPM', 'Mathematics', 'C', 1.00),
        (2, 'SPM', 'Physics', 'C', 1.00),
        (2, 'SPM', 'English', 'C', 0.90)");
// Seed: 1 scholarship
    $pdo->exec("INSERT OR IGNORE INTO scholarships
        (id, name, type, budget_min, budget_max, min_fit_percentage, is_active)
        VALUES (1, 'Test Scholarship', 'scholarship', 5000, 20000, 60, 1)");
    $pdo->exec("INSERT OR IGNORE INTO scholarship_programme (scholarship_id, programme_id)
        VALUES (1, 1), (1, 2)");
    return $pdo;
}

// Stub out functions that use real mail or filesystem in tests
if (!function_exists('trackEvent')) {
    function trackEvent($event, $context = [], $level = 'INFO')
    {
     /* no-op in tests */
    }

}
if (!function_exists('logAudit')) {
    function logAudit($userId, $action, $type = null, $id = null, $details = null)
    {
     /* no-op */
    }

}
if (!function_exists('sendVerificationEmail')) {
    function sendVerificationEmail($userId, $email, $name): bool
    {
        return true;
    }

}

// Load Composer autoloader for PSR-4 class resolution (src/ namespace)
require_once __DIR__ . '/../vendor/autoload.php';
// Load legacy bridge modules that the test bootstrap needs
require_once __DIR__ . '/../includes/init.php';
