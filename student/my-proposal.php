<?php
/**
 * Student Proposal Page
 * Auto-generated sponsorship proposal document
 */
require_once __DIR__ . '/../includes/auth.php';
requireStudent();

$db = getDB();
$userId = $_SESSION['user_id'];
$appId = intval($_GET['id'] ?? 0);

// Get application
$stmt = $db->prepare("SELECT a.*, q.qual_type, u.full_name, u.email, u.ic_number, u.phone FROM applications a JOIN qualifications q ON a.qualification_id = q.id JOIN users u ON a.user_id = u.id WHERE a.id = ? AND a.user_id = ?");
$stmt->execute([$appId, $userId]);
$application = $stmt->fetch();

if (!$application) {
    header('Location: /student/results.php');
    exit;
}

// Get grades
$stmt = $db->prepare("SELECT subject, grade FROM grades WHERE qualification_id = ?");
$stmt->execute([$application['qualification_id']]);
$grades = $stmt->fetchAll();

// Get eligible results with fee data
$stmt = $db->prepare("SELECT er.*, p.name as programme_name, p.category, p.duration, p.foundation_fee, p.undergraduate_fee FROM eligibility_results er JOIN programmes p ON er.programme_id = p.id WHERE er.application_id = ? AND er.eligible = 1 ORDER BY er.fit_percentage DESC");
$stmt->execute([$appId]);
$eligible = $stmt->fetchAll();

// Get fee structure
$fees = $db->query("SELECT id, fee_type, description, amount_min, amount_max, frequency, notes FROM fee_structure ORDER BY id")->fetchAll();

$pageTitle = 'Eligibility Proposal — UTP Scholarship System';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:32px; padding-bottom:48px;">
    <div class="flex-between mb-6 no-print">
        <div class="page-header" style="margin-bottom:0;">
            <h1>Eligibility Proposal</h1>
            <p>Auto-generated document for your application</p>
        </div>
        <button data-action="print" class="btn btn-orange btn-sm">Print / Save PDF</button>
    </div>

    <!-- Printable Proposal Document -->
    <div class="card" style="max-width:800px; margin:0 auto; padding:40px;">
        <div style="text-align:center; margin-bottom:32px; border-bottom:2px solid var(--text); padding-bottom:20px;">
            <h2 style="font-size:1.4rem; font-weight:700; text-transform:uppercase; letter-spacing:1px;">UTP Foundation Programme</h2>
            <h3 style="font-size:1rem; font-weight:600; color:var(--text-secondary);">Eligibility Assessment Report</h3>
            <p style="font-size:0.82rem; color:var(--text-muted); margin-top:8px;">Generated: <?= date('d F Y, h:i A') ?></p>
        </div>

        <!-- Student Information -->
        <h4 style="font-size:0.9rem; font-weight:700; text-transform:uppercase; margin-bottom:12px; color:var(--orange);">Student Information</h4>
        <div class="table-wrap" style="margin-bottom:24px;">
            <table>
                <tr><td style="width:180px; font-weight:600;">Full Name</td><td><?= htmlspecialchars($application['full_name']) ?></td></tr>
                <tr><td style="font-weight:600;">IC Number</td><td><?= htmlspecialchars($application['ic_number']) ?></td></tr>
                <tr><td style="font-weight:600;">Email</td><td><?= htmlspecialchars($application['email']) ?></td></tr>
                <tr><td style="font-weight:600;">Phone</td><td><?= htmlspecialchars($application['phone']) ?></td></tr>
                <tr><td style="font-weight:600;">Qualification</td><td><?= htmlspecialchars($application['qual_type']) ?></td></tr>
                <tr><td style="font-weight:600;">Application Status</td><td><span class="badge badge-<?= $application['status'] === 'approved' ? 'green' : ($application['status'] === 'rejected' ? 'red' : 'blue') ?>"><?= ucfirst($application['status']) ?></span></td></tr>
            </table>
        </div>

        <!-- Academic Results -->
        <h4 style="font-size:0.9rem; font-weight:700; text-transform:uppercase; margin-bottom:12px; color:var(--orange);">Academic Results</h4>
        <div class="table-wrap" style="margin-bottom:24px;">
            <table>
                <thead><tr><th>Subject</th><th>Grade</th></tr></thead>
                <tbody>
                <?php foreach ($grades as $g): ?>
                    <tr><td><?= htmlspecialchars($g['subject']) ?></td><td><strong><?= htmlspecialchars($g['grade']) ?></strong></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Eligible Programmes -->
        <h4 style="font-size:0.9rem; font-weight:700; text-transform:uppercase; margin-bottom:12px; color:var(--orange);">Eligible Programmes (<?= count($eligible) ?>)</h4>
        <div class="table-wrap" style="margin-bottom:24px;">
            <table>
                <thead><tr><th>Programme</th><th>Category</th><th>Duration</th><th>Foundation Fee</th><th>Undergraduate Fee</th><th>Fit</th></tr></thead>
                <tbody>
                <?php foreach ($eligible as $e): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($e['programme_name']) ?></strong></td>
                        <td><?= htmlspecialchars($e['category']) ?></td>
                        <td><?= htmlspecialchars($e['duration']) ?></td>
                        <td>RM <?= number_format($e['foundation_fee']) ?></td>
                        <td>RM <?= number_format($e['undergraduate_fee']) ?></td>
                        <td><strong><?= $e['fit_percentage'] ?>%</strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Fee Structure -->
        <h4 style="font-size:0.9rem; font-weight:700; text-transform:uppercase; margin-bottom:12px; color:var(--orange);">Fee Structure (Effective May 2026)</h4>
        <div class="table-wrap" style="margin-bottom:24px;">
            <table>
                <thead><tr><th>Fee Type</th><th>Amount</th><th>Frequency</th><th>Notes</th></tr></thead>
                <tbody>
                <?php foreach ($fees as $f): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($f['fee_type']) ?></strong><br><span style="font-size:0.78rem; color:var(--text-muted);"><?= htmlspecialchars($f['description']) ?></span></td>
                        <td>RM <?= number_format($f['amount_min']) ?><?= $f['amount_max'] ? ' - RM ' . number_format($f['amount_max']) : '' ?></td>
                        <td><?= ucfirst($f['frequency']) ?></td>
                        <td style="font-size:0.82rem;"><?= htmlspecialchars($f['notes'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="margin-top:40px; padding-top:20px; border-top:1px solid var(--border); font-size:0.82rem; color:var(--text-muted); text-align:center;">
            This report was auto-generated by the UTP Scholarship & Course Eligibility System.
            Application ID: #<?= $appId ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
