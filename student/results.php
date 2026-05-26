<?php
/**
 * Student Results Page
 * Displays AI-ranked eligible programmes and matching scholarships
 */
require_once __DIR__ . '/../includes/init.php';
requireStudent();

$db = getDB();
$userId = $_SESSION['user_id'];

// Get latest application with results
$stmt = $db->prepare("
    SELECT a.*, q.qual_type
    FROM applications a
    JOIN qualifications q ON a.qualification_id = q.id
    WHERE a.user_id = ?
    ORDER BY a.created_at DESC
    LIMIT 1
");
$stmt->execute([$userId]);
$application = $stmt->fetch();

$results = [];
$scholarships = [];

if ($application) {
    // Get eligibility results
    $stmt = $db->prepare("
        SELECT er.*, p.name as programme_name, p.category, p.description as programme_desc,
               p.duration, p.foundation_fee, p.undergraduate_fee
        FROM eligibility_results er
        JOIN programmes p ON er.programme_id = p.id
        WHERE er.application_id = ?
        ORDER BY er.eligible DESC, er.fit_percentage DESC
    ");
    $stmt->execute([$application['id']]);
    $results = $stmt->fetchAll();

    // Get matching scholarships
    $eligibleIds = [];
    $fitMap = [];
    foreach ($results as $r) {
        if ($r['eligible']) {
            $eligibleIds[] = $r['programme_id'];
            $fitMap[$r['programme_id']] = $r['fit_percentage'];
        }
    }

    if (!empty($eligibleIds)) {
        $placeholders = str_repeat('?,', count($eligibleIds) - 1) . '?';
        $stmt = $db->prepare("
            SELECT DISTINCT s.*, GROUP_CONCAT(sp.programme_id) as programme_ids
            FROM scholarships s
            JOIN scholarship_programme sp ON s.id = sp.scholarship_id
            WHERE sp.programme_id IN ({$placeholders})
            AND s.is_active = 1 AND (s.end_date IS NULL OR s.end_date >= CURDATE())
            GROUP BY s.id
        ");
        $stmt->execute($eligibleIds);
        $scholarships = $stmt->fetchAll();
    }
    
    // Supplement with full AI run to get gaps and confidence labels
    $aiEngine = new \UTP\Services\AIEngine($db);
    $aiResultsRaw = $aiEngine->checkEligibility($application['qualification_id']);
    $aiMap = [];
    foreach ($aiResultsRaw as $air) {
        $aiMap[$air['programme_id']] = $air;
    }
    
    foreach ($results as &$r) {
        if (isset($aiMap[$r['programme_id']])) {
            $r['confidence_label'] = $aiMap[$r['programme_id']]['confidence_label'];
            $r['gaps'] = $aiMap[$r['programme_id']]['gaps'];
            // Overwrite fit_percentage to include the optional bonus if it wasn't captured when DB saved
            $r['fit_percentage'] = $aiMap[$r['programme_id']]['fit_percentage'];
        } else {
            $r['confidence_label'] = 'Unknown';
            $r['gaps'] = [];
        }
    }
    unset($r); // safe unset
}

$pageTitle = 'Eligibility Results — UTP Scholarship System';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top:32px; padding-bottom:48px;">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-4"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success mb-4"><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="flex-between mb-6">
        <div class="page-header" style="margin-bottom:0;">
            <h1>Eligibility Results</h1>
            <?php if ($application): ?>
                <p>Qualification: <?= htmlspecialchars($application['qual_type']) ?> | Checked: <?= date('d M Y', strtotime($application['created_at'])) ?></p>
            <?php endif; ?>
        </div>
        <?php if ($application): ?>
            <div style="display:flex; gap:12px;">
                <a href="/student/export-results.php" target="_blank" class="btn btn-outline btn-sm">📄 Download PDF</a>
                <a href="/student/my-proposal.php?id=<?= $application['id'] ?>" class="btn btn-orange btn-sm">View Proposal</a>
            </div>
        <?php endif; ?>
    </div>

    <?php if (empty($results)): ?>
        <div class="card text-center" style="padding:48px;">
            <h3 style="margin-bottom:8px;">No Results Yet</h3>
            <p style="color:var(--text-secondary); margin-bottom:20px;">You haven't checked your eligibility yet. Enter your grades to get started.</p>
            <a href="/student/check-eligibility.php" class="btn btn-orange">Check Eligibility</a>
        </div>
    <?php else: ?>

        <!-- Eligible Programmes -->
        <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:16px;">
            <?= count(array_filter($results, function($r){ return $r['eligible']; })) ?> Programmes Found
        </h2>

        <div class="grid-auto mb-6">
            <?php foreach ($results as $r):
                if (!$r['eligible']) continue;
                $fitClass = $r['fit_percentage'] >= 75 ? 'high' : ($r['fit_percentage'] >= 50 ? 'medium' : 'low');
                $categoryColors = [
                    'Engineering & Science' => 'orange',
                    'Technology' => 'blue',
                    'Computer Science' => 'purple',
                    'Business Management' => 'green'
                ];
                $badgeColor = $categoryColors[$r['category']] ?? 'orange';
            ?>
            <div class="result-card">
                <div class="result-card-header">
                    <div>
                        <span class="badge badge-<?= $badgeColor ?>"><?= htmlspecialchars($r['category']) ?></span>
                        <?php 
                        $confClass = 'gray';
                        if ($r['confidence_label'] === 'Excellent Match' || $r['confidence_label'] === 'Strong Match') $confClass = 'green';
                        elseif ($r['confidence_label'] === 'Good Match' || $r['confidence_label'] === 'Possible Match') $confClass = 'yellow';
                        elseif ($r['confidence_label'] === 'Not Recommended') $confClass = 'red';
                        ?>
                        <span class="badge badge-<?= $confClass ?>" style="margin-left:8px;"><?= $r['confidence_label'] ?></span>
                    </div>
                </div>
                <h3><?= htmlspecialchars($r['programme_name']) ?></h3>
                <p><?= htmlspecialchars($r['programme_desc']) ?></p>
                <?php if ($r['recommendation_text']): ?>
                    <p style="margin-top:8px; font-size:0.82rem; color:var(--text-muted); font-style:italic;">
                        <?= htmlspecialchars($r['recommendation_text']) ?>
                    </p>
                <?php endif; ?>
                
                <div class="progress-bar mb-4" style="margin-top:12px;">
                    <div class="progress-fill <?= $fitClass === 'high' ? 'green' : ($fitClass === 'medium' ? 'yellow' : 'red') ?>" style="width:<?= $r['fit_percentage'] ?>%"></div>
                    <div style="font-size:0.85rem; font-weight:bold; margin-top:4px; text-align:right;">Fit: <?= $r['fit_percentage'] ?>%</div>
                </div>

                <?php if (!empty($r['gaps'])): ?>
                <details style="margin-bottom:12px; background:#fefefe; border:1px solid #eee; padding:8px; border-radius:4px;">
                    <summary style="font-size:0.85rem; cursor:pointer; color:var(--text-secondary); font-weight:bold;">Gap Analysis (<?= count($r['gaps']) ?> gaps)</summary>
                    <ul style="margin-top:8px; font-size:0.8rem; color:var(--text-muted); padding-left:20px;">
                        <?php foreach($r['gaps'] as $g): ?>
                            <li><strong><?= htmlspecialchars($g['subject']) ?>:</strong> <?= htmlspecialchars($g['message']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </details>
                <?php endif; ?>

                <?php
                $progSchols = array_filter($scholarships, function($s) use ($r) {
                    $pids = explode(',', $s['programme_ids'] ?? '');
                    return in_array($r['programme_id'], $pids);
                });
                if (!empty($progSchols)):
                ?>
                <div class="mt-4" style="border-top:1px solid var(--border); padding-top:12px; margin-bottom:12px;">
                    <strong style="font-size:0.85rem; color:var(--text-secondary);">Matching Scholarships:</strong>
                    <div style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">
                        <?php foreach (array_slice($progSchols, 0, 3) as $ps): ?>
                            <span class="badge badge-outline" style="font-size:0.75rem;" title="<?= htmlspecialchars($ps['name']) ?>">
                                <?= htmlspecialchars(strlen($ps['name']) > 25 ? substr($ps['name'], 0, 22).'...' : $ps['name']) ?>
                            </span>
                        <?php endforeach; ?>
                        <?php if (count($progSchols) > 3): ?>
                            <span style="font-size:0.75rem; color:var(--text-muted);">+<?= count($progSchols)-3 ?> more</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="result-card-footer">
                    <span>Foundation: RM <?= number_format($r['foundation_fee']) ?></span>
                    <span>Undergraduate: RM <?= number_format($r['undergraduate_fee']) ?></span>
                    <span><?= htmlspecialchars($r['duration']) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Not Eligible (collapsed) -->
        <?php
        $notEligible = array_filter($results, function($r){ return !$r['eligible']; });
        if (!empty($notEligible)):
        ?>
        <details style="margin-bottom:32px;">
            <summary style="cursor:pointer; font-size:1rem; font-weight:600; margin-bottom:12px; color:var(--text-secondary);">
                <?= count($notEligible) ?> programmes you don't qualify for yet
            </summary>
            <div class="grid-auto">
                <?php foreach ($notEligible as $r): ?>
                <div class="result-card" style="opacity:0.7;">
                    <div class="result-card-header">
                        <span class="badge badge-red">Not Eligible</span>
                        <span class="fit-score low"><?= $r['fit_percentage'] ?>%</span>
                    </div>
                    <h3><?= htmlspecialchars($r['programme_name']) ?></h3>
                    <?php if ($r['recommendation_text']): ?>
                        <p style="font-size:0.82rem; color:var(--text-muted); margin-top:6px;">
                            <?= htmlspecialchars($r['recommendation_text']) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </details>
        <?php endif; ?>

        <!-- Scholarships section removed as they are now grouped under each programme -->

        <!-- Ready to Apply? Redirect to Official UTP Admission -->
        <div class="card mt-6" style="border:2px solid var(--orange); text-align:center; padding:40px;">
            <h2 style="font-size:1.3rem; font-weight:700; color:var(--orange); margin-bottom:12px;">🎓 Ready to Apply?</h2>
            <p style="color:var(--text-secondary); margin-bottom:8px; max-width:600px; margin-left:auto; margin-right:auto;">
                Now that you know which programmes and scholarships you're eligible for, take the next step by submitting your official application through the UTP Admissions Portal.
            </p>
            <p style="font-size:0.85rem; color:var(--text-muted); margin-bottom:24px;">
                Please prepare your IC/MyKad, academic certificates, passport photo, and proof of RM50 processing fee payment.
            </p>
            <a href="https://utpdec.microsoftcrmportals.com/admission/" target="_blank" class="btn btn-orange btn-lg" style="display:inline-flex; align-items:center; gap:8px;">
                Apply at Official UTP Portal
                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6M15 3h6v6M10 14L21 3"/></svg>
            </a>
        </div>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
