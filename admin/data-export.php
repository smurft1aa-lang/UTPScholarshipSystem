<?php
/**
 * Admin: Data Export & Analytics
 *
 * Provides downloadable CSV exports for students, applications,
 * scholarships, and monthly acceptance analytics.
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$db = getDB();
$exporter = new \UTP\Services\DataExporter($db);

// Handle export download requests
if (isset($_GET['download'])) {
    $type = $_GET['download'];
    $dateFrom = $_GET['from'] ?? null;
    $dateTo = $_GET['to'] ?? null;

    switch ($type) {
        case 'students':
            $csv = $exporter->exportStudents();
            \UTP\Services\DataExporter::sendDownload($csv, 'students_export_' . date('Y-m-d') . '.csv');
            break;

        case 'applications':
            $csv = $exporter->exportApplications($dateFrom, $dateTo);
            \UTP\Services\DataExporter::sendDownload($csv, 'applications_export_' . date('Y-m-d') . '.csv');
            break;

        case 'scholarships':
            $csv = $exporter->exportScholarships();
            \UTP\Services\DataExporter::sendDownload($csv, 'scholarships_export_' . date('Y-m-d') . '.csv');
            break;

        case 'analytics':
            $year = $_GET['year'] ?? date('Y');
            $csv = $exporter->exportMonthlyAnalytics($year);
            \UTP\Services\DataExporter::sendDownload($csv, 'monthly_analytics_' . $year . '.csv');
            break;
    }
}

// Stats for display
$studentCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'student'")->fetchColumn();
$appCount = $db->query("SELECT COUNT(*) FROM applications")->fetchColumn();
$schCount = $db->query("SELECT COUNT(*) FROM scholarships WHERE is_active = 1")->fetchColumn();

require_once __DIR__ . '/admin_header.php';
?>

<div class="page-header">
    <h1>Data Export & Analytics</h1>
    <p>Download system data as CSV files or generate analytics reports.</p>
</div>

<!-- Quick Stats -->
<div class="stats-grid mb-6">
    <div class="stat-card purple">
        <div class="stat-label">Students</div>
        <div class="stat-value"><?= $studentCount ?></div>
        <div class="stat-detail">Total registered</div>
    </div>
    <div class="stat-card orange">
        <div class="stat-label">Applications</div>
        <div class="stat-value"><?= $appCount ?></div>
        <div class="stat-detail">Total submitted</div>
    </div>
    <div class="stat-card green">
        <div class="stat-label">Scholarships</div>
        <div class="stat-value"><?= $schCount ?></div>
        <div class="stat-detail">Active listings</div>
    </div>
</div>

<!-- Export Cards -->
<div class="grid-2 mb-6">
    <!-- Students Export -->
    <div class="card">
        <h3 style="font-size:1rem; font-weight:600; margin-bottom:8px;">📋 Student List</h3>
        <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:16px;">
            Export all registered students with contact information and verification status.
        </p>
        <a href="?download=students" class="btn btn-purple btn-sm">Download CSV</a>
    </div>

    <!-- Scholarships Export -->
    <div class="card">
        <h3 style="font-size:1rem; font-weight:600; margin-bottom:8px;">🎓 Scholarship Catalog</h3>
        <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:16px;">
            Export all scholarships with budget ranges, types, and eligibility thresholds.
        </p>
        <a href="?download=scholarships" class="btn btn-purple btn-sm">Download CSV</a>
    </div>
</div>

<!-- Applications Export with Date Filter -->
<div class="card mb-6">
    <h3 style="font-size:1rem; font-weight:600; margin-bottom:8px;">📝 Applications Export</h3>
    <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:16px;">
        Export applications with student info, programme choices, and status. Optional date range filter.
    </p>
    <form method="GET" class="flex" style="gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <input type="hidden" name="download" value="applications">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">From</label>
            <input type="date" name="from" class="form-input admin-focus" value="<?= htmlspecialchars(date('Y-01-01')) ?>" style="width:auto;">
        </div>
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">To</label>
            <input type="date" name="to" class="form-input admin-focus" value="<?= htmlspecialchars(date('Y-12-31')) ?>" style="width:auto;">
        </div>
        <button type="submit" class="btn btn-purple btn-sm">Download CSV</button>
    </form>
</div>

<!-- Monthly Analytics -->
<div class="card">
    <h3 style="font-size:1rem; font-weight:600; margin-bottom:8px;">📊 Monthly Acceptance Analytics</h3>
    <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:16px;">
        Monthly breakdown of applications, acceptances, rejections, and approval rates.
    </p>
    <form method="GET" class="flex" style="gap:12px; align-items:flex-end; flex-wrap:wrap;">
        <input type="hidden" name="download" value="analytics">
        <div class="form-group" style="margin-bottom:0;">
            <label class="form-label">Year</label>
            <select name="year" class="form-select admin-focus" style="width:auto;">
                <?php for ($y = (int) date('Y'); $y >= 2024; $y--): ?>
                    <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-purple btn-sm">Download Report</button>
    </form>
</div>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
