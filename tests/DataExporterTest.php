<?php

use PHPUnit\Framework\TestCase;
use UTP\Services\DataExporter;

class DataExporterTest extends TestCase
{
    private \PDO $db;

    protected function setUp(): void
    {
        $this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // Create tables matching the application schema
        $this->db->exec("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                ic_number TEXT NOT NULL,
                phone TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'student',
                email_verified INTEGER DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->db->exec("
            CREATE TABLE qualifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                qual_type TEXT NOT NULL
            )
        ");

        $this->db->exec("
            CREATE TABLE programmes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                category TEXT,
                description TEXT,
                duration TEXT,
                foundation_fee REAL DEFAULT 0,
                undergraduate_fee REAL DEFAULT 0,
                is_active INTEGER DEFAULT 1
            )
        ");

        $this->db->exec("
            CREATE TABLE scholarships (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                description TEXT,
                type TEXT DEFAULT 'scholarship',
                budget_min REAL DEFAULT 0,
                budget_max REAL DEFAULT 0,
                min_fit_percentage INTEGER DEFAULT 0,
                start_date TEXT,
                end_date TEXT,
                is_active INTEGER DEFAULT 1
            )
        ");

        $this->db->exec("
            CREATE TABLE applications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                qualification_id INTEGER NOT NULL,
                programme_id_1 INTEGER,
                programme_id_2 INTEGER,
                programme_id_3 INTEGER,
                scholarship_id INTEGER,
                status TEXT DEFAULT 'submitted',
                admin_notes TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Seed test data
        $this->db->exec("INSERT INTO users (full_name, email, password_hash, ic_number, phone, role) VALUES
            ('Alice Tan', 'alice@test.com', 'hash1', '990101010001', '0121111111', 'student'),
            ('Bob Lee', 'bob@test.com', 'hash2', '990202020002', '0122222222', 'student'),
            ('Admin User', 'admin@utp.edu.my', 'hash3', '880101010001', '0130000000', 'admin')
        ");

        $this->db->exec("INSERT INTO qualifications (user_id, qual_type) VALUES (1, 'SPM'), (2, 'O-Level')");

        $this->db->exec("INSERT INTO programmes (name, category, duration, foundation_fee, undergraduate_fee) VALUES
            ('Computer Science', 'Engineering', '4 years', 15000, 45000),
            ('Business Admin', 'Business', '3 years', 12000, 38000),
            ('Petroleum Eng', 'Engineering', '4 years', 18000, 55000)
        ");

        $this->db->exec("INSERT INTO scholarships (name, description, type, budget_min, budget_max, min_fit_percentage, start_date, end_date) VALUES
            ('PETRONAS Scholarship', 'Full scholarship', 'scholarship', 50000, 200000, 80, '2026-01-01', '2026-12-31')
        ");

        $this->db->exec("INSERT INTO applications (user_id, qualification_id, programme_id_1, programme_id_2, programme_id_3, scholarship_id, status, created_at) VALUES
            (1, 1, 1, 2, 3, 1, 'approved', '2026-03-15 10:00:00'),
            (2, 2, 2, 1, 3, NULL, 'submitted', '2026-04-01 14:30:00')
        ");
    }

    public function testExportStudentsReturnsCsv(): void
    {
        $exporter = new DataExporter($this->db);
        $csv = $exporter->exportStudents();

        $this->assertStringContainsString('full_name', $csv);
        $this->assertStringContainsString('Alice Tan', $csv);
        $this->assertStringContainsString('Bob Lee', $csv);
        // Admin should NOT be in student export
        $this->assertStringNotContainsString('Admin User', $csv);
    }

    public function testExportStudentsHasCorrectColumnCount(): void
    {
        $exporter = new DataExporter($this->db);
        $csv = $exporter->exportStudents();
        $lines = array_filter(explode("\n", trim($csv)));

        // Header + 2 student rows
        $this->assertCount(3, $lines);

        // Header should have 6 columns
        $header = str_getcsv($lines[0]);
        $this->assertCount(6, $header);
    }

    public function testExportApplicationsReturnsCsv(): void
    {
        $exporter = new DataExporter($this->db);
        $csv = $exporter->exportApplications();

        $this->assertStringContainsString('student_name', $csv);
        $this->assertStringContainsString('Alice Tan', $csv);
        $this->assertStringContainsString('approved', $csv);
        $this->assertStringContainsString('Computer Science', $csv);
    }

    public function testExportApplicationsWithDateFilter(): void
    {
        $exporter = new DataExporter($this->db);
        // Filter to only March 2026 — should get Alice only
        $csv = $exporter->exportApplications('2026-03-01', '2026-03-31');

        $this->assertStringContainsString('Alice Tan', $csv);
        $this->assertStringNotContainsString('Bob Lee', $csv);
    }

    public function testExportApplicationsWithFutureDate(): void
    {
        $exporter = new DataExporter($this->db);
        $csv = $exporter->exportApplications('2099-01-01', '2099-12-31');

        $lines = array_filter(explode("\n", trim($csv)));
        // Only header row — no data matches
        $this->assertCount(1, $lines);
    }

    public function testExportScholarshipsReturnsCsv(): void
    {
        $exporter = new DataExporter($this->db);
        $csv = $exporter->exportScholarships();

        $this->assertStringContainsString('PETRONAS Scholarship', $csv);
        $this->assertStringContainsString('scholarship', $csv);
        $this->assertStringContainsString('50000', $csv);
    }

    public function testExportMonthlyAnalyticsReturnsCsv(): void
    {
        $exporter = new DataExporter($this->db);
        $csv = $exporter->exportMonthlyAnalytics('2026');

        $this->assertStringContainsString('month', $csv);
        $this->assertStringContainsString('total_applications', $csv);
        // Should have data for 2026
        $this->assertStringContainsString('2026', $csv);
    }

    public function testExportMonthlyAnalyticsDefaultsToCurrentYear(): void
    {
        $exporter = new DataExporter($this->db);
        $csv = $exporter->exportMonthlyAnalytics();

        // Should still return a valid CSV with headers
        $this->assertStringContainsString('month', $csv);
        $this->assertStringContainsString('approval_rate_percent', $csv);
    }

    public function testExportStudentsWithEmptyTable(): void
    {
        // Use a fresh DB with empty tables
        $emptyDb = new \PDO('sqlite::memory:');
        $emptyDb->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, full_name TEXT, email TEXT, ic_number TEXT, phone TEXT, email_verified INTEGER, role TEXT DEFAULT 'student', created_at TEXT)");

        $exporter = new DataExporter($emptyDb);
        $csv = $exporter->exportStudents();

        $lines = array_filter(explode("\n", trim($csv)));
        // Header only
        $this->assertCount(1, $lines);
        $this->assertStringContainsString('full_name', $csv);
    }
}
