<?php

declare(strict_types=1);

namespace Tests\DataManagement;

use PHPUnit\Framework\TestCase;
use UTP\Services\DataExporter;

/**
 * DataExporter Unit Tests
 *
 * Validates CSV export generation for students, applications,
 * scholarships, and analytics reports.
 */
class DataExportTest extends TestCase
{
    private \PDO $db;
    private DataExporter $exporter;

    protected function setUp(): void
    {
        $this->db = getDB();
        $this->exporter = new DataExporter($this->db);
    }

    // ─── Student Export Tests ──────────────────────────────────

    public function testExportStudentsReturnsCsvString(): void
    {
        $csv = $this->exporter->exportStudents();

        $this->assertIsString($csv);
        $this->assertStringContainsString('full_name', $csv);
        $this->assertStringContainsString('email', $csv);
        // Should contain the seeded test student
        $this->assertStringContainsString('Test Student', $csv);
    }

    public function testExportStudentsExcludesAdmins(): void
    {
        $csv = $this->exporter->exportStudents();

        // Admin email should NOT appear in student export
        $this->assertStringNotContainsString('admin@test.com', $csv);
    }

    // ─── Application Export Tests ──────────────────────────────

    public function testExportApplicationsReturnsCsvString(): void
    {
        // Create a test application first
        $this->db->exec(
            "INSERT OR IGNORE INTO qualifications (id, user_id, qual_type) VALUES (99, 2, 'SPM')"
        );
        $this->db->exec(
            "INSERT OR IGNORE INTO applications (id, user_id, qualification_id, status) VALUES (99, 2, 99, 'submitted')"
        );

        $csv = $this->exporter->exportApplications();

        $this->assertIsString($csv);
        $this->assertStringContainsString('student_name', $csv);
        $this->assertStringContainsString('status', $csv);
    }

    public function testExportApplicationsWithDateFilter(): void
    {
        $csv = $this->exporter->exportApplications('2020-01-01', '2020-12-31');

        $this->assertIsString($csv);
        // Should still have headers even with no data
        $this->assertStringContainsString('student_name', $csv);
    }

    // ─── Scholarship Export Tests ──────────────────────────────

    public function testExportScholarshipsReturnsCsvString(): void
    {
        $csv = $this->exporter->exportScholarships();

        $this->assertIsString($csv);
        $this->assertStringContainsString('name', $csv);
        $this->assertStringContainsString('Test Scholarship', $csv);
    }

    // ─── Analytics Export Tests ────────────────────────────────

    public function testExportAnalyticsReturnsCsvString(): void
    {
        $csv = $this->exporter->exportMonthlyAnalytics();

        $this->assertIsString($csv);
        $this->assertStringContainsString('month', $csv);
    }

    // ─── Edge Cases ───────────────────────────────────────────

    public function testExportWithEmptyDatabaseReturnsHeadersOnly(): void
    {
        // Delete all applications and test
        $this->db->beginTransaction();
        $this->db->exec("DELETE FROM applications");

        $csv = $this->exporter->exportApplications();

        $this->assertIsString($csv);
        $this->assertStringContainsString('student_name', $csv);

        $this->db->rollBack();
    }
}
