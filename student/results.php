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
    require_once __DIR__ . '/../includes/ai_engine.php';
    $aiResultsRaw = AIEngine::checkEligibility($application['qualification_id']);
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

        <!-- Application Submission Form -->
        <?php if (empty($application['programme_id_1'])): ?>
        <div class="card mt-6" style="border:2px solid var(--orange);">
            <h2 style="font-size:1.2rem; font-weight:700; color:var(--orange); margin-bottom:16px;">Step 1: Document Preparation</h2>
            <div style="background:var(--bg-body); padding:16px; border-radius:8px; margin-bottom:24px; font-size:0.9rem;">
                <p style="margin-bottom:8px;"><strong>Please prepare scanned copies of the following documents in PDF format to be uploaded during the official application process:</strong></p>
                <ul style="margin-left:20px; color:var(--text-secondary); line-height:1.6;">
                    <li>Identity Card/MyKad (Crossed IC for UTP use only)</li>
                    <li>Passport photo (blue background)</li>
                    <li>Results for the following qualification:</li>
                    <ul style="margin-left:20px;">
                        <li>Official Academic Certificate & Transcript for STPM/A-Level/IB/Diploma/Foundation/Matriculation or equivalent</li>
                        <li>Official SPM Result</li>
                    </ul>
                    <li>Results for English proficiency, e.g., SPM, MUET, IELTS, etc.</li>
                    <li>Proof of processing fee payment – RM50.00 (Non-refundable)</li>
                </ul>
            </div>

            <h2 style="font-size:1.2rem; font-weight:700; color:var(--orange); margin-bottom:16px;">Step 2: Online Application Submission</h2>
            <p style="color:var(--text-secondary); margin-bottom:24px;">For the programme selection section, you are <strong>required/compulsory</strong> to choose exactly three (3) programmes according to your preference.</p>
            
            <form method="POST" action="/api/submit-application.php">
                <?= csrfField() ?>
                <input type="hidden" name="app_id" value="<?= $application['id'] ?>">
                
                <div class="grid-3 mb-4">
                    <div class="form-group">
                        <label class="form-label">Choice 1 <span style="color:red;">*</span></label>
                        <select name="programme_id_1" class="form-select" required>
                            <option value="">-- 1st Choice --</option>
                            <?php foreach ($results as $r): if ($r['eligible']): ?>
                                <option value="<?= $r['programme_id'] ?>"><?= htmlspecialchars($r['programme_name']) ?> (Fit: <?= $r['fit_percentage'] ?>%)</option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Choice 2 <span style="color:red;">*</span></label>
                        <select name="programme_id_2" class="form-select" required>
                            <option value="">-- 2nd Choice --</option>
                            <?php foreach ($results as $r): if ($r['eligible']): ?>
                                <option value="<?= $r['programme_id'] ?>"><?= htmlspecialchars($r['programme_name']) ?> (Fit: <?= $r['fit_percentage'] ?>%)</option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Choice 3 <span style="color:red;">*</span></label>
                        <select name="programme_id_3" class="form-select" required>
                            <option value="">-- 3rd Choice --</option>
                            <?php foreach ($results as $r): if ($r['eligible']): ?>
                                <option value="<?= $r['programme_id'] ?>"><?= htmlspecialchars($r['programme_name']) ?> (Fit: <?= $r['fit_percentage'] ?>%)</option>
                            <?php endif; endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Select Primary Scholarship/Sponsorship (Optional)</label>
                    <select name="scholarship_id" class="form-select">
                        <option value="">-- None --</option>
                        <?php foreach ($scholarships as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="margin-top:24px;">
                    <button type="submit" class="btn btn-orange btn-lg">Submit Official Application</button>
                </div>
            </form>
        </div>
        <?php else: ?>
        <div class="card mt-6 bg-success" style="background:#e8f5e9; border:1px solid #c8e6c9;">
            <h2 style="font-size:1.2rem; font-weight:700; color:#2e7d32; margin-bottom:8px;">Application Submitted Successfully</h2>
            <p style="color:#1b5e20;">You have chosen a programme for this application. View your dashboard for status updates.</p>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
