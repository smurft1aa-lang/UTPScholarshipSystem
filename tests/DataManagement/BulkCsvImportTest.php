<?php

declare(strict_types=1);

namespace Tests\DataManagement;

use PHPUnit\Framework\TestCase;
use UTP\Services\BulkCsvImporter;

/**
 * BulkCsvImporter Unit Tests
 *
 * Validates CSV parsing, column mapping, duplicate detection,
 * and error handling for admin bulk-upload functionality.
 */
class BulkCsvImportTest extends TestCase
{
    private \PDO $db;
    private BulkCsvImporter $importer;

    protected function setUp(): void
    {
        $this->db = getDB();
        $this->importer = new BulkCsvImporter($this->db);
        $this->db->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }

    // ─── Student Import Tests ──────────────────────────────────

    public function testImportStudentsFromValidCsv(): void
    {
        $csvContent = "full_name,email,ic_number,phone\n";
        $csvContent .= "Alice Tan,alice@test.com,010101010101,0123456780\n";
        $csvContent .= "Bob Lee,bob@test.com,020202020202,0123456781\n";

        $file = $this->createTempCsv($csvContent);
        $result = $this->importer->importStudents($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['imported']);
        $this->assertEquals(0, $result['skipped']);
        $this->assertEmpty($result['errors']);
    }

    public function testImportStudentsDetectsDuplicateEmail(): void
    {
        // student@test.com already exists in test bootstrap
        $csvContent = "full_name,email,ic_number,phone\n";
        $csvContent .= "Duplicate Student,student@test.com,030303030303,0123456782\n";

        $file = $this->createTempCsv($csvContent);
        $result = $this->importer->importStudents($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('duplicate', strtolower($result['errors'][0]));
    }

    public function testImportStudentsRejectsMissingColumns(): void
    {
        // Missing 'phone' column
        $csvContent = "full_name,email,ic_number\n";
        $csvContent .= "Alice Tan,alice@test.com,010101010101\n";

        $file = $this->createTempCsv($csvContent);
        $result = $this->importer->importStudents($file);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('missing', strtolower($result['errors'][0]));
    }

    public function testImportStudentsRejectsInvalidEmail(): void
    {
        $csvContent = "full_name,email,ic_number,phone\n";
        $csvContent .= "Bad Email,not-an-email,040404040404,0123456783\n";

        $file = $this->createTempCsv($csvContent);
        $result = $this->importer->importStudents($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['skipped']);
    }

    public function testImportStudentsHandlesEmptyFile(): void
    {
        $csvContent = "full_name,email,ic_number,phone\n";

        $file = $this->createTempCsv($csvContent);
        $result = $this->importer->importStudents($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(0, $result['imported']);
    }

    // ─── Programme Import Tests ────────────────────────────────

    public function testImportProgrammesFromValidCsv(): void
    {
        $csvContent = "name,category,description,duration,foundation_fee,undergraduate_fee\n";
        $csvContent .= "Test Programme,Technology,A test programme,3 Years,21000,82500\n";

        $file = $this->createTempCsv($csvContent);
        $result = $this->importer->importProgrammes($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported']);
    }

    // ─── Scholarship Import Tests ──────────────────────────────

    public function testImportScholarshipsFromValidCsv(): void
    {
        $csvContent = "name,description,type,budget_min,budget_max,min_fit_percentage,start_date,end_date\n";
        $csvContent .= "Test Grant,A test scholarship,scholarship,5000,50000,60,2026-01-01,2026-12-31\n";

        $file = $this->createTempCsv($csvContent);
        $result = $this->importer->importScholarships($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported']);
    }

    public function testImportScholarshipsDetectsInvalidType(): void
    {
        $csvContent = "name,description,type,budget_min,budget_max,min_fit_percentage,start_date,end_date\n";
        $csvContent .= "Bad Type Grant,Desc,INVALID,5000,50000,60,2026-01-01,2026-12-31\n";

        $file = $this->createTempCsv($csvContent);
        $result = $this->importer->importScholarships($file);

        $this->assertEquals(1, $result['skipped']);
        $this->assertStringContainsString('invalid type', strtolower($result['errors'][0]));
    }

    public function testPreviewCsv(): void
    {
        $csvContent = "h1,h2\nv1,v2\nv3,v4\n";
        $file = $this->createTempCsv($csvContent);
        
        $preview = $this->importer->preview($file, 2);
        
        $this->assertEquals(['h1', 'h2'], $preview['headers']);
        $this->assertCount(2, $preview['rows']);
        $this->assertEquals(['v1', 'v2'], $preview['rows'][0]);
    }

    public function testProcessImportDetectsColumnCountMismatch(): void
    {
        $csvContent = "full_name,email,ic_number,phone\n";
        $csvContent .= "Alice Tan,alice@test.com,010101010101\n"; // Missing 1 col

        $file = $this->createTempCsv($csvContent);
        $result = $this->importer->importStudents($file);

        $this->assertEquals(1, $result['skipped']);
        $this->assertStringContainsString('column count mismatch', strtolower($result['errors'][0]));
    }

    public function testImportStudentsDetectsDuplicateIC(): void
    {
        // 111111111111 exists in bootstrap
        $csvContent = "full_name,email,ic_number,phone\n";
        $csvContent .= "IC Dupe,unique@test.com,111111111111,0123456780\n";

        $file = $this->createTempCsv($csvContent);
        $result = $this->importer->importStudents($file);

        $this->assertEquals(1, $result['skipped']);
        $this->assertStringContainsString('duplicate ic', strtolower($result['errors'][0]));
    }

    // ─── Helper ────────────────────────────────────────────────

    /**
     * Write CSV content to a temp file and return its path.
     */
    private function createTempCsv(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'csv_test_');
        file_put_contents($path, $content);
        return $path;
    }
}
