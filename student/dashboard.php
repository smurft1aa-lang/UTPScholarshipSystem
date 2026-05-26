<?php
/**
 * Student Dashboard
 */
require_once __DIR__ . '/../includes/init.php';
requireStudent();

$db = getDB();
$userId = $_SESSION['user_id'];

// Get latest qualification
$stmt = $db->prepare("SELECT COUNT(*) FROM qualifications WHERE user_id = ?");
$stmt->execute([$userId]);
$qualCount = $stmt->fetchColumn();

// Get latest application
$stmt = $db->prepare("
    SELECT a.*, q.qual_type,
           p1.name as prog1_name, p2.name as prog2_name, p3.name as prog3_name, s.name as schol_name
    FROM applications a 
    JOIN qualifications q ON a.qualification_id = q.id 
    LEFT JOIN programmes p1 ON a.programme_id_1 = p1.id
    LEFT JOIN programmes p2 ON a.programme_id_2 = p2.id
    LEFT JOIN programmes p3 ON a.programme_id_3 = p3.id
    LEFT JOIN scholarships s ON a.scholarship_id = s.id
    WHERE a.user_id = ? 
    ORDER BY a.created_at DESC 
    LIMIT 1
");
$stmt->execute([$userId]);
$latestApp = $stmt->fetch();

// Get eligible programme count
$eligibleCount = 0;
if ($latestApp) {
    $stmt = $db->prepare("SELECT COUNT(*) FROM eligibility_results WHERE application_id = ? AND eligible = 1");
    $stmt->execute([$latestApp['id']]);
    $eligibleCount = $stmt->fetchColumn();
}

// Get all historical applications
$stmt = $db->prepare("
    SELECT a.*, q.qual_type,
           p1.name as prog1_name, p2.name as prog2_name, p3.name as prog3_name, s.name as schol_name
    FROM applications a 
    JOIN qualifications q ON a.qualification_id = q.id 
    LEFT JOIN programmes p1 ON a.programme_id_1 = p1.id
    LEFT JOIN programmes p2 ON a.programme_id_2 = p2.id
    LEFT JOIN programmes p3 ON a.programme_id_3 = p3.id
    LEFT JOIN scholarships s ON a.scholarship_id = s.id
    WHERE a.user_id = ? 
    ORDER BY a.created_at DESC
");
$stmt->execute([$userId]);
$allApps = $stmt->fetchAll();

// Get document upload status
$stmt = $db->prepare("SELECT doc_type FROM documents WHERE user_id = ?");
$stmt->execute([$userId]);
$uploadedDocs = $stmt->fetchAll(PDO::FETCH_COLUMN);

$requiredDocs = [
    'ic' => 'IC/Passport Scan',
    'certificate' => 'Academic Certificate',
    'photo' => 'Passport Photo'
];

$missingCount = count($requiredDocs) - count($uploadedDocs);

$pageTitle = 'Dashboard — UTP Scholarship System';
require_once __DIR__ . '/../includes/header.php';
?>

<style nonce="<?= $GLOBALS['csp_nonce'] ?? '' ?>">
/* Removed status timeline css */
</style>

<div class="container" style="padding-top:32px; padding-bottom:48px;">
    <div class="page-header">
        <h1>Welcome, <?= htmlspecialchars($currentUser['full_name']) ?></h1>
        <p>Check your eligibility for UTP foundation programmes and scholarships.</p>
    </div>

    <?php if (!isVerified()): ?>
    <div class="alert mb-6" style="background:#fff3cd; color:#856404; border:1px solid #ffeeba; display:flex; justify-content:space-between; align-items:center;">
        <div>
            <strong>Email Verification Required:</strong> Please verify your email to submit applications or check eligibility.
        </div>
        <form method="POST" action="/auth/resend-verification.php" style="margin:0;">
            <?= csrfField() ?>
            <button type="submit" class="btn btn-orange btn-sm">Resend verification email</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card orange">
            <div class="stat-label">Qualifications Entered</div>
            <div class="stat-value"><?= $qualCount ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-label">Eligible Programmes</div>
            <div class="stat-value"><?= $eligibleCount ?></div>
        </div>
        <div class="stat-card blue">
            <div class="stat-label">Last Check Date</div>
            <div class="stat-value" style="font-size:1.2rem;">
                <?php if ($latestApp): ?>
                    <?= date('d M Y', strtotime($latestApp['created_at'])) ?>
                <?php else: ?>
                    <span style="color:var(--text-muted); font-size:0.9rem;">No checks yet</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="grid-3 mb-6">
        <div class="card">
            <h3 style="margin-bottom:10px; font-size:1.05rem;">Check Eligibility</h3>
            <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:18px;">
                Enter your results to find out which foundation programmes and scholarships you qualify for.
            </p>
            <a href="/student/check-eligibility.php" class="btn btn-orange">Check Now</a>
        </div>
        <div class="card">
            <h3 style="margin-bottom:10px; font-size:1.05rem;">View Results</h3>
            <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:18px;">
                View your eligibility results, recommended programmes, and matching scholarships.
            </p>
            <a href="/student/results.php" class="btn btn-outline">View Results</a>
        </div>
        <div class="card">
            <h3 style="margin-bottom:10px; font-size:1.05rem;">Document Vault (Optional)</h3>
            <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:18px;">
                Store your official documents here so you're ready when applying at the official UTP portal.
                <?php if ($missingCount > 0): ?>
                    <span style="color:red; font-weight:bold;">(<?= $missingCount ?> Missing)</span>
                <?php endif; ?>
            </p>
            <a href="/student/upload-documents.php" class="btn btn-<?= $missingCount > 0 ? 'orange' : 'outline' ?>">Manage Documents</a>
        </div>
    </div>

    <!-- Eligibility Checks History -->
    <?php if (count($allApps) > 0): ?>
    <div class="card mt-6">
        <h3 style="margin-bottom:16px; font-size:1.1rem;">Eligibility Checks History</h3>
        <div class="table-wrap">
            <table role="table" aria-label="Eligibility Checks History">
                <thead>
                    <tr>
                        <th>Check ID</th>
                        <th>Qualification</th>
                        <th>Top Programme Match</th>
                        <th>Best Fit %</th>
                        <th>Date Checked</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allApps as $app): 
                        // Fetch the top match for this specific check
                        $stmtMatch = $db->prepare("SELECT p.name, er.fit_percentage FROM eligibility_results er JOIN programmes p ON er.programme_id = p.id WHERE er.application_id = ? AND er.eligible = 1 ORDER BY er.fit_percentage DESC LIMIT 1");
                        $stmtMatch->execute([$app['id']]);
                        $topMatch = $stmtMatch->fetch();
                    ?>
                    <tr>
                        <td>#<?= $app['id'] ?></td>
                        <td><?= htmlspecialchars($app['qual_type']) ?></td>
                        <td>
                            <?php if ($topMatch): ?>
                                <strong><?= htmlspecialchars($topMatch['name']) ?></strong>
                            <?php else: ?>
                                <span style="color:var(--text-muted); font-size:0.85rem;">No eligible programmes</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($topMatch): ?>
                                <span class="badge badge-<?= $topMatch['fit_percentage'] >= 75 ? 'green' : ($topMatch['fit_percentage'] >= 50 ? 'yellow' : 'red') ?>">
                                    <?= $topMatch['fit_percentage'] ?>%
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= date('d M Y, h:i A', strtotime($app['created_at'])) ?></td>
                        <td>
                            <a href="/student/results.php" class="btn btn-outline btn-sm">View Full Results</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</div>

<?php 
require_once __DIR__ . '/../includes/chatbot.php';
require_once __DIR__ . '/../includes/footer.php'; 
?>
