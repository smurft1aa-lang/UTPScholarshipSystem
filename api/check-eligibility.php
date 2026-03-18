<?php
/**
 * API: Check Eligibility
 * Saves qualification + grades, runs AI engine, creates application
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/ai_engine.php';

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
    if (in_array($existingApp['status'], ['approved', 'rejected'])) {
        $_SESSION['error'] = 'Your application has already been processed. You cannot change your grades now.';
        header('Location: /student/dashboard.php');
        exit;
    }
    // They are just re-checking. We will delete the old un-processed application + cascade delete qualifications/grades/results.
    $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->execute([$existingApp['id']]);
    
    // Also delete their previous qualifications so we start fresh
    $stmt = $db->prepare("DELETE FROM qualifications WHERE user_id = ?");
    $stmt->execute([$userId]);
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

    // Run AI eligibility engine
    $results = AIEngine::checkEligibility($qualId);

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

    header('Location: /student/results.php');
    exit;

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error'] = 'An error occurred. Please try again.';
    header('Location: /student/check-eligibility.php');
    exit;
}
