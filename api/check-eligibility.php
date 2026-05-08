<?php

declare(strict_types=1);

/**
 * API: Check Eligibility
 * Saves qualification + grades, runs AI engine, creates application.
 * Returns JSON for API/fetch clients, or redirects for native form submissions.
 */

require_once __DIR__ . '/../includes/init.php';

setSecurityHeaders();
initSession();

/**
 * Helper: respond with an error in either JSON or redirect format.
 */
function apiError(int $httpCode, string $message, string $redirectUrl = '/student/check-eligibility.php'): never
{
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($acceptHeader, 'application/json')) {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'error' => $message]);
    } else {
        $_SESSION['error'] = $message;
        header('Location: ' . $redirectUrl);
    }
    exit;
}

/**
 * Helper: respond with success in either JSON or redirect format.
 */
function apiSuccess(string $redirectUrl): never
{
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($acceptHeader, 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
    } else {
        header('Location: ' . $redirectUrl);
    }
    exit;
}

if (!isLoggedIn()) {
    apiError(403, 'Authentication required.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError(405, 'Method not allowed. Use POST.');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    apiError(403, 'Invalid form submission.');
}

$qualType = sanitize($_POST['qual_type'] ?? '');
$subjects = $_POST['subjects'] ?? [];
$grades = $_POST['grades'] ?? [];

if (empty($qualType) || !in_array($qualType, ['SPM', 'O-Level', 'IGCSE'])) {
    apiError(400, 'Please select a valid qualification type.');
}

if (empty($subjects) || empty($grades) || count($subjects) !== count($grades)) {
    apiError(400, 'Please enter all grades.');
}

$db = getDB();
$userId = $_SESSION['user_id'];

// Allow admin to override the userId if performing check on behalf of a student
if (isAdmin() && !empty($_POST['student_id'])) {
    $userId = (int) $_POST['student_id'];
}

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
    $submittedGrades = [];
    for ($i = 0; $i < count($subjects); $i++) {
        $subject = sanitize($subjects[$i]);
        $grade = sanitize($grades[$i]);
        if (!empty($subject) && !empty($grade)) {
            $stmt->execute([$qualId, $subject, $grade]);
            $submittedGrades[$subject] = $grade;
        }
    }

    // Audit log OCR corrections if applicable
    $isOcrSubmission = (int)($_POST['is_ocr_submission'] ?? 0) === 1;
    if ($isOcrSubmission && isset($_SESSION['ocr_last_result'])) {
        $ocrOriginals = $_SESSION['ocr_last_result'];
        $corrections = [];
        
        // Map original subjects to their matched keys for comparison
        $originalMap = [];
        foreach ($ocrOriginals as $orig) {
            if (!empty($orig['matched_key'])) {
                $originalMap[$orig['matched_key']] = $orig['grade'];
            }
        }

        foreach ($submittedGrades as $sub => $grd) {
            if (isset($originalMap[$sub])) {
                if ($originalMap[$sub] !== $grd) {
                    $corrections[] = "Changed $sub from {$originalMap[$sub]} to $grd";
                }
            } else {
                $corrections[] = "Added manually $sub ($grd)";
            }
        }
        
        foreach ($originalMap as $origSub => $origGrd) {
            if (!isset($submittedGrades[$origSub])) {
                $corrections[] = "Deleted matched subject $origSub (was $origGrd)";
            }
        }

        if (!empty($corrections)) {
            logAudit($userId, 'OCR Result Corrected', 'Qualification', (int) $qualId, count($corrections) . " changes made. Details: " . json_encode($corrections));
            \UTP\Services\Telemetry::trackEvent('OCR Corrections Made', ['user_id' => $userId, 'corrections_count' => count($corrections)]);
        }
        
        unset($_SESSION['ocr_last_result']);
    }

    // Create application
    $stmt = $db->prepare("INSERT INTO applications (user_id, qualification_id, status) VALUES (?, ?, 'submitted')");
    $stmt->execute([$userId, $qualId]);
    $appId = $db->lastInsertId();

    // Run AI eligibility engine (OOP instance with DI)
    $aiEngine = new \UTP\Services\AIEngine($db);
    $results = $aiEngine->checkEligibility((int) $qualId);

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

    logAudit($_SESSION['user_id'], 'Eligibility Check Completed', 'Application', (int) $appId, "Qualification: $qualType, Results: " . count($results) . (isAdmin() ? " (on behalf of student $userId)" : ""));
    trackEvent('Eligibility Check Completed', ['user_id' => $userId, 'qualification_type' => $qualType, 'results_count' => count($results)]);

    $redirectUrl = isAdmin() ? "/admin/student-results.php?id={$appId}" : '/student/results.php';
    apiSuccess($redirectUrl);
} catch (\RuntimeException $e) {
    $db->rollBack();
    \UTP\Services\Telemetry::trackEvent('AI Engine Error', ['exception' => $e, 'user_id' => $userId], 'ERROR');
    apiError(500, 'Eligibility engine failed. Please try again later.');
} catch (\Exception $e) {
    $db->rollBack();
    \UTP\Services\Telemetry::trackEvent('Eligibility Check Failed', ['exception' => $e, 'user_id' => $userId], 'ERROR');
    apiError(500, 'An error occurred. Please try again.');
}
