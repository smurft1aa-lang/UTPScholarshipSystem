<?php
/**
 * Admin Dashboard
 * Overview with stats, application status, calendar
 */
require_once __DIR__ . '/admin_header.php';

$db = getDB();

// Stats
$totalProgrammes = $db->query("SELECT COUNT(*) FROM programmes WHERE is_active = 1")->fetchColumn();
$totalApplications = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$pendingApproval = $db->query("SELECT COUNT(*) FROM applications WHERE status = 'submitted' OR status = 'processing'")->fetchColumn();
$activeScholarships = $db->query("SELECT COUNT(*) FROM scholarships WHERE is_active = 1 AND (end_date IS NULL OR end_date >= CURDATE())")->fetchColumn();

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

// Recent applications
$stmt = $db->query("SELECT a.*, u.full_name, u.email, q.qual_type FROM applications a JOIN users u ON a.user_id = u.id JOIN qualifications q ON a.qualification_id = q.id ORDER BY a.created_at DESC LIMIT 5");
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
<div class="stats-grid">
    <div class="stat-card purple">
        <div class="stat-label">Total Programmes</div>
        <div class="stat-value"><?= $totalProgrammes ?></div>
        <div class="stat-detail"><?= $totalProgrammes ?> active</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Total Applications</div>
        <div class="stat-value"><?= $totalApplications ?></div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Pending Approval</div>
        <div class="stat-value"><?= $pendingApproval ?></div>
        <div class="stat-detail" style="color:var(--orange);">Review</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Active Scholarships</div>
        <div class="stat-value"><?= $activeScholarships ?></div>
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
        <table>
            <thead>
                <tr>
                    <th>Student</th>
                    <th>Qualification</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentApps as $app): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($app['full_name']) ?></strong><br>
                        <span style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($app['email']) ?></span>
                    </td>
                    <td><?= htmlspecialchars($app['qual_type']) ?></td>
                    <td>
                        <span class="badge badge-<?= $app['status'] === 'approved' ? 'green' : ($app['status'] === 'rejected' ? 'red' : ($app['status'] === 'processing' ? 'yellow' : 'blue')) ?>">
                            <?= ucfirst($app['status']) ?>
                        </span>
                    </td>
                    <td><?= date('d M Y', strtotime($app['created_at'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
