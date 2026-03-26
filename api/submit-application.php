<?php
/**
 * API: Submit Application
 * Updates an existing application with chosen programme and scholarship
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

setSecurityHeaders();
header('Content-Type: application/json; charset=utf-8');
initSession();
requireVerified();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /student/dashboard.php');
    exit;
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid form submission.';
    header('Location: /student/dashboard.php');
    exit;
}

$appId = intval($_POST['app_id'] ?? 0);
$prog1 = intval($_POST['programme_id_1'] ?? 0);
$prog2 = intval($_POST['programme_id_2'] ?? 0);
$prog3 = intval($_POST['programme_id_3'] ?? 0);
$scholarshipId = (!empty($_POST['scholarship_id'])) ? intval($_POST['scholarship_id']) : null;
$userId = $_SESSION['user_id'];

if ($appId <= 0 || $prog1 <= 0 || $prog2 <= 0 || $prog3 <= 0) {
    $_SESSION['error'] = 'You must select exactly three (3) programmes according to your preference.';
    header("Location: /student/results.php");
    exit;
}

if (count(array_unique([$prog1, $prog2, $prog3])) !== 3) {
    $_SESSION['error'] = 'Please select three different programmes. You cannot choose the same programme more than once.';
    header("Location: /student/results.php");
    exit;
}

$db = getDB();

try {
    // Verify the application belongs to the user
    $stmt = $db->prepare("SELECT id FROM applications WHERE id = ? AND user_id = ?");
    $stmt->execute([$appId, $userId]);
    if (!$stmt->fetch()) {
        $_SESSION['error'] = 'Application not found.';
        header("Location: /student/dashboard.php");
        exit;
    }

    // Verify all 3 programmes are eligible
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM eligibility_results 
        WHERE application_id = ? 
        AND programme_id IN (?, ?, ?) 
        AND eligible = 1
    ");
    $stmt->execute([$appId, $prog1, $prog2, $prog3]);
    if ($stmt->fetchColumn() != 3) {
        $_SESSION['error'] = 'One or more selected programmes do not match your eligibility results.';
        header("Location: /student/results.php");
        exit;
    }

    // Update with chosen programmes and scholarship
    $stmt = $db->prepare("UPDATE applications SET programme_id_1 = ?, programme_id_2 = ?, programme_id_3 = ?, scholarship_id = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$prog1, $prog2, $prog3, $scholarshipId, $appId]);

    // Send confirmation email
    require_once __DIR__ . '/../includes/mailer.php';
    $userStmt = $db->prepare("SELECT full_name, email FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $user = $userStmt->fetch();

    $progStmt = $db->prepare("SELECT name FROM programmes WHERE id = ?");
    $progStmt->execute([$prog1]);
    $prog = $progStmt->fetch();

    if ($user && $prog) {
        sendApplicationStatusEmail(
            $user['email'],
            $user['full_name'],
            'processing',
            $prog['name']
        );
    }

    $_SESSION['success'] = 'Your application has been successfully submitted! The administration will review it shortly.';
    header('Location: /student/dashboard.php');
    exit;

}
catch (Exception $e) {
    trackEvent('Application Submission Failed', ['exception' => $e, 'user_id' => $userId], 'ERROR');
    $_SESSION['error'] = 'An error occurred. Please try again.';
    header('Location: /student/results.php');
    exit;
}
