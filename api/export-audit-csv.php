<?php
/**
 * Audit Log CSV Export
 * Exports all audit log entries as a downloadable CSV file.
 * Restricted to admin users only.
 */
require_once __DIR__ . '/../includes/init.php';
setSecurityHeaders();
requireAdmin();

$db = getDB();

// Optional date range filtering — sanitize inputs
$startDate = isset($_GET['start']) ? sanitize($_GET['start']) : null;
$endDate = isset($_GET['end']) ? sanitize($_GET['end']) : null;

// Validate date format (YYYY-MM-DD) to prevent SQL injection via date params
if ($startDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate))
    $startDate = null;
if ($endDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate))
    $endDate = null;

$sql = "SELECT a.id, u.full_name, u.email, a.action, a.target_type, a.target_id, a.details, a.ip_address, a.created_at
        FROM audit_log a
        LEFT JOIN users u ON a.user_id = u.id
        WHERE 1=1";
$params = [];

if ($startDate) {
    $sql .= " AND a.created_at >= ?";
    $params[] = $startDate;
}
if ($endDate) {
    $sql .= " AND a.created_at <= ?";
    $params[] = $endDate . ' 23:59:59';
}

$sql .= " ORDER BY a.created_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set CSV download headers
$filename = 'audit_log_' . date('Y-m-d_His') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, no-store');

$output = fopen('php://output', 'w');

// CSV Header Row
fputcsv($output, ['ID', 'User Name', 'Email', 'Action', 'Target Type', 'Target ID', 'Details', 'IP Address', 'Timestamp']);

// Data Rows
foreach ($rows as $row) {
    fputcsv($output, [
        $row['id'],
        $row['full_name'] ?? 'System',
        $row['email'] ?? '',
        $row['action'],
        $row['target_type'] ?? '',
        $row['target_id'] ?? '',
        $row['details'] ?? '',
        $row['ip_address'] ?? '',
        $row['created_at'],
    ]);
}

fclose($output);
exit;
