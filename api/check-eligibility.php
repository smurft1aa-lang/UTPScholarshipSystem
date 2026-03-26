<?php
/**
 * API: Check Eligibility
 * Saves qualification + grades, runs AI engine, creates application
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/ai_engine.php';

setSecurityHeaders();
initSession();
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /student/check-eligibility.php');
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid form submission.';
    header('Location: /student/check-eligibility.php');
    exit;
}

$qualType = sanitize($_POST['qual_type'] ?? '');
$subjects = $_POST['subjects'] ?? [];
$grades = $_POST['grades'] ?? [];

if (empty($qualType) || !in_array($qualType, ['SPM', 'O-Level', 'IGCSE'])) {
    $_SESSION['error'] = 'Please select a valid qualification type.';
    header('Location: /student/check-eligibility.php');
    exit;
}

if (empty($subjects) || empty($grades) || count($subjects) !== count($grades)) {
    $_SESSION['error'] = 'Please enter all grades.';
    header('Location: /student/check-eligibility.php');
    exit;
}

$db = getDB();
$userId = $_SESSION['user_id'];

// ── Guard: allow overwrite if status is 'submitted', block if processed ──
$stmt = $db->prepare("SELECT id, status FROM applications WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$userId]);
$existingApp = $stmt->fetch();

if ($existingApp) {
    if (in_array($existingApp['status'], ['submitted', 'processing'])) {
        // Delete draft eligibility results
        $stmt = $db->prepare("DELETE FROM eligibility_results WHERE application_id = ?");
        $stmt->execute([$existingApp['id']]);

        // Get the specific qualification ID tied to this draft so we only delete draft grades
        $stmt = $db->prepare("SELECT qualification_id FROM applications WHERE id = ?");
        $stmt->execute([$existingApp['id']]);
        $oldQualId = $stmt->fetchColumn();

        // Delete the draft application itself
        $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$existingApp['id']]);

        // Delete the old qualifications and grades
        if ($oldQualId) {
            $stmt = $db->prepare("DELETE FROM grades WHERE qualification_id = ?");
            $stmt->execute([$oldQualId]);

            $stmt = $db->prepare("DELETE FROM qualifications WHERE id = ?");
            $stmt->execute([$oldQualId]);
        }
    }
    // If status is 'approved' or 'rejected', we DO NOTHING.
    // This preserves their historical application and allows them to submit a brand new one.
}

try {
    $db->beginTransaction();

    // Save qualification
    $stmt = $db->prepare("INSERT INTO qualifications (user_id, qual_type) VALUES (?, ?)");
    $stmt->execute([$userId, $qualType]);
    $qualId = $db->lastInsertId();

    // Save grades
    $stmt = $db->prepare("INSERT INTO grades (qualification_id, subject, grade) VALUES (?, ?, ?)");
    for ($i = 0; $i < count($subjects); $i++) {
        $subject = sanitize($subjects[$i]);
        $grade = sanitize($grades[$i]);
        if (!empty($subject) && !empty($grade)) {
            $stmt->execute([$qualId, $subject, $grade]);
        }
    }

    // Create application
    $stmt = $db->prepare("INSERT INTO applications (user_id, qualification_id, status) VALUES (?, ?, 'submitted')");
    $stmt->execute([$userId, $qualId]);
    $appId = $db->lastInsertId();

    // Run AI eligibility engine (OOP instance with DI)
    $aiEngine = new \UTP\Services\AIEngine($db);
    $results = $aiEngine->checkEligibility($qualId);

    // Save eligibility results
    $stmt = $db->prepare("INSERT INTO eligibility_results (application_id, programme_id, eligible, fit_percentage, recommendation_text) VALUES (?, ?, ?, ?, ?)");
    foreach ($results as $r) {
        $stmt->execute([
            $appId,
            $r['programme_id'],
            $r['eligible'] ? 1 : 0,
            $r['fit_percentage'],
            $r['recommendation']
        ]);
    }

    $db->commit();

    logAudit($userId, 'Eligibility Check Completed', 'Application', $appId, "Qualification: $qualType, Results: " . count($results));
    trackEvent('Eligibility Check Completed', ['user_id' => $userId, 'qualification_type' => $qualType, 'results_count' => count($results)]);

    header('Location: /student/results.php');
    exit;

}
catch (Exception $e) {
    $db->rollBack();
    trackEvent('Eligibility Check Failed', ['exception' => $e, 'user_id' => $userId], 'ERROR');
    $_SESSION['error'] = 'An error occurred. Please try again.';
    header('Location: /student/check-eligibility.php');
    exit;
}
