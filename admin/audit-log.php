<?php
/**
 * Admin Audit Log Viewer
 * View and filter system audit events
 */
require_once __DIR__ . '/admin_header.php';
require_once __DIR__ . '/../includes/security.php';

$db = getDB();

$actionFilter = sanitize($_GET['action_type'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');

$where = [];
$params = [];

if ($actionFilter) {
    $where[] = "a.action = ?";
    $params[] = $actionFilter;
}
if ($dateFrom) {
    $where[] = "DATE(a.created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo) {
    $where[] = "DATE(a.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Validation for pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Count total records for pagination
$countQuery = "SELECT COUNT(*) FROM audit_log a {$whereClause}";
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Data query
$query = "
    SELECT a.*, u.full_name, u.email
    FROM audit_log a
    LEFT JOIN users u ON a.user_id = u.id
    {$whereClause}
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = $db->prepare($query);
// Bind params dynamically
$paramIndex = 1;
foreach ($params as $param) {
    $stmt->bindValue($paramIndex++, $param);
}
$stmt->bindValue($paramIndex++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll();

// Get unique actions for filter
$stmt = $db->query("SELECT DISTINCT action FROM audit_log ORDER BY action");
$uniqueActions = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-header">
    <h1>System Audit Log</h1>
    <p>Monitor security events and administrative actions.</p>
</div>

<!-- Filters -->
<div class="card mb-4" style="padding:16px 20px;">
    <form method="GET" class="flex" style="gap:12px; align-items:center; flex-wrap:wrap;">
        <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-size:0.8rem; margin-bottom:4px;">Action Type</label>
            <select name="action_type" class="form-select" onchange="this.form.submit()">
                <option value="">All Actions</option>
                <?php foreach ($uniqueActions as $act): ?>
                    <option value="<?= htmlspecialchars($act) ?>" <?= $actionFilter === $act ? 'selected' : '' ?>><?= htmlspecialchars($act) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-size:0.8rem; margin-bottom:4px;">From Date</label>
            <input type="date" name="date_from" class="form-input" value="<?= htmlspecialchars($dateFrom) ?>">
        </div>
        
        <div class="form-group" style="margin:0;">
            <label class="form-label" style="font-size:0.8rem; margin-bottom:4px;">To Date</label>
            <input type="date" name="date_to" class="form-input" value="<?= htmlspecialchars($dateTo) ?>">
        </div>
        
        <div style="margin-top:20px;">
            <button type="submit" class="btn btn-purple btn-sm">Filter</button>
            <?php if ($actionFilter || $dateFrom || $dateTo): ?>
                <a href="/admin/audit-log.php" class="btn btn-outline btn-sm">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Log Table -->
<div class="card" style="padding:0; overflow:hidden;">
    <div class="table-wrap" style="border:none; box-shadow:none; margin:0;">
        <table>
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Target</th>
                    <th>IP Address</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" style="text-align:center; padding:32px; color:var(--text-muted);">No logs found.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td style="white-space:nowrap;"><?= date('y/m/d H:i:s', strtotime($log['created_at'])) ?></td>
                        <td>
                            <?php if ($log['user_id']): ?>
                                <strong><?= htmlspecialchars($log['full_name']) ?></strong><br>
                                <span style="font-size:0.8rem; color:var(--text-muted);">ID: <?= $log['user_id'] ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-muted);">System / Guest</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-outline"><?= htmlspecialchars($log['action']) ?></span></td>
                        <td>
                            <?php if ($log['target_type']): ?>
                                <?= htmlspecialchars($log['target_type']) ?> #<?= $log['target_id'] ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><code style="font-size:0.8rem; background:#f4f4f5; padding:2px 4px; border-radius:4px;"><?= htmlspecialchars($log['ip_address']) ?></code></td>
                        <td>
                            <?php if ($log['details']): ?>
                                <span style="font-size:0.85rem; color:var(--text-secondary); max-width:300px; display:inline-block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?= htmlspecialchars($log['details']) ?>">
                                    <?= htmlspecialchars($log['details']) ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:24px; margin-bottom:48px;">
    <?php
    $queryString = '';
    if ($actionFilter) $queryString .= '&action_type=' . urlencode($actionFilter);
    if ($dateFrom) $queryString .= '&date_from=' . urlencode($dateFrom);
    if ($dateTo) $queryString .= '&date_to=' . urlencode($dateTo);
    ?>
    
    <?php if ($page > 1): ?>
        <a href="?page=<?= $page - 1 . $queryString ?>" class="btn btn-outline btn-sm">Previous</a>
    <?php endif; ?>
    
    <span style="font-size:0.9rem; color:var(--text-secondary);">Page <?= $page ?> of <?= $totalPages ?></span>
    
    <?php if ($page < $totalPages): ?>
        <a href="?page=<?= $page + 1 . $queryString ?>" class="btn btn-outline btn-sm">Next</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
