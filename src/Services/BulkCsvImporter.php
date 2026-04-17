<?php

declare(strict_types=1);

namespace UTP\Services;

/**
 * Bulk CSV Import Service
 *
 * Handles parsing, validation, and database insertion of CSV files
 * for students, programmes, and scholarships. Each import runs inside
 * a transaction so partial failures are rolled back cleanly.
 */
class BulkCsvImporter
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    // ─── Student Import ────────────────────────────────────────

    /**
     * Import student accounts from a CSV file.
     *
     * Required columns: full_name, email, ic_number, phone
     * Duplicates (by email or IC) are skipped with an error message.
     * A random password is generated for each imported student.
     *
     * @param string $filePath Absolute path to the uploaded CSV file
     * @return array{success: bool, imported: int, skipped: int, errors: string[]}
     */
    public function importStudents(string $filePath): array
    {
        $required = ['full_name', 'email', 'ic_number', 'phone'];
        return $this->processImport($filePath, $required, function (array $row): ?string {
            // Validate email format
            if (!filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                return "Row skipped: invalid email '{$row['email']}'";
            }

            // Validate IC (12 digits after removing dashes/spaces)
            $cleanIC = preg_replace('/[-\s]/', '', $row['ic_number']);
            if (!preg_match('/^\d{12}$/', $cleanIC)) {
                return "Row skipped: invalid IC number '{$row['ic_number']}'";
            }

            // Check for duplicate email
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([trim($row['email'])]);
            if ($stmt->fetch()) {
                return "Row skipped: duplicate email '{$row['email']}'";
            }

            // Check for duplicate IC
            $stmt = $this->db->prepare(
                "SELECT id FROM users WHERE REPLACE(REPLACE(ic_number, '-', ''), ' ', '') = ?"
            );
            $stmt->execute([$cleanIC]);
            if ($stmt->fetch()) {
                return "Row skipped: duplicate IC number '{$row['ic_number']}'";
            }

            // Generate a temporary password (students should reset on first login)
            $tempPassword = bin2hex(random_bytes(8));
            $hash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => 10]);

            $stmt = $this->db->prepare(
                "INSERT INTO users (full_name, email, password_hash, ic_number, phone, role, email_verified)
                 VALUES (?, ?, ?, ?, ?, 'student', 0)"
            );
            $stmt->execute([
                trim($row['full_name']),
                trim($row['email']),
                $hash,
                trim($row['ic_number']),
                trim($row['phone']),
            ]);

            return null; // Success
        });
    }

    // ─── Programme Import ──────────────────────────────────────

    /**
     * Import programmes from a CSV file.
     *
     * Required columns: name, category, description, duration, foundation_fee, undergraduate_fee
     *
     * @param string $filePath Absolute path to the uploaded CSV file
     * @return array{success: bool, imported: int, skipped: int, errors: string[]}
     */
    public function importProgrammes(string $filePath): array
    {
        $required = ['name', 'category', 'description', 'duration', 'foundation_fee', 'undergraduate_fee'];
        return $this->processImport($filePath, $required, function (array $row): ?string {
            // Check for duplicate programme name
            $stmt = $this->db->prepare("SELECT id FROM programmes WHERE name = ?");
            $stmt->execute([trim($row['name'])]);
            if ($stmt->fetch()) {
                return "Row skipped: programme '{$row['name']}' already exists";
            }

            $stmt = $this->db->prepare(
                "INSERT INTO programmes (name, category, description, duration, foundation_fee, undergraduate_fee, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, 1)"
            );
            $stmt->execute([
                trim($row['name']),
                trim($row['category']),
                trim($row['description']),
                trim($row['duration']),
                (float) $row['foundation_fee'],
                (float) $row['undergraduate_fee'],
            ]);

            return null;
        });
    }

    // ─── Scholarship Import ────────────────────────────────────

    /**
     * Import scholarships from a CSV file.
     *
     * Required columns: name, description, type, budget_min, budget_max,
     *                    min_fit_percentage, start_date, end_date
     *
     * @param string $filePath Absolute path to the uploaded CSV file
     * @return array{success: bool, imported: int, skipped: int, errors: string[]}
     */
    public function importScholarships(string $filePath): array
    {
        $required = ['name', 'description', 'type', 'budget_min', 'budget_max', 'min_fit_percentage', 'start_date', 'end_date'];
        return $this->processImport($filePath, $required, function (array $row): ?string {
            // Validate scholarship type
            $validTypes = ['scholarship', 'loan', 'sponsorship', 'financial_aid'];
            if (!in_array($row['type'], $validTypes, true)) {
                return "Row skipped: invalid type '{$row['type']}'. Must be one of: " . implode(', ', $validTypes);
            }

            // Check for duplicate name
            $stmt = $this->db->prepare("SELECT id FROM scholarships WHERE name = ?");
            $stmt->execute([trim($row['name'])]);
            if ($stmt->fetch()) {
                return "Row skipped: scholarship '{$row['name']}' already exists";
            }

            $stmt = $this->db->prepare(
                "INSERT INTO scholarships (name, description, type, budget_min, budget_max, min_fit_percentage, start_date, end_date, is_active)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)"
            );
            $stmt->execute([
                trim($row['name']),
                trim($row['description']),
                trim($row['type']),
                (float) $row['budget_min'],
                (float) $row['budget_max'],
                (int) $row['min_fit_percentage'],
                trim($row['start_date']),
                trim($row['end_date']),
            ]);

            return null;
        });
    }

    // ─── CSV Preview ───────────────────────────────────────────

    /**
     * Read the first N rows of a CSV file for preview.
     *
     * @param string $filePath Path to CSV file
     * @param int    $maxRows  Maximum rows to preview (default 5)
     * @return array{headers: string[], rows: array<int, string[]>}
     */
    public function preview(string $filePath, int $maxRows = 5): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['headers' => [], 'rows' => []];
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return ['headers' => [], 'rows' => []];
        }

        $headers = array_map('trim', $headers);
        $rows = [];
        $count = 0;

        while (($data = fgetcsv($handle)) !== false && $count < $maxRows) {
            if (count($data) === count($headers)) {
                $rows[] = array_map('trim', $data);
                $count++;
            }
        }

        fclose($handle);
        return ['headers' => $headers, 'rows' => $rows];
    }

    // ─── Core Processing Logic ─────────────────────────────────

    /**
     * Generic CSV import processor with validation and transaction support.
     *
     * @param string   $filePath      Path to CSV file
     * @param string[] $requiredCols  Required column headers
     * @param callable $rowHandler    Callback to process each row; returns null on success, error string on skip
     * @return array{success: bool, imported: int, skipped: int, errors: string[]}
     */
    private function processImport(string $filePath, array $requiredCols, callable $rowHandler): array
    {
        $result = ['success' => false, 'imported' => 0, 'skipped' => 0, 'errors' => []];

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $result['errors'][] = 'Failed to open CSV file.';
            return $result;
        }

        // Read and validate header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            $result['errors'][] = 'CSV file is empty or unreadable.';
            return $result;
        }

        $headers = array_map('trim', array_map('strtolower', $headers));
        $missing = array_diff($requiredCols, $headers);

        if (!empty($missing)) {
            fclose($handle);
            $result['errors'][] = 'Missing required columns: ' . implode(', ', $missing);
            return $result;
        }

        // Build column index map for flexible column ordering
        $colMap = array_flip($headers);

        $result['success'] = true;
        $lineNum = 1;

        // Wrap in transaction for atomicity
        $inTransaction = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $inTransaction = true;
        }

        try {
            while (($data = fgetcsv($handle)) !== false) {
                $lineNum++;

                // Skip blank rows
                if (count($data) === 1 && trim($data[0]) === '') {
                    continue;
                }

                if (count($data) !== count($headers)) {
                    $result['errors'][] = "Row {$lineNum}: column count mismatch";
                    $result['skipped']++;
                    continue;
                }

                // Map columns to associative array
                $row = [];
                foreach ($requiredCols as $col) {
                    $row[$col] = $data[$colMap[$col]] ?? '';
                }

                // Run the entity-specific handler
                $error = $rowHandler($row);
                if ($error !== null) {
                    $result['errors'][] = "Row {$lineNum}: {$error}";
                    $result['skipped']++;
                } else {
                    $result['imported']++;
                }
            }

            if ($inTransaction) {
                $this->db->commit();
            }
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $result['success'] = false;
            $result['errors'][] = 'Import failed: ' . $e->getMessage();
        }

        fclose($handle);
        return $result;
    }
}
