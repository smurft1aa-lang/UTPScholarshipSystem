<?php

declare(strict_types=1);

namespace UTP\Services;

/**
 * Data Export Service
 *
 * Generates CSV exports for admin dashboard downloads.
 * Uses streaming output for large datasets (fputcsv to php://temp)
 * then returns the complete CSV as a string for flexibility.
 */
class DataExporter
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    // ─── Student Export ────────────────────────────────────────

    /**
     * Export all student accounts as CSV.
     * Excludes admin accounts.
     *
     * @return string CSV content
     */
    public function exportStudents(): string
    {
        $headers = ['full_name', 'email', 'ic_number', 'phone', 'email_verified', 'created_at'];

        $stmt = $this->db->prepare(
            "SELECT full_name, email, ic_number, phone, email_verified, created_at
             FROM users WHERE role = 'student' ORDER BY created_at DESC"
        );
        $stmt->execute();

        return $this->buildCsv($headers, $stmt);
    }

    // ─── Application Export ────────────────────────────────────

    /**
     * Export all applications as CSV with student and programme info.
     *
     * @param string|null $dateFrom Filter start date (YYYY-MM-DD)
     * @param string|null $dateTo   Filter end date (YYYY-MM-DD)
     * @return string CSV content
     */
    public function exportApplications(?string $dateFrom = null, ?string $dateTo = null): string
    {
        $headers = [
            'student_name', 'student_email', 'qualification_type', 'status',
            'programme_1', 'programme_2', 'programme_3',
            'scholarship', 'admin_notes', 'created_at', 'updated_at'
        ];

        $sql = "SELECT
                    u.full_name AS student_name,
                    u.email AS student_email,
                    q.qual_type AS qualification_type,
                    a.status,
                    p1.name AS programme_1,
                    p2.name AS programme_2,
                    p3.name AS programme_3,
                    s.name AS scholarship,
                    a.admin_notes,
                    a.created_at,
                    a.updated_at
                FROM applications a
                JOIN users u ON a.user_id = u.id
                JOIN qualifications q ON a.qualification_id = q.id
                LEFT JOIN programmes p1 ON a.programme_id_1 = p1.id
                LEFT JOIN programmes p2 ON a.programme_id_2 = p2.id
                LEFT JOIN programmes p3 ON a.programme_id_3 = p3.id
                LEFT JOIN scholarships s ON a.scholarship_id = s.id
                WHERE 1=1";

        $params = [];
        if ($dateFrom) {
            $sql .= " AND a.created_at >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND a.created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }

        $sql .= " ORDER BY a.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->buildCsv($headers, $stmt);
    }

    // ─── Scholarship Export ────────────────────────────────────

    /**
     * Export all scholarships as CSV.
     *
     * @return string CSV content
     */
    public function exportScholarships(): string
    {
        $headers = [
            'name', 'description', 'type', 'budget_min', 'budget_max',
            'min_fit_percentage', 'start_date', 'end_date', 'is_active'
        ];

        $stmt = $this->db->prepare(
            "SELECT name, description, type, budget_min, budget_max,
                    min_fit_percentage, start_date, end_date, is_active
             FROM scholarships ORDER BY name"
        );
        $stmt->execute();

        return $this->buildCsv($headers, $stmt);
    }

    // ─── Monthly Analytics Report ──────────────────────────────

    /**
     * Generate monthly acceptance analytics report as CSV.
     *
     * @param string|null $year Filter by year (default: current year)
     * @return string CSV content
     */
    public function exportMonthlyAnalytics(?string $year = null): string
    {
        $year = $year ?: date('Y');

        $headers = [
            'month', 'total_applications', 'approved', 'rejected',
            'pending', 'approval_rate_percent'
        ];

        // SQLite uses strftime, MySQL uses DATE_FORMAT — handle both
        $driver = $this->db->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $monthExpr = "strftime('%Y-%m', a.created_at)";
            $yearFilter = "strftime('%Y', a.created_at) = ?";
        } else {
            $monthExpr = "DATE_FORMAT(a.created_at, '%Y-%m')";
            $yearFilter = "YEAR(a.created_at) = ?";
        }

        $sql = "SELECT
                    {$monthExpr} AS month,
                    COUNT(*) AS total_applications,
                    SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) AS approved,
                    SUM(CASE WHEN a.status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                    SUM(CASE WHEN a.status IN ('submitted', 'processing') THEN 1 ELSE 0 END) AS pending,
                    ROUND(
                        CASE WHEN COUNT(*) > 0
                        THEN (SUM(CASE WHEN a.status = 'approved' THEN 1 ELSE 0 END) * 100.0 / COUNT(*))
                        ELSE 0 END, 1
                    ) AS approval_rate_percent
                FROM applications a
                WHERE {$yearFilter}
                GROUP BY month
                ORDER BY month";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$year]);

        return $this->buildCsv($headers, $stmt);
    }

    // ─── Core CSV Builder ──────────────────────────────────────

    /**
     * Build CSV string from headers and a PDOStatement.
     *
     * @param string[]      $headers Column headers
     * @param \PDOStatement $stmt    Executed statement to fetch rows from
     * @return string Complete CSV content
     */
    private function buildCsv(array $headers, \PDOStatement $stmt): string
    {
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            // Ensure column order matches headers
            $orderedRow = [];
            foreach ($headers as $h) {
                $orderedRow[] = $row[$h] ?? '';
            }
            fputcsv($output, $orderedRow);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }

    // ─── Stream to Browser ─────────────────────────────────────

    /**
     * Send CSV content directly to the browser as a download.
     *
     * @param string $csv      CSV content string
     * @param string $filename Download filename
     */
    public static function sendDownload(string $csv, string $filename): void
    {
        // Sanitize filename to prevent header injection (TaintedHeader)
        // Strip path separators, null bytes, and newlines; whitelist safe characters
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-.]/', '_', basename($filename));
        $safeFilename = $safeFilename ?: 'export.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Cache-Control: no-cache, no-store');
        header('Content-Length: ' . strlen($csv));
        echo $csv;
        exit;
    }
}
