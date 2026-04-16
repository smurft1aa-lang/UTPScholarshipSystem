<?php

declare(strict_types=1);

/**
 * Audit Log CSV Export
 * Exports audit log entries as a downloadable CSV file with optional date
 * filtering and row-by-row streaming to avoid memory exhaustion.
 * Restricted to admin users only.
 */

require_once __DIR__ . '/../includes/init.php';
setSecurityHeaders();
requireAdmin();
// ── Validate optional date-range params ────────────────────────────────
$dateFrom = $_GET['date_from'] ?? null;
$dateTo   = $_GET['date_to']   ?? null;
if ($dateFrom !== null && $dateFrom !== '') {
    $dt = \DateTime::createFromFormat('Y-m-d', $dateFrom);
    if (!$dt || $dt->format('Y-m-d') !== $dateFrom) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Invalid date_from format. Expected Y-m-d.']);
        exit;
    }
} else {
    $dateFrom = null;
}

if ($dateTo !== null && $dateTo !== '') {
    $dt = \DateTime::createFromFormat('Y-m-d', $dateTo);
    if (!$dt || $dt->format('Y-m-d') !== $dateTo) {
        http_response_code(400);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => 'Invalid date_to format. Expected Y-m-d.']);
        exit;
    }
} else {
    $dateTo = null;
}

// Default to last 90 days when neither param is supplied
if ($dateFrom === null && $dateTo === null) {
    $dateFrom = (new \DateTime())->modify('-90 days')->format('Y-m-d');
}

// ── Build query ────────────────────────────────────────────────────────
$db = getDB();
$sql = "SELECT a.id, u.full_name, u.email, a.action, a.target_type, a.target_id, a.details, a.ip_address, a.created_at
        FROM audit_log a
        LEFT JOIN users u ON a.user_id = u.id
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

try {
    $sql .= " ORDER BY a.created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
// ── Stream CSV row-by-row ──────────────────────────────────────────────
    $filename = 'audit_log_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store');
    $output = fopen('php://output', 'w');
// CSV Header Row
    fputcsv($output, ['ID', 'User Name', 'Email', 'Action', 'Target Type', 'Target ID', 'Details', 'IP Address', 'Timestamp']);
// Stream rows one at a time to avoid OOM on large audit logs
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
} catch (\Exception $e) {
    \UTP\Services\Telemetry::trackEvent('Audit CSV Export Failed', ['exception' => $e], 'ERROR');
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Failed to export audit log.']);
    exit;
}
