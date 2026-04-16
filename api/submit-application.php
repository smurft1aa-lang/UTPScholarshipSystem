<?php

declare(strict_types=1);

/**
 * API: Submit Application
 * Updates an existing application with chosen programme and scholarship.
 * Returns JSON for API/fetch clients, or redirects for native form submissions.
 */

require_once __DIR__ . '/../includes/init.php';

setSecurityHeaders();
initSession();

/**
 * Helper: respond with an error in either JSON or redirect format.
 */
function apiError(int $httpCode, string $message, string $redirectUrl = '/student/results.php'): never
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
function apiSuccess(string $redirectUrl, string $message = ''): never
{
    $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (str_contains($acceptHeader, 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'redirect' => $redirectUrl]);
    } else {
        if ($message) {
            $_SESSION['success'] = $message;
        }
        header('Location: ' . $redirectUrl);
    }
    exit;
}

if (!isLoggedIn() || !isVerified()) {
    apiError(403, 'Authentication and email verification required.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiError(405, 'Method not allowed. Use POST.');
}

if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    apiError(403, 'Invalid form submission.');
}

$appId = intval($_POST['app_id'] ?? 0);
$prog1 = intval($_POST['programme_id_1'] ?? 0);
$prog2 = intval($_POST['programme_id_2'] ?? 0);
$prog3 = intval($_POST['programme_id_3'] ?? 0);
$scholarshipId = (!empty($_POST['scholarship_id'])) ? intval($_POST['scholarship_id']) : null;
$userId = $_SESSION['user_id'];

if ($appId <= 0 || $prog1 <= 0 || $prog2 <= 0 || $prog3 <= 0) {
    apiError(400, 'You must select exactly three (3) programmes according to your preference.');
}

if (count(array_unique([$prog1, $prog2, $prog3])) !== 3) {
    apiError(400, 'Please select three different programmes. You cannot choose the same programme more than once.');
}

$db = getDB();

try {
    // Verify the application belongs to the user
    $stmt = $db->prepare("SELECT id FROM applications WHERE id = ? AND user_id = ?");
    $stmt->execute([$appId, $userId]);
    if (!$stmt->fetch()) {
        apiError(400, 'Application not found.');
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
        apiError(400, 'One or more selected programmes do not match your eligibility results.');
    }

    // Update with chosen programmes and scholarship
    $stmt = $db->prepare("UPDATE applications SET programme_id_1 = ?, programme_id_2 = ?, programme_id_3 = ?, scholarship_id = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$prog1, $prog2, $prog3, $scholarshipId, $appId]);

    // Send confirmation email
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

    apiSuccess('/student/dashboard.php', 'Application submitted successfully.');
} catch (\Exception $e) {
    \UTP\Services\Telemetry::trackEvent('Application Submission Failed', ['exception' => $e, 'user_id' => $userId], 'ERROR');
    apiError(500, 'An error occurred. Please try again.');
}
