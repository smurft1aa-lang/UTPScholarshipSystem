<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

setSecurityHeaders();
initSession();

$token = $_GET['token'] ?? '';
if (empty($token)) {
    $_SESSION['error'] = 'Invalid or missing verification token.';
    header('Location: /auth/login.php');
    exit;
}

$db = getDB();
$stmt = $db->prepare("SELECT id, user_id FROM email_verifications WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$record = $stmt->fetch();

if (!$record) {
    $_SESSION['error'] = "Invalid or expired verification link. Please request a new one.";
    header("Location: /auth/login.php");
    exit;
}

// Update user
$update = $db->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
$update->execute([$record['user_id']]);

// Delete token
$delete = $db->prepare("DELETE FROM email_verifications WHERE user_id = ?");
$delete->execute([$record['user_id']]);

// Log verification
require_once __DIR__ . '/../includes/audit.php';
logAudit($record['user_id'], 'Email Verified');

// If already logged in, update session
if (isLoggedIn() && $_SESSION['user_id'] == $record['user_id']) {
    $_SESSION['email_verified'] = 1;
    $_SESSION['success'] = "Email successfully verified!";
    header("Location: /student/dashboard.php");
}
else {
    $_SESSION['success'] = "Email successfully verified! You can now log in.";
    header("Location: /auth/login.php");
}
exit;
