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
.status-timeline {
    display: flex;
    justify-content: space-between;
    position: relative;
    margin: 40px 0;
    padding: 0 20px;
}
.status-timeline::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 40px;
    right: 40px;
    height: 4px;
    background: #e0e0e0;
    transform: translateY(-50%);
    z-index: 1;
}
.timeline-step {
    position: relative;
    z-index: 2;
    text-align: center;
    background: var(--bg-body);
    padding: 0 10px;
    width: 30%;
}
.timeline-circle {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #e0e0e0;
    color: white;
    line-height: 30px;
    text-align: center;
    margin: 0 auto 10px;
    font-weight: bold;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}
.timeline-label {
    font-size: 0.9rem;
    color: var(--text-muted);
    font-weight: 500;
}
.timeline-step.active .timeline-circle {
    background: var(--orange);
    box-shadow: 0 0 0 4px rgba(242, 101, 34, 0.2);
}
.timeline-step.active .timeline-label {
    color: var(--orange);
    font-weight: 700;
}
.timeline-step.completed .timeline-circle {
    background: var(--green);
}
.timeline-step.completed .timeline-label {
    color: var(--green);
}
.timeline-step.rejected .timeline-circle {
    background: var(--red);
}
.timeline-step.rejected .timeline-label {
    color: var(--red);
}
@media (max-width: 768px) {
    .status-timeline { flex-direction: column; align-items: flex-start; gap: 20px; padding-left:30px; }
    .status-timeline::before { left: 44px; top: 0; bottom: 0; width: 4px; height: auto; right: auto; transform: none; }
    .timeline-step { width: 100%; display: flex; align-items: center; gap: 15px; text-align: left; padding: 0; background: transparent; }
    .timeline-circle { margin: 0; }
}
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
            <div class="stat-label">Application Status</div>
            <div class="stat-value" style="font-size:1.2rem;">
                <?php if ($latestApp): ?>
                    <span class="badge badge-<?= statusBadgeClass($latestApp['status']) ?>">
                        <?= ucfirst($latestApp['status']) ?>
                    </span>
                <?php else: ?>
                    <span style="color:var(--text-muted); font-size:0.9rem;">No application yet</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($latestApp): ?>
    <div class="card mb-6 mb-6-mobile" style="padding: 24px;">
        <h3 style="margin-bottom: 20px; font-size: 1.1rem;">Application Progress</h3>
        <?php
            $stat = $latestApp['status'];
            $s1 = 'completed'; // Submitted is always completed if we have an app
            $s2 = ''; $s3 = '';
            
            if ($stat === 'submitted') {
                $s2 = 'active'; // processing is the next active step
            } elseif ($stat === 'processing') {
                $s2 = 'active'; // currently here
            } elseif ($stat === 'approved') {
                $s2 = 'completed';
                $s3 = 'completed';
            } elseif ($stat === 'rejected') {
                $s2 = 'completed';
                $s3 = 'rejected';
            }
        ?>
        <div class="status-timeline">
            <div class="timeline-step <?= $s1 ?>">
                <div class="timeline-circle">1</div>
                <div class="timeline-label">Application Submitted</div>
            </div>
            <div class="timeline-step <?= $s2 ?>">
                <div class="timeline-circle">2</div>
                <div class="timeline-label">Under Review</div>
            </div>
            <div class="timeline-step <?= $s3 ?>">
                <div class="timeline-circle">3</div>
                <div class="timeline-label"><?= $s3 === 'rejected' ? 'Application Rejected' : ($s3 === 'completed' ? 'Application Approved' : 'Final Decision') ?></div>
            </div>
        </div>
        
        <?php if ($latestApp['admin_notes']): ?>
        <div style="margin-top:20px; padding:15px; background:var(--bg-card); border-left:4px solid var(--purple); border-radius:0 4px 4px 0;">
            <strong style="font-size:0.9rem; color:var(--text-secondary);">Message from Admissions:</strong>
            <p style="margin-top:5px; font-size:0.95rem; font-style:italic;"><?= htmlspecialchars($latestApp['admin_notes']) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

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
            <h3 style="margin-bottom:10px; font-size:1.05rem;">My Documents</h3>
            <p style="color:var(--text-secondary); font-size:0.9rem; margin-bottom:18px;">
                Required Docs: <?= count($uploadedDocs) ?> / <?= count($requiredDocs) ?> uploaded.
                <?php if ($missingCount > 0): ?>
                    <span style="color:red; font-weight:bold;">(<?= $missingCount ?> Missing)</span>
                <?php endif; ?>
            </p>
            <a href="/student/upload-documents.php" class="btn btn-<?= $missingCount > 0 ? 'orange' : 'outline' ?>">Manage Documents</a>
        </div>
    </div>

    <?php if ($latestApp): ?>
    <div class="card mt-6">
        <div class="flex-between mb-4">
            <h3 style="font-size:1.05rem;">Latest Application</h3>
            <span class="badge badge-<?= statusBadgeClass($latestApp['status']) ?>">
                <?= ucfirst($latestApp['status']) ?>
            </span>
        </div>
        <div style="font-size:0.9rem; color:var(--text-secondary);">
            <p><strong>Qualification:</strong> <?= htmlspecialchars($latestApp['qual_type']) ?></p>
            <p><strong>Submitted:</strong> <?= date('d M Y, h:i A', strtotime($latestApp['created_at'])) ?></p>
            
            <?php if ($latestApp['prog1_name']): ?>
                <div style="background:var(--bg-card); padding:12px; border-radius:6px; border:1px solid var(--border); margin:12px 0;">
                    <p style="margin-bottom:8px;"><strong>Applied Programmes:</strong><br>
                        1. <?= htmlspecialchars($latestApp['prog1_name']) ?><br>
                        2. <?= htmlspecialchars($latestApp['prog2_name']) ?><br>
                        3. <?= htmlspecialchars($latestApp['prog3_name']) ?>
                    </p>
                    <?php if ($latestApp['schol_name']): ?>
                        <p><strong>Preferred Scholarship:</strong><br> <?= htmlspecialchars($latestApp['schol_name']) ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-yellow mt-4">
                    <strong>Action Required:</strong> You need to submit your official application choices.<br>
                    <a href="/student/results.php" class="btn btn-orange btn-sm mt-3">Continue to Application Form</a>
                </div>
            <?php endif; ?>

            <?php if ($latestApp['admin_notes']): ?>
                <p style="margin-top:12px; padding:12px; background:#fff3cd; color:#856404; border-radius:6px; border:1px solid #ffeeba;"><strong>Admin Notes:</strong> <?= htmlspecialchars($latestApp['admin_notes']) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Application History -->
    <?php if (count($allApps) > 0): ?>
    <div class="card mt-6">
        <h3 style="margin-bottom:16px; font-size:1.1rem;">Application History</h3>
        <div class="table-wrap">
            <table role="table" aria-label="Application History">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Qualification</th>
                        <th>Preferred Programmes</th>
                        <th>Scholarship</th>
                        <th>Status</th>
                        <th>Date Applied</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($allApps as $app): ?>
                    <tr>
                        <td>#<?= $app['id'] ?></td>
                        <td><?= htmlspecialchars($app['qual_type']) ?></td>
                        <td>
                            <?php if ($app['prog1_name']): ?>
                                <ul style="margin:0; padding-left:16px; font-size:0.85rem; color:var(--text-secondary);">
                                    <li><?= htmlspecialchars($app['prog1_name']) ?></li>
                                    <li><?= htmlspecialchars($app['prog2_name']) ?></li>
                                    <li><?= htmlspecialchars($app['prog3_name']) ?></li>
                                </ul>
                            <?php else: ?>
                                <span style="font-size:0.85rem; color:var(--text-muted); font-style:italic;">Draft / Unsubmitted</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= $app['schol_name'] ? '<span class="badge badge-outline">'.htmlspecialchars($app['schol_name']).'</span>' : '<span style="color:var(--text-muted); font-size:0.85rem;">None</span>' ?>
                        </td>
                        <td>
                            <span class="badge badge-<?= statusBadgeClass($app['status']) ?>">
                                <?= ucfirst($app['status']) ?>
                            </span>
                        </td>
                        <td><?= date('d M Y', strtotime($app['created_at'])) ?></td>
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
