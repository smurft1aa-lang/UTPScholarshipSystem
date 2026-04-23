<?php
/**
 * Admin Applications Management
 * View, filter, approve/reject student applications
 */
require_once __DIR__ . '/../includes/init.php';

$db = getDB();
$statusFilter = $_GET['status'] ?? '';
$search = sanitize($_GET['search'] ?? '');

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $appId = intval($_POST['app_id'] ?? 0);
        $action = sanitize($_POST['action']);
        $notes = sanitize($_POST['admin_notes'] ?? '');

        if (in_array($action, ['processing', 'approved', 'rejected']) && $appId > 0) {
            $stmt = $db->prepare("UPDATE applications SET status = ?, admin_notes = ?, reviewed_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$action, $notes, $_SESSION['user_id'], $appId]);
            logAudit($_SESSION['user_id'], 'Application Status Changed', 'Application', $appId, "Status: $action");
            
            // Fetch student info and dispatch email notification
            $stmt = $db->prepare("
                SELECT u.email, u.full_name, p.name as prog_name 
                FROM applications a 
                JOIN users u ON a.user_id = u.id 
                LEFT JOIN programmes p ON a.programme_id_1 = p.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$appId]);
            $studentInfo = $stmt->fetch();
            if ($studentInfo) {
                sendApplicationStatusEmail(
                    $studentInfo['email'], 
                    $studentInfo['full_name'], 
                    $action, 
                    $studentInfo['prog_name'] ?: 'UTP Programme', 
                    $notes
                );
            }
        }
    }
    header('Location: /admin/applications.php' . ($statusFilter ? '?status=' . $statusFilter : ''));
    exit;
}

require_once __DIR__ . '/admin_header.php';

// Build query
$where = [];
$params = [];

if ($statusFilter && in_array($statusFilter, ['submitted', 'processing', 'approved', 'rejected'])) {
    $where[] = "a.status = ?";
    $params[] = $statusFilter;
}
if ($search) {
    $where[] = "(u.full_name LIKE ? OR u.email LIKE ?)";
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
           p1.name as prog1_name, p2.name as prog2_name, p3.name as prog3_name, s.name as schol_name,
           (SELECT COUNT(*) FROM eligibility_results er WHERE er.application_id = a.id AND er.eligible = 1) as eligible_count,
           (SELECT COUNT(*) FROM documents d WHERE d.user_id = a.user_id) as doc_count,
           (SELECT GROUP_CONCAT(doc_type) FROM documents d WHERE d.user_id = a.user_id) as uploaded_docs
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN qualifications q ON a.qualification_id = q.id
    LEFT JOIN programmes p1 ON a.programme_id_1 = p1.id
    LEFT JOIN programmes p2 ON a.programme_id_2 = p2.id
    LEFT JOIN programmes p3 ON a.programme_id_3 = p3.id
    LEFT JOIN scholarships s ON a.scholarship_id = s.id
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
    <h1>Applications</h1>
    <p>Manage student applications and update their status.</p>
</div>

<!-- Filters -->
<div class="card mb-4" style="padding:16px 20px;">
    <form method="GET" class="flex" style="gap:12px; align-items:center; flex-wrap:wrap;">
        <select name="status" class="form-select" style="width:auto; min-width:160px;" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="submitted" <?= $statusFilter === 'submitted' ? 'selected' : '' ?>>Submitted</option>
            <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
            <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="rejected" <?= $statusFilter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
        </select>
        <input type="text" name="search" class="form-input admin-focus" style="width:auto; min-width:240px;" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-purple btn-sm">Search</button>
        <?php if ($statusFilter || $search): ?>
            <a href="/admin/applications.php" class="btn btn-outline btn-sm">Clear</a>
        <?php endif; ?>
    </form>
</div>

<!-- Applications Table -->
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Student</th>
                <th>IC Number</th>
                <th>Qualification</th>
                <th>Choice</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($applications)): ?>
                <tr><td colspan="8" style="text-align:center; padding:32px; color:var(--text-muted);">No applications found.</td></tr>
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
                        <?= htmlspecialchars($app['qual_type']) ?><br>
                        <small style="color:var(--text-muted);"><?= $app['eligible_count'] ?> eligible</small>
                    </td>
                    <td>
                        <div style="font-size:0.8rem; margin-bottom:4px;">
                            Docs: <span class="badge badge-<?= $app['doc_count'] >= 3 ? 'green' : 'red' ?>"><?= $app['doc_count'] ?>/3</span>
                        </div>
                        <?php if ($app['prog1_name']): ?>
                            <div style="font-size:0.85rem; line-height:1.4;">
                                <strong>1.</strong> <?= htmlspecialchars($app['prog1_name']) ?><br>
                                <strong>2.</strong> <?= htmlspecialchars($app['prog2_name']) ?><br>
                                <strong>3.</strong> <?= htmlspecialchars($app['prog3_name']) ?>
                            </div>
                            <?= $app['schol_name'] ? '<span style="font-size:0.8rem; color:var(--purple); display:block; margin-top:4px;">+ ' . htmlspecialchars($app['schol_name']) . '</span>' : '' ?>
                        <?php else: ?>
                            <span style="color:var(--text-muted); font-style:italic;">Not Selected</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= $app['status'] === 'approved' ? 'green' : ($app['status'] === 'rejected' ? 'red' : ($app['status'] === 'processing' ? 'yellow' : 'blue')) ?>">
                            <?= ucfirst($app['status']) ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($app['created_at'])) ?></td>
                    <td>
                        <button data-modal-target="modal_<?= $app['id'] ?>" class="btn btn-outline btn-sm">Review</button>
                    </td>
                </tr>

                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Review Modals -->
<?php if (!empty($applications)): ?>
    <?php foreach ($applications as $app): ?>
    <div class="modal-overlay" id="modal_<?= $app['id'] ?>">
        <div class="modal">
            <h2>Review Application #<?= $app['id'] ?></h2>
            <p><strong>Student:</strong> <?= htmlspecialchars($app['full_name']) ?> (<?= htmlspecialchars($app['ic_number']) ?>)</p>
            <p><strong>Qualification:</strong> <?= htmlspecialchars($app['qual_type']) ?> (Eligible for <?= $app['eligible_count'] ?> programmes)</p>
            <div style="margin-top: 8px; margin-bottom: 8px;">
                <strong>Documents (<?= $app['doc_count'] ?> out of 3 uploaded):</strong>
                <div style="display: flex; gap: 8px; margin-top: 8px;">
                    <?php
                        $userDocs = $app['uploaded_docs'] ? explode(',', $app['uploaded_docs']) : [];
                        $validTypes = ['ic' => 'IC Scan', 'photo' => 'Photo', 'certificate' => 'Certificate'];
                        foreach ($validTypes as $typeKey => $typeLabel):
                            if (in_array($typeKey, $userDocs)):
                    ?>
                        <a href="/admin/download-document.php?user_id=<?= $app['user_id'] ?>&doc_type=<?= $typeKey ?>" target="_blank" class="badge badge-green" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                            <?= $typeLabel ?>
                        </a>
                    <?php 
                            else:
                    ?>
                        <span class="badge badge-red" style="opacity: 0.6; cursor: not-allowed;"><?= $typeLabel ?> (Missing)</span>
                    <?php 
                            endif;
                        endforeach; 
                    ?>
                </div>
            </div>
            
            <div style="background:var(--bg-card); padding:12px; border-radius:6px; border:1px solid var(--border); margin:12px 0;">
                <?php if ($app['prog1_name']): ?>
                    <p style="margin-bottom:8px;"><strong>Applied Programmes:</strong><br>
                        1. <?= htmlspecialchars($app['prog1_name']) ?><br>
                        2. <?= htmlspecialchars($app['prog2_name']) ?><br>
                        3. <?= htmlspecialchars($app['prog3_name']) ?>
                    </p>
                <?php else: ?>
                    <p style="margin-bottom:8px;"><strong>Applied Programmes:</strong><br> <span style="color:var(--text-muted); font-style:italic;">Pending Selection</span></p>
                <?php endif; ?>
                <?php if ($app['schol_name']): ?>
                    <p><strong>Preferred Scholarship:</strong><br> <?= htmlspecialchars($app['schol_name']) ?></p>
                <?php endif; ?>
            </div>

            <p><strong>Current Status:</strong> <?= ucfirst($app['status']) ?></p>
            <?php if ($app['admin_notes']): ?>
                <p><strong>Previous Notes:</strong> <?= htmlspecialchars($app['admin_notes']) ?></p>
            <?php endif; ?>

            <form method="POST" style="margin-top:20px;">
                <?= csrfField() ?>
                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                <div class="form-group">
                    <label class="form-label">Admin Notes</label>
                    <textarea name="admin_notes" class="form-input admin-focus" rows="3" placeholder="Add notes..."><?= htmlspecialchars($app['admin_notes'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Update Status</label>
                    <div class="flex gap-3">
                        <button type="submit" name="action" value="processing" class="btn btn-outline btn-sm">Processing</button>
                        <button type="submit" name="action" value="approved" class="btn btn-success btn-sm">Approve</button>
                        <button type="submit" name="action" value="rejected" class="btn btn-danger btn-sm">Reject</button>
                    </div>
                </div>
            </form>
            <div class="modal-actions">
                <button data-modal-close class="btn btn-outline btn-sm">Close</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Pagination -->
<?php if ($totalPages > 1): ?>
<div style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:24px;">
    <?php
    $queryString = '';
    if ($statusFilter) $queryString .= '&status=' . urlencode($statusFilter);
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
