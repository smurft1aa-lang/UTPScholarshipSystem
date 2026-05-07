<?php

use PHPUnit\Framework\TestCase;
use UTP\Services\BulkCsvImporter;

class BulkCsvImporterTest extends TestCase
{
    private \PDO $db;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->db = new \PDO('sqlite::memory:');
        $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

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

        $this->tmpDir = sys_get_temp_dir() . '/utp_csv_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        // Clean up temp files
        if (is_dir($this->tmpDir)) {
            array_map('unlink', glob($this->tmpDir . '/*'));
            rmdir($this->tmpDir);
        }
    }

    private function createCsv(string $filename, array $rows): string
    {
        $path = $this->tmpDir . '/' . $filename;
        $handle = fopen($path, 'w');
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);
        return $path;
    }

    // ─── Student Import Tests ─────────────────────────────────

    public function testImportStudentsSuccess(): void
    {
        $path = $this->createCsv('students.csv', [
            ['full_name', 'email', 'ic_number', 'phone'],
            ['Alice Tan', 'alice@test.com', '990101010001', '0121111111'],
            ['Bob Lee', 'bob@test.com', '990202020002', '0122222222'],
        ]);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importStudents($path);

        $this->assertTrue($result['success']);
        $this->assertSame(2, $result['imported']);
        $this->assertSame(0, $result['skipped']);
        $this->assertEmpty($result['errors']);

        // Verify DB
        $count = $this->db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $this->assertEquals(2, $count);
    }

    public function testImportStudentsSkipsDuplicateEmail(): void
    {
        // Pre-insert a user
        $this->db->exec("INSERT INTO users (full_name, email, password_hash, ic_number, phone) VALUES ('Existing', 'alice@test.com', 'hash', '990101010001', '012')");

        $path = $this->createCsv('students.csv', [
            ['full_name', 'email', 'ic_number', 'phone'],
            ['Alice Tan', 'alice@test.com', '990303030003', '0123333333'],
        ]);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importStudents($path);

        $this->assertTrue($result['success']);
        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('duplicate email', $result['errors'][0]);
    }

    public function testImportStudentsSkipsInvalidEmail(): void
    {
        $path = $this->createCsv('students.csv', [
            ['full_name', 'email', 'ic_number', 'phone'],
            ['Bad User', 'not-an-email', '990101010001', '0121111111'],
        ]);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importStudents($path);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertStringContainsString('invalid email', $result['errors'][0]);
    }

    public function testImportStudentsSkipsInvalidIC(): void
    {
        $path = $this->createCsv('students.csv', [
            ['full_name', 'email', 'ic_number', 'phone'],
            ['Bad IC', 'user@test.com', '123', '0121111111'],
        ]);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importStudents($path);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertStringContainsString('invalid IC', $result['errors'][0]);
    }

    // ─── Programme Import Tests ───────────────────────────────

    public function testImportProgrammesSuccess(): void
    {
        $path = $this->createCsv('programmes.csv', [
            ['name', 'category', 'description', 'duration', 'foundation_fee', 'undergraduate_fee'],
            ['CS', 'Engineering', 'Computer Science programme', '4 years', '15000', '45000'],
        ]);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importProgrammes($path);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['imported']);

        $prog = $this->db->query("SELECT * FROM programmes")->fetch();
        $this->assertSame('CS', $prog['name']);
    }

    public function testImportProgrammesSkipsDuplicate(): void
    {
        $this->db->exec("INSERT INTO programmes (name, category, description, duration) VALUES ('CS', 'Eng', 'Desc', '4y')");

        $path = $this->createCsv('programmes.csv', [
            ['name', 'category', 'description', 'duration', 'foundation_fee', 'undergraduate_fee'],
            ['CS', 'Engineering', 'Duplicate', '4 years', '15000', '45000'],
        ]);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importProgrammes($path);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertStringContainsString('already exists', $result['errors'][0]);
    }

    // ─── Scholarship Import Tests ─────────────────────────────

    public function testImportScholarshipsSuccess(): void
    {
        $path = $this->createCsv('scholarships.csv', [
            ['name', 'description', 'type', 'budget_min', 'budget_max', 'min_fit_percentage', 'start_date', 'end_date'],
            ['PETRONAS', 'Full scholarship', 'scholarship', '50000', '200000', '80', '2026-01-01', '2026-12-31'],
        ]);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importScholarships($path);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['imported']);
    }

    public function testImportScholarshipsInvalidType(): void
    {
        $path = $this->createCsv('scholarships.csv', [
            ['name', 'description', 'type', 'budget_min', 'budget_max', 'min_fit_percentage', 'start_date', 'end_date'],
            ['Bad Type', 'Desc', 'grant', '1000', '5000', '50', '2026-01-01', '2026-12-31'],
        ]);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importScholarships($path);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertStringContainsString('invalid type', $result['errors'][0]);
    }

    // ─── CSV Preview Tests ────────────────────────────────────

    public function testPreviewReturnHeadersAndRows(): void
    {
        $path = $this->createCsv('preview.csv', [
            ['name', 'email'],
            ['Alice', 'alice@test.com'],
            ['Bob', 'bob@test.com'],
            ['Charlie', 'charlie@test.com'],
        ]);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->preview($path, 2);

        $this->assertSame(['name', 'email'], $result['headers']);
        $this->assertCount(2, $result['rows']);
        $this->assertSame('Alice', $result['rows'][0][0]);
    }

    public function testPreviewEmptyFile(): void
    {
        $path = $this->tmpDir . '/empty.csv';
        file_put_contents($path, '');

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->preview($path);

        $this->assertEmpty($result['headers']);
        $this->assertEmpty($result['rows']);
    }

    // ─── Error Handling Tests ─────────────────────────────────

    public function testImportMissingColumnsReturnsError(): void
    {
        $path = $this->createCsv('bad_headers.csv', [
            ['full_name', 'email'],  // Missing ic_number, phone
            ['Alice', 'alice@test.com'],
        ]);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importStudents($path);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Missing required columns', $result['errors'][0]);
    }

    public function testImportNonExistentFileReturnsError(): void
    {
        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importStudents('/nonexistent/path/file.csv');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Failed to open', $result['errors'][0]);
    }

    public function testImportHandlesRowsWithBlankValues(): void
    {
        // Write CSV manually to include a row with empty values
        $path = $this->tmpDir . '/with_blanks.csv';
        $content = "name,category,description,duration,foundation_fee,undergraduate_fee\n";
        $content .= " ,,,,,\n";  // Row with blank values — still valid column count
        $content .= "CS,Engineering,Desc,4 years,15000,45000\n";
        file_put_contents($path, $content);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importProgrammes($path);

        $this->assertTrue($result['success']);
        // Both rows imported — blank values row has matching column count
        $this->assertSame(2, $result['imported']);
    }

    public function testImportColumnMismatchSkipsRow(): void
    {
        $path = $this->createCsv('mismatch.csv', [
            ['name', 'category', 'description', 'duration', 'foundation_fee', 'undergraduate_fee'],
            ['Only', 'Two'],  // Column count mismatch
        ]);

        $importer = new BulkCsvImporter($this->db);
        $result = $importer->importProgrammes($path);

        $this->assertSame(0, $result['imported']);
        $this->assertSame(1, $result['skipped']);
        $this->assertStringContainsString('column count mismatch', $result['errors'][0]);
    }
}
