<?php
declare(strict_types=1);
/**
 * Admin Dashboard - Analytics and Eligibility Checks Overview
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

require_once __DIR__ . '/admin_header.php';
$db = getDB();

// Analytics Stats
$totalStudents = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$totalChecks = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$highFitCount = $db->query("SELECT COUNT(DISTINCT application_id) FROM eligibility_results WHERE fit_percentage >= 75")->fetchColumn();

// Recent Eligibility Checks (last 10)
$stmt = $db->query("
    SELECT a.id, a.created_at, u.full_name, u.email, q.qual_type
    FROM applications a 
    JOIN users u ON a.user_id = u.id 
    JOIN qualifications q ON a.qualification_id = q.id 
    ORDER BY a.created_at DESC 
    LIMIT 10
");
$recentChecks = $stmt->fetchAll();

// Chart View Filter
$chartView = $_GET['chart_view'] ?? 'daily';
if (!in_array($chartView, ['daily', 'monthly', 'yearly'])) {
    $chartView = 'daily';
}

$dates = [];
$highFitData = [];
$lowFitData = [];
$dataMap = [];

if ($chartView === 'monthly') {
    $chartTitle = "Eligibility Checks (Last 6 Months)";
    $chartDataQuery = "
        SELECT 
            DATE_FORMAT(a.created_at, '%Y-%m') as check_date,
            SUM(CASE WHEN (SELECT MAX(fit_percentage) FROM eligibility_results er WHERE er.application_id = a.id) >= 75 THEN 1 ELSE 0 END) as high_fit,
            SUM(CASE WHEN (SELECT MAX(fit_percentage) FROM eligibility_results er WHERE er.application_id = a.id) < 75 OR (SELECT MAX(fit_percentage) FROM eligibility_results er WHERE er.application_id = a.id) IS NULL THEN 1 ELSE 0 END) as low_fit
        FROM applications a
        WHERE a.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(a.created_at, '%Y-%m')
        ORDER BY check_date ASC
    ";
    $chartDataStmt = $db->query($chartDataQuery);
    foreach ($chartDataStmt->fetchAll() as $row) {
        $dataMap[$row['check_date']] = $row;
    }
    for ($i = 5; $i >= 0; $i--) {
        $dt = new DateTime("first day of -$i months");
        $dateStr = $dt->format('Y-m');
        $dates[] = $dt->format('M Y');
        $highFitData[] = isset($dataMap[$dateStr]) ? (int)$dataMap[$dateStr]['high_fit'] : 0;
        $lowFitData[] = isset($dataMap[$dateStr]) ? (int)$dataMap[$dateStr]['low_fit'] : 0;
    }
} elseif ($chartView === 'yearly') {
    $chartTitle = "Eligibility Checks (Last 5 Years)";
    $chartDataQuery = "
        SELECT 
            YEAR(a.created_at) as check_date,
            SUM(CASE WHEN (SELECT MAX(fit_percentage) FROM eligibility_results er WHERE er.application_id = a.id) >= 75 THEN 1 ELSE 0 END) as high_fit,
            SUM(CASE WHEN (SELECT MAX(fit_percentage) FROM eligibility_results er WHERE er.application_id = a.id) < 75 OR (SELECT MAX(fit_percentage) FROM eligibility_results er WHERE er.application_id = a.id) IS NULL THEN 1 ELSE 0 END) as low_fit
        FROM applications a
        WHERE a.created_at >= DATE_SUB(CURDATE(), INTERVAL 4 YEAR)
        GROUP BY YEAR(a.created_at)
        ORDER BY check_date ASC
    ";
    $chartDataStmt = $db->query($chartDataQuery);
    foreach ($chartDataStmt->fetchAll() as $row) {
        $dataMap[$row['check_date']] = $row;
    }
    for ($i = 4; $i >= 0; $i--) {
        $dt = new DateTime("-$i years");
        $dateStr = $dt->format('Y');
        $dates[] = $dt->format('Y');
        $highFitData[] = isset($dataMap[$dateStr]) ? (int)$dataMap[$dateStr]['high_fit'] : 0;
        $lowFitData[] = isset($dataMap[$dateStr]) ? (int)$dataMap[$dateStr]['low_fit'] : 0;
    }
} else {
    // default: daily
    $chartTitle = "Eligibility Checks (Last 14 Days)";
    $chartDataQuery = "
        SELECT 
            DATE(a.created_at) as check_date,
            SUM(CASE WHEN (SELECT MAX(fit_percentage) FROM eligibility_results er WHERE er.application_id = a.id) >= 75 THEN 1 ELSE 0 END) as high_fit,
            SUM(CASE WHEN (SELECT MAX(fit_percentage) FROM eligibility_results er WHERE er.application_id = a.id) < 75 OR (SELECT MAX(fit_percentage) FROM eligibility_results er WHERE er.application_id = a.id) IS NULL THEN 1 ELSE 0 END) as low_fit
        FROM applications a
        WHERE a.created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
        GROUP BY DATE(a.created_at)
        ORDER BY check_date ASC
    ";
    $chartDataStmt = $db->query($chartDataQuery);
    foreach ($chartDataStmt->fetchAll() as $row) {
        $dataMap[$row['check_date']] = $row;
    }
    for ($i = 13; $i >= 0; $i--) {
        $dt = new DateTime("-$i days");
        $dateStr = $dt->format('Y-m-d');
        $dates[] = $dt->format('d M');
        $highFitData[] = isset($dataMap[$dateStr]) ? (int)$dataMap[$dateStr]['high_fit'] : 0;
        $lowFitData[] = isset($dataMap[$dateStr]) ? (int)$dataMap[$dateStr]['low_fit'] : 0;
    }
}

?>

<div class="flex-between mb-6">
    <div class="page-header" style="margin-bottom:0;">
        <h1>Platform Analytics</h1>
        <p style="color:var(--green); font-size:0.82rem;">Last updated now</p>
    </div>
</div>

<!-- Stat Cards -->
<div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
    <div class="stat-card purple">
        <div class="stat-label">Total Registered Students</div>
        <div class="stat-value"><?= $totalStudents ?></div>
    </div>
    <div class="stat-card blue">
        <div class="stat-label">Total Eligibility Checks</div>
        <div class="stat-value"><?= $totalChecks ?></div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">High-Fit Candidates (>=75%)</div>
        <div class="stat-value"><?= $highFitCount ?></div>
    </div>
</div>

<div class="grid-2" style="margin-bottom:24px;">
    <!-- Recent Eligibility Checks -->
    <div class="card">
        <div class="flex-between mb-4">
            <h3 style="font-size:1.05rem; font-weight:600;">Recent Eligibility Checks</h3>
            <a href="/admin/applications.php" class="btn btn-outline btn-sm">View All History</a>
        </div>
        <?php if (empty($recentChecks)): ?>
            <p style="color:var(--text-muted); padding:20px 0; text-align:center;">No checks performed yet.</p>
        <?php else: ?>
        <div class="table-wrap" style="border:none; box-shadow:none;">
            <table role="table" aria-label="Recent Checks">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Qualification</th>
                        <th>Date Checked</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentChecks as $check): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($check['full_name']) ?></strong><br>
                            <span style="font-size:0.8rem; color:var(--text-muted);"><?= htmlspecialchars($check['email']) ?></span>
                        </td>
                        <td>
                            <span class="badge badge-outline"><?= htmlspecialchars($check['qual_type']) ?></span>
                        </td>
                        <td><?= date('d M Y', strtotime($check['created_at'])) ?></td>
                        <td>
                            <a href="/admin/student-results.php?id=<?= $check['id'] ?>" class="btn btn-outline btn-sm">View Results</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Chart -->
    <div class="card">
        <div class="flex-between mb-4">
            <h3 style="font-size:1.05rem; font-weight:600; margin:0;"><?= htmlspecialchars($chartTitle) ?></h3>
            <form method="GET" style="margin:0;" id="chartViewForm">
                <select name="chart_view" id="chartViewSelect" class="form-select" style="padding:4px 28px 4px 8px; font-size:0.85rem;">
                    <option value="daily" <?= $chartView === 'daily' ? 'selected' : '' ?>>Daily</option>
                    <option value="monthly" <?= $chartView === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                    <option value="yearly" <?= $chartView === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                </select>
            </form>
        </div>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="eligibilityChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js" nonce="<?= $GLOBALS['csp_nonce'] ?? '' ?>"></script>
<script nonce="<?= $GLOBALS['csp_nonce'] ?? '' ?>">
document.addEventListener('DOMContentLoaded', function() {
    // Attach event listener for the dropdown to comply with CSP
    document.getElementById('chartViewSelect').addEventListener('change', function() {
        document.getElementById('chartViewForm').submit();
    });

    const ctx = document.getElementById('eligibilityChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [
                {
                    label: 'High-Fit (>= 75%)',
                    data: <?= json_encode($highFitData) ?>,
                    backgroundColor: '#10b981', // green
                    borderRadius: 4
                },
                {
                    label: 'Low-Fit (< 75%)',
                    data: <?= json_encode($lowFitData) ?>,
                    backgroundColor: '#f59e0b', // yellow/orange
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: {
                    stacked: true,
                },
                y: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            },
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
