<?php
declare(strict_types=1);
/**
 * Admin Structured Performance Reports
 * Application stats, programme popularity, scholarship distribution, grade trends
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

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

// Pagination for programme stats
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Count total programmes with stats
$countStmt = $db->prepare("
    SELECT COUNT(DISTINCT p.id)
    FROM eligibility_results er
    JOIN programmes p ON er.programme_id = p.id
    JOIN applications a ON er.application_id = a.id
    WHERE a.created_at BETWEEN ? AND ?
");
$countStmt->execute([$dateFrom, $dateTo . ' 23:59:59']);
$totalProgRecords = (int) $countStmt->fetchColumn();
$totalProgPages = (int) ceil($totalProgRecords / $perPage);

$stmt = $db->prepare("
    SELECT p.name, p.category,
           (SELECT COUNT(a2.id) FROM applications a2 WHERE (a2.programme_id_1 = p.id OR a2.programme_id_2 = p.id OR a2.programme_id_3 = p.id) AND a2.created_at BETWEEN ? AND ?) as applied_count,
           SUM(CASE WHEN er.eligible = 1 THEN 1 ELSE 0 END) as eligible_count,
           COUNT(er.id) as total_students,
           ROUND(AVG(er.fit_percentage), 1) as avg_fit
    FROM eligibility_results er
    JOIN programmes p ON er.programme_id = p.id
    JOIN applications a ON er.application_id = a.id
    WHERE a.created_at BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY applied_count DESC, eligible_count DESC
    LIMIT ? OFFSET ?
");
$stmtParams = [$dateFrom, $dateTo . ' 23:59:59', $dateFrom, $dateTo . ' 23:59:59'];
$paramIdx = 1;
foreach ($stmtParams as $p) {
    $stmt->bindValue($paramIdx++, $p);
}
$stmt->bindValue($paramIdx++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($paramIdx, $offset, PDO::PARAM_INT);
$stmt->execute();
$progStats = $stmt->fetchAll();

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

// Handle AI Insights Generation
$aiInsights = null;
$aiError = null;
if (isset($_GET['generate_insights']) && $_GET['generate_insights'] === '1') {
    try {
        $generator = new \UTP\Services\ProposalGenerator($db);
        $reportData = [
            'total_applications' => $totalApps,
            'application_stats' => $appStats,
            'programme_stats' => array_slice($progStats, 0, 10), // Limit to top 10 to fit context window
            'scholarship_distribution' => $schDist,
            'monthly_trend' => $monthlyTrend
        ];
        $aiInsights = $generator->generateReportInsights($dateFrom, $dateTo, $reportData);
    } catch (Exception $e) {
        $aiError = "Failed to generate AI insights: " . $e->getMessage();
    }
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Reconstruct date strings from parsed timestamps to break taint chain.
    // strtotime() + date() produces a brand-new string that Psalm considers untainted.
    $parsedFrom = strtotime($dateFrom);
    $parsedTo = strtotime($dateTo);
    /** @psalm-taint-escape header */
    $safeDateFrom = $parsedFrom !== false ? date('Y-m-d', $parsedFrom) : 'unknown';
    /** @psalm-taint-escape header */
    $safeDateTo = $parsedTo !== false ? date('Y-m-d', $parsedTo) : 'unknown';
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="utp_performance_report_' . $safeDateFrom . '_to_' . $safeDateTo . '.csv"');
    
    $out = fopen('php://output', 'w');
    
    fputcsv($out, ['REPORT SUMMARY', "Period: $dateFrom to $dateTo"]);
    fputcsv($out, []);
    
    // 1. Application Stats
    fputcsv($out, ['APPLICATION STATISTICS']);
    fputcsv($out, ['Status', 'Count']);
    fputcsv($out, ['Total Applications', $totalApps]);
    fputcsv($out, ['Approved', $appStats['approved'] ?? 0]);
    fputcsv($out, ['Processing', $appStats['processing'] ?? 0]);
    fputcsv($out, ['Submitted', $appStats['submitted'] ?? 0]);
    fputcsv($out, ['Rejected', $appStats['rejected'] ?? 0]);
    fputcsv($out, []);
    
    // 2. Programme Statistics
    fputcsv($out, ['PROGRAMME STATISTICS']);
    fputcsv($out, ['Programme Name', 'Category', 'Applied Students', 'Eligible Students', 'Total Students', 'Average AI Fit %']);
    foreach ($progStats as $ps) {
        fputcsv($out, [
            $ps['name'], 
            $ps['category'], 
            $ps['applied_count'], 
            $ps['eligible_count'], 
            $ps['total_students'], 
            $ps['avg_fit'] . '%'
        ]);
    }
    fputcsv($out, []);
    
    // 3. Scholarship Distribution
    fputcsv($out, ['SCHOLARSHIP DISTRIBUTION']);
    fputcsv($out, ['Scholarship Name', 'Potential Candidates', 'Budget Min', 'Budget Max']);
    foreach ($schDist as $sd) {
        fputcsv($out, [
            $sd['name'], 
            $sd['potential_students'], 
            $sd['budget_min'], 
            $sd['budget_max']
        ]);
    }
    fputcsv($out, []);
    
    fclose($out);
    exit;
}

// ---------------------------------------------------------
// HTML RENDER BLOCK
// ---------------------------------------------------------

require_once __DIR__ . '/admin_header.php';
?>

<div class="flex-between mb-6 no-print">
    <div class="page-header" style="margin-bottom:0;">
        <h1>Performance Reports</h1>
        <p>Structured reports on applications, programmes, and scholarships.</p>
    </div>
    <div class="flex gap-2">
        <a href="?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>&generate_insights=1" class="btn btn-outline btn-sm">
            <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24" style="margin-right:4px;"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-9 14l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/></svg>
            AI Insights
        </a>
        <a href="?from=<?= urlencode($dateFrom) ?>&to=<?= urlencode($dateTo) ?>&export=csv" class="btn btn-outline btn-sm">Export CSV</a>
        <button data-action="print" class="btn btn-purple btn-sm">Print Report</button>
    </div>
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

<!-- AI Insights Panel -->
<?php if ($aiError): ?>
    <div class="alert alert-danger mb-4 no-print"><?= htmlspecialchars($aiError) ?></div>
<?php endif; ?>
<?php if ($aiInsights): ?>
    <div class="card mb-6" style="border-left: 4px solid var(--utp-teal); background: linear-gradient(to right, rgba(0, 161, 177, 0.05), transparent);">
        <div class="flex-between mb-4">
            <h3 style="font-size:1.1rem; font-weight:600; color:var(--utp-navy); margin:0; display:flex; align-items:center; gap:8px;">
                <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2L2 22h20L12 2zm0 3.8l6.3 12.6H5.7L12 5.8zm-1 5.2h2v4h-2v-4zm0 6h2v2h-2v-2z"/></svg>
                AI Performance Insights
            </h3>
        </div>
        <div class="raw-html-content" style="font-size:0.95rem; line-height:1.6;">
            <?= \UTP\Security\InputSanitizer::sanitizeHtml($aiInsights) ?>
        </div>
    </div>
<?php endif; ?>

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

<!-- Programme Statistics -->
<div class="card mb-6">
    <h3 style="font-size:1.05rem; font-weight:600; margin-bottom:16px;">Programme Statistics</h3>
    <?php if (empty($progStats)): ?>
        <p style="color:var(--text-muted);">No data available for this period.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Programme</th>
                    <th>Category</th>
                    <th>Applied Students</th>
                    <th>Eligible Students</th>
                    <th>Total Students</th>
                    <th>Avg Fit</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($progStats as $ps): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($ps['name']) ?></strong></td>
                    <td><span class="badge badge-purple"><?= htmlspecialchars($ps['category']) ?></span></td>
                    <td><?= $ps['applied_count'] ?></td>
                    <td><?= $ps['eligible_count'] ?></td>
                    <td><?= $ps['total_students'] ?></td>
                    <td><?= $ps['avg_fit'] ?>%</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <?php if ($totalProgPages > 1): ?>
    <div style="display:flex; justify-content:center; align-items:center; gap:8px; margin-top:16px;">
        <?php
        $paginationQS = '';
        if ($dateFrom) $paginationQS .= '&from=' . urlencode($dateFrom);
        if ($dateTo) $paginationQS .= '&to=' . urlencode($dateTo);
        ?>
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 . $paginationQS ?>" class="btn btn-outline btn-sm">Previous</a>
        <?php endif; ?>
        <span style="font-size:0.9rem; color:var(--text-secondary);">Page <?= $page ?> of <?= $totalProgPages ?></span>
        <?php if ($page < $totalProgPages): ?>
            <a href="?page=<?= $page + 1 . $paginationQS ?>" class="btn btn-outline btn-sm">Next</a>
        <?php endif; ?>
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
