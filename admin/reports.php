<?php
/**
 * Admin Structured Performance Reports
 * Application stats, programme popularity, scholarship distribution, grade trends
 */
require_once __DIR__ . '/admin_header.php';

$db = getDB();

// Date range filter
$dateFrom = $_GET['from'] ?? date('Y-01-01');
$dateTo = $_GET['to'] ?? date('Y-12-31');

// Application statistics
$stmt = $db->prepare("SELECT status, COUNT(*) as cnt FROM applications WHERE created_at BETWEEN ? AND ? GROUP BY status");
$stmt->execute([$dateFrom, $dateTo . ' 23:59:59']);
$appStats = [];
while ($row = $stmt->fetch()) $appStats[$row['status']] = $row['cnt'];
$totalApps = array_sum($appStats);

// Programme popularity
$stmt = $db->prepare("
    SELECT p.name, p.category, COUNT(er.id) as app_count,
           SUM(CASE WHEN er.eligible = 1 THEN 1 ELSE 0 END) as eligible_count,
           ROUND(AVG(er.fit_percentage), 1) as avg_fit
    FROM eligibility_results er
    JOIN programmes p ON er.programme_id = p.id
    JOIN applications a ON er.application_id = a.id
    WHERE a.created_at BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY app_count DESC
");
$stmt->execute([$dateFrom, $dateTo . ' 23:59:59']);
$progPopularity = $stmt->fetchAll();

// Scholarship distribution
$stmt = $db->prepare("
    SELECT s.name, COUNT(DISTINCT a.user_id) as potential_students,
           s.budget_min, s.budget_max
    FROM scholarships s
    JOIN scholarship_programme sp ON s.id = sp.scholarship_id
    JOIN eligibility_results er ON er.programme_id = sp.programme_id AND er.eligible = 1
    JOIN applications a ON er.application_id = a.id
    WHERE a.created_at BETWEEN ? AND ? AND s.is_active = 1
    GROUP BY s.id
    ORDER BY potential_students DESC
");
$stmt->execute([$dateFrom, $dateTo . ' 23:59:59']);
$schDist = $stmt->fetchAll();

// Grade trends by qualification type
$stmt = $db->prepare("
    SELECT q.qual_type, g.subject, g.grade, COUNT(*) as cnt
    FROM grades g
    JOIN qualifications q ON g.qualification_id = q.id
    JOIN applications a ON a.qualification_id = q.id
    WHERE a.created_at BETWEEN ? AND ?
    GROUP BY q.qual_type, g.subject, g.grade
    ORDER BY q.qual_type, g.subject, cnt DESC
");
$stmt->execute([$dateFrom, $dateTo . ' 23:59:59']);
$gradeData = [];
while ($row = $stmt->fetch()) {
    $gradeData[$row['qual_type']][$row['subject']][] = ['grade' => $row['grade'], 'count' => $row['cnt']];
}

// Monthly trend
$stmt = $db->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as cnt
    FROM applications
    WHERE created_at BETWEEN ? AND ?
    GROUP BY month
    ORDER BY month
");
$stmt->execute([$dateFrom, $dateTo . ' 23:59:59']);
$monthlyTrend = $stmt->fetchAll();
?>

<div class="flex-between mb-6 no-print">
    <div class="page-header" style="margin-bottom:0;">
        <h1>Performance Reports</h1>
        <p>Structured reports on applications, programmes, and scholarships.</p>
    </div>
    <button onclick="window.print()" class="btn btn-purple btn-sm">Print Report</button>
</div>

<!-- Date Range Filter -->
<div class="card mb-6 no-print" style="padding:16px 20px;">
    <form method="GET" class="flex" style="gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-input admin-focus" value="<?= htmlspecialchars($dateFrom) ?>" style="width:auto;">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-input admin-focus" value="<?= htmlspecialchars($dateTo) ?>" style="width:auto;">
        </div>
        <button type="submit" class="btn btn-purple btn-sm">Generate</button>
    </form>
</div>

<!-- Report Header (for print) -->
<div class="print-only" style="text-align:center; margin-bottom:24px;">
    <h2 style="font-size:1.3rem;">UTP System - Performance Report</h2>
    <p style="color:var(--text-muted);">Period: <?= date('d M Y', strtotime($dateFrom)) ?> to <?= date('d M Y', strtotime($dateTo)) ?></p>
    <p style="color:var(--text-muted);">Generated: <?= date('d F Y, h:i A') ?></p>
</div>

<!-- Application Statistics -->
<div class="card mb-6">
    <h3 style="font-size:1.05rem; font-weight:600; margin-bottom:16px;">Application Statistics</h3>
    <div class="stats-grid">
        <div class="stat-card purple">
            <div class="stat-label">Total Applications</div>
            <div class="stat-value"><?= $totalApps ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Approved</div>
            <div class="stat-value"><?= $appStats['approved'] ?? 0 ?></div>
            <div class="stat-detail"><?= $totalApps ? round((($appStats['approved'] ?? 0)/$totalApps)*100, 1) : 0 ?>% rate</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?= ($appStats['submitted'] ?? 0) + ($appStats['processing'] ?? 0) ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-label">Rejected</div>
            <div class="stat-value"><?= $appStats['rejected'] ?? 0 ?></div>
            <div class="stat-detail"><?= $totalApps ? round((($appStats['rejected'] ?? 0)/$totalApps)*100, 1) : 0 ?>% rate</div>
        </div>
    </div>

    <?php if (!empty($monthlyTrend)): ?>
    <h4 style="font-size:0.9rem; font-weight:600; margin-top:20px; margin-bottom:10px;">Monthly Trend</h4>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Month</th><th>Applications</th><th>Trend</th></tr></thead>
            <tbody>
            <?php $maxMonthly = max(array_column($monthlyTrend, 'cnt')); ?>
            <?php foreach ($monthlyTrend as $m): ?>
                <tr>
                    <td><?= date('F Y', strtotime($m['month'] . '-01')) ?></td>
                    <td><strong><?= $m['cnt'] ?></strong></td>
                    <td style="width:40%;">
                        <div class="progress-bar"><div class="progress-fill purple" style="width:<?= ($m['cnt']/$maxMonthly)*100 ?>%"></div></div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Programme Popularity -->
<div class="card mb-6">
    <h3 style="font-size:1.05rem; font-weight:600; margin-bottom:16px;">Programme Popularity</h3>
    <?php if (empty($progPopularity)): ?>
        <p style="color:var(--text-muted);">No data available for this period.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Programme</th><th>Category</th><th>Applications</th><th>Eligible</th><th>Avg Fit</th></tr></thead>
            <tbody>
            <?php foreach ($progPopularity as $pp): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($pp['name']) ?></strong></td>
                    <td><span class="badge badge-purple"><?= htmlspecialchars($pp['category']) ?></span></td>
                    <td><?= $pp['app_count'] ?></td>
                    <td><?= $pp['eligible_count'] ?></td>
                    <td><?= $pp['avg_fit'] ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Scholarship Distribution -->
<div class="card mb-6">
    <h3 style="font-size:1.05rem; font-weight:600; margin-bottom:16px;">Scholarship Distribution</h3>
    <?php if (empty($schDist)): ?>
        <p style="color:var(--text-muted);">No data available for this period.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Scholarship</th><th>Potential Students</th><th>Budget Range</th></tr></thead>
            <tbody>
            <?php foreach ($schDist as $sd): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($sd['name']) ?></strong></td>
                    <td><?= $sd['potential_students'] ?></td>
                    <td>RM <?= number_format($sd['budget_min']) ?> - RM <?= number_format($sd['budget_max']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Grade Trends -->
<?php if (!empty($gradeData)): ?>
<div class="card mb-6">
    <h3 style="font-size:1.05rem; font-weight:600; margin-bottom:16px;">Grade Trends</h3>
    <?php foreach ($gradeData as $qualType => $subjects): ?>
        <h4 style="font-size:0.9rem; font-weight:600; margin:16px 0 10px; color:var(--purple);"><?= htmlspecialchars($qualType) ?></h4>
        <div class="table-wrap mb-4">
            <table>
                <thead><tr><th>Subject</th><th>Most Common Grade</th><th>Distribution</th></tr></thead>
                <tbody>
                <?php foreach ($subjects as $subject => $gradeList): ?>
                    <tr>
                        <td><?= htmlspecialchars($subject) ?></td>
                        <td><strong><?= htmlspecialchars($gradeList[0]['grade']) ?></strong> (<?= $gradeList[0]['count'] ?>)</td>
                        <td style="font-size:0.82rem; color:var(--text-secondary);">
                            <?php
                            $parts = [];
                            foreach (array_slice($gradeList, 0, 5) as $g) {
                                $parts[] = $g['grade'] . ':' . $g['count'];
                            }
                            echo implode(', ', $parts);
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
