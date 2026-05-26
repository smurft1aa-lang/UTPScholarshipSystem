<?php
declare(strict_types=1);
/**
 * Admin Eligibility Checks Management
 * View student eligibility checks history
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$db = getDB();
$search = sanitize($_GET['search'] ?? '');

require_once __DIR__ . '/admin_header.php';

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.ic_number LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Validation for pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Count total records for pagination
$countQuery = "SELECT COUNT(*) FROM applications a JOIN users u ON a.user_id = u.id {$whereClause}";
$stmt = $db->prepare($countQuery);
$stmt->execute($params);
$totalRecords = $stmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

$query = "
    SELECT a.*, u.full_name, u.email, u.ic_number, q.qual_type,
           (SELECT COUNT(*) FROM eligibility_results er WHERE er.application_id = a.id AND er.eligible = 1) as eligible_count,
           (SELECT MAX(fit_percentage) FROM eligibility_results er WHERE er.application_id = a.id) as max_fit
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN qualifications q ON a.qualification_id = q.id
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
$applications = $stmt->fetchAll();
?>

<div class="page-header">
    <h1>Student Eligibility Checks</h1>
    <p>View the history of eligibility checks performed by students on the platform.</p>
</div>

<!-- Filters -->
<div class="card mb-4" style="padding:16px 20px;">
    <form method="GET" class="flex" style="gap:12px; align-items:center; flex-wrap:wrap;">
        <input type="text" name="search" class="form-input admin-focus" style="width:auto; min-width:300px;" placeholder="Search by name, email, or IC..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-purple btn-sm">Search</button>
        <?php if ($search): ?>
            <a href="/admin/applications.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Checks Table -->
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>Check ID</th>
                <th>Student</th>
                <th>IC Number</th>
                <th>Qualification</th>
                <th>Eligible Matches</th>
                <th>Highest Fit</th>
                <th>Date Checked</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($applications)): ?>
                <tr><td colspan="8" style="text-align:center; padding:32px; color:var(--text-muted);">No eligibility checks found.</td></tr>
            <?php else: ?>
                <?php foreach ($applications as $app): ?>
                <tr>
                    <td>#<?= $app['id'] ?></td>
                    <td>
                        <strong><?= htmlspecialchars($app['full_name']) ?></strong><br>
                        <span style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($app['email']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($app['ic_number']) ?></td>
                    <td>
                        <span class="badge badge-outline"><?= htmlspecialchars($app['qual_type']) ?></span>
                    </td>
                    <td>
                        <?= $app['eligible_count'] ?> programmes
                    </td>
                    <td>
                        <?php if ($app['max_fit']): ?>
                            <span class="badge badge-<?= $app['max_fit'] >= 75 ? 'green' : ($app['max_fit'] >= 50 ? 'yellow' : 'red') ?>">
                                <?= $app['max_fit'] ?>%
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td><?= date('d M Y, h:i A', strtotime($app['created_at'])) ?></td>
                    <td>
                        <a href="/admin/student-results.php?id=<?= $app['id'] ?>" class="btn btn-outline btn-sm">View Report</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:24px;">
    <?php
    $queryString = '';
    if ($search) $queryString .= '&search=' . urlencode($search);
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
