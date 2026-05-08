<?php
/**
 * Admin: Student Eligibility Results
 * Read-only view of a student's eligibility results and scholarship matches.
 */
require_once __DIR__ . '/../includes/init.php';
requireAdmin();

$db = getDB();
$appId = intval($_GET['id'] ?? 0);

if (!$appId) {
    $_SESSION['error'] = 'Invalid application ID.';
    header('Location: /admin/applications.php');
    exit;
}

// Get the specific application and student info
$stmt = $db->prepare("
    SELECT a.*, q.qual_type, u.full_name, u.email, u.ic_number
    FROM applications a
    JOIN qualifications q ON a.qualification_id = q.id
    JOIN users u ON a.user_id = u.id
    WHERE a.id = ?
");
$stmt->execute([$appId]);
$application = $stmt->fetch();

if (!$application) {
    $_SESSION['error'] = 'Application not found.';
    header('Location: /admin/applications.php');
    exit;
}

$results = [];
$scholarships = [];

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
        $r['fit_percentage'] = $aiMap[$r['programme_id']]['fit_percentage'];
    } else {
        $r['confidence_label'] = 'Unknown';
        $r['gaps'] = [];
    }
}
unset($r);

$pageTitle = 'Student Eligibility Report — UTP Scholarship System';
require_once __DIR__ . '/admin_header.php';
?>

<div class="page-header flex-between align-center mb-6">
    <div>
        <h1>Eligibility Report: <?= htmlspecialchars($application['full_name']) ?></h1>
        <p style="color:var(--text-secondary); margin-top:4px;">
            Application #<?= $application['id'] ?> | 
            Qualification: <?= htmlspecialchars($application['qual_type']) ?> | 
            Checked: <?= date('d M Y', strtotime($application['created_at'])) ?>
        </p>
    </div>
    <div style="display:flex; gap:12px;">
        <a href="/admin/applications.php" class="btn btn-outline btn-sm">← Back to Applications</a>
    </div>
</div>

<?php if (empty($results)): ?>
    <div class="card text-center" style="padding:48px;">
        <h3 style="margin-bottom:8px;">No Results Found</h3>
        <p style="color:var(--text-secondary);">This application does not have any eligibility results recorded.</p>
    </div>
<?php else: ?>

    <!-- Eligible Programmes -->
    <h2 style="font-size:1.1rem; font-weight:600; margin-bottom:16px;">
        <?= count(array_filter($results, function($r){ return $r['eligible']; })) ?> Eligible Programmes
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
            <?= count($notEligible) ?> programmes student does not qualify for
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

<?php endif; ?>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
