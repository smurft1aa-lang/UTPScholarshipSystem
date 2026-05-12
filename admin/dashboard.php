<?php
declare(strict_types=1);
/**
 * Admin Dashboard
 * Overview with stats, application status, calendar
 */
require_once __DIR__ . '/../includes/init.php';

requireAdmin();

// Handle quick status update FIRST (before any output)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $db = getDB();
        $appId = intval($_POST['app_id'] ?? 0);
        $action = sanitize($_POST['action']);
        $notes = sanitize($_POST['admin_notes'] ?? '');
        if (in_array($action, ['processing', 'approved', 'rejected']) && $appId > 0) {
            $stmt = $db->prepare("UPDATE applications SET status = ?, admin_notes = ?, reviewed_by = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$action, $notes, $_SESSION['user_id'], $appId]);
            logAudit($_SESSION['user_id'], 'Application Status Changed', 'Application', $appId, "Status: $action");
            // Send email notification
            $stmt2 = $db->prepare("SELECT u.email, u.full_name, p.name as prog_name FROM applications a JOIN users u ON a.user_id = u.id LEFT JOIN programmes p ON a.programme_id_1 = p.id WHERE a.id = ?");
            $stmt2->execute([$appId]);
            $studentInfo = $stmt2->fetch();
            if ($studentInfo) {
                sendApplicationStatusEmail($studentInfo['email'], $studentInfo['full_name'], $action, $studentInfo['prog_name'] ?: 'UTP Programme', $notes);
            }
        }
    }
    header('Location: /admin/dashboard.php');
    exit;
}

// NOW load the header (which sends output)
require_once __DIR__ . '/admin_header.php';
$db = getDB();
// Stats
$totalStudents = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalApplications = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$pendingReview = $db->query("SELECT COUNT(*) FROM applications WHERE status = 'submitted' OR status = 'processing'")->fetchColumn();

// Status counts
$statusCounts = [];
$stmt = $db->query("SELECT status, COUNT(*) as cnt FROM applications GROUP BY status");
while ($row = $stmt->fetch()) {
    $statusCounts[$row['status']] = $row['cnt'];
}
$approved = $statusCounts['approved'] ?? 0;
$processing = $statusCounts['processing'] ?? 0;
$submitted = $statusCounts['submitted'] ?? 0;
$rejected = $statusCounts['rejected'] ?? 0;

$maxStatus = max(1, $approved, $processing, $submitted, $rejected);

// Recent applications (last 10)
$stmt = $db->query("
    SELECT a.*, u.full_name, u.email, q.qual_type,
           p1.name as prog1_name, p2.name as prog2_name, p3.name as prog3_name, s.name as schol_name
    FROM applications a 
    JOIN users u ON a.user_id = u.id 
    JOIN qualifications q ON a.qualification_id = q.id 
    LEFT JOIN programmes p1 ON a.programme_id_1 = p1.id
    LEFT JOIN programmes p2 ON a.programme_id_2 = p2.id
    LEFT JOIN programmes p3 ON a.programme_id_3 = p3.id
    LEFT JOIN scholarships s ON a.scholarship_id = s.id
    ORDER BY a.created_at DESC 
    LIMIT 10
");
$recentApps = $stmt->fetchAll();

// Calendar data
$year = date('Y');
$month = date('n');
$monthName = date('F Y');
$daysInMonth = date('t');
$firstDay = date('w', mktime(0, 0, 0, $month, 1, $year));
$today = date('j');
?>

<div class="flex-between mb-6">
    <div class="page-header" style="margin-bottom:0;">
        <h1>Dashboard</h1>
        <p style="color:var(--green); font-size:0.82rem;">Last updated now</p>
    </div>
</div>

<!-- Stat Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card purple">
        <div class="stat-label">Total Students</div>
        <div class="stat-value"><?= $totalStudents ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Total Applications</div>
        <div class="stat-value"><?= $totalApplications ?></div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Pending Review</div>
        <div class="stat-value"><?= $pendingReview ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Approved</div>
        <div class="stat-value"><?= $approved ?></div>
    </div>
    <div class="stat-card red">
        <div class="stat-label">Rejected</div>
        <div class="stat-value"><?= $rejected ?></div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:24px;">
    <!-- Application Status -->
    <div class="card">
        <div class="flex-between mb-4">
            <h3 style="font-size:1.05rem; font-weight:600;">Application Status</h3>
            <span style="font-size:0.8rem; color:var(--text-muted);"><?= $totalApplications ?> total</span>
        </div>
        <div class="status-row">
            <span class="status-label" style="color:var(--green);">Approved</span>
            <div class="status-bar-wrap">
                <div class="progress-bar"><div class="progress-fill green" style="width:<?= ($approved/$maxStatus)*100 ?>%"></div></div>
            </div>
            <span class="status-count"><?= $approved ?></span>
        </div>
        <div class="status-row">
            <span class="status-label" style="color:var(--yellow);">Processing</span>
            <div class="status-bar-wrap">
                <div class="progress-bar"><div class="progress-fill yellow" style="width:<?= ($processing/$maxStatus)*100 ?>%"></div></div>
            </div>
            <span class="status-count"><?= $processing ?></span>
        </div>
        <div class="status-row">
            <span class="status-label" style="color:var(--blue);">Submitted</span>
            <div class="status-bar-wrap">
                <div class="progress-bar"><div class="progress-fill purple" style="width:<?= ($submitted/$maxStatus)*100 ?>%"></div></div>
            </div>
            <span class="status-count"><?= $submitted ?></span>
        </div>
        <div class="status-row">
            <span class="status-label" style="color:var(--red);">Rejected</span>
            <div class="status-bar-wrap">
                <div class="progress-bar"><div class="progress-fill red" style="width:<?= ($rejected/$maxStatus)*100 ?>%"></div></div>
            </div>
            <span class="status-count"><?= $rejected ?></span>
        </div>
    </div>

    <!-- Calendar -->
    <div class="card">
        <div class="calendar">
            <div class="calendar-header">
                <span><?= $monthName ?></span>
            </div>
            <div class="calendar-grid">
                <span class="day-name">SU</span><span class="day-name">MO</span><span class="day-name">TU</span><span class="day-name">WE</span><span class="day-name">TH</span><span class="day-name">FR</span><span class="day-name">SA</span>
                <?php
                // Empty cells before first day
                for ($i = 0; $i < $firstDay; $i++) echo '<span class="day other-month"></span>';
                // Days
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $cls = $d == $today ? 'day today' : 'day';
                    echo "<span class=\"{$cls}\">{$d}</span>";
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent Applications -->
<div class="card">
    <div class="flex-between mb-4">
        <h3 style="font-size:1.05rem; font-weight:600;">Recent Applications</h3>
        <a href="/admin/applications.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php if (empty($recentApps)): ?>
        <p style="color:var(--text-muted); padding:20px 0; text-align:center;">No applications yet.</p>
    <?php else: ?>
    <div class="table-wrap" style="border:none; box-shadow:none;">
        <table role="table" aria-label="Recent Applications">
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Programmes & Scholarship</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentApps as $app): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($app['full_name']) ?></strong><br>
                        <span style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($app['email']) ?></span>
                    </td>
                    <td>
                        <?php if ($app['prog1_name']): ?>
                            <div style="font-size:0.85rem;">
                                1. <?= htmlspecialchars($app['prog1_name']) ?><br>
                                2. <?= htmlspecialchars($app['prog2_name']) ?><br>
                                3. <?= htmlspecialchars($app['prog3_name']) ?>
                            </div>
                            <?php if ($app['schol_name']): ?>
                                <span style="font-size:0.8rem; color:var(--purple);">+ <?= htmlspecialchars($app['schol_name']) ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color:var(--text-muted); font-style:italic;">Pending Selection</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge badge-<?= statusBadgeClass($app['status']) ?>">
                            <?= ucfirst($app['status']) ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($app['created_at'])) ?></td>
                    <td>
                        <button data-modal-target="modal_<?= $app['id'] ?>" class="btn btn-outline btn-sm">Review</button>
                    </td>
                </tr>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Review Modals -->
<?php if (!empty($recentApps)): ?>
    <?php foreach ($recentApps as $app): ?>
    <div class="modal-overlay" id="modal_<?= $app['id'] ?>">
        <div class="modal">
            <h2>Quick Review #<?= $app['id'] ?></h2>
            <p><strong>Student:</strong> <?= htmlspecialchars($app['full_name']) ?></p>
            <p><strong>Current Status:</strong> <?= ucfirst($app['status']) ?></p>
            <form method="POST" style="margin-top:20px;">
                <?= csrfField() ?>
                <input type="hidden" name="app_id" value="<?= $app['id'] ?>">
                <div class="form-group">
                    <label class="form-label">Admin Notes</label>
                    <textarea name="admin_notes" class="form-input" rows="2" placeholder="Add notes..."><?= htmlspecialchars($app['admin_notes'] ?? '') ?></textarea>
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
                <button data-modal-close class="btn btn-outline btn-sm">Cancel</button>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
