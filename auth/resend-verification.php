<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

setSecurityHeaders();
requireLogin();

$userId = $_SESSION['user_id'];

// Check if already verified
$db = getDB();
$stmt = $db->prepare("SELECT email_verified, email, full_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($user['email_verified'] == 1) {
    $_SESSION['email_verified'] = 1;
    $_SESSION['success'] = "Your email is already verified.";
    header("Location: /student/dashboard.php");
    exit;
}

$sent = sendVerificationEmail($userId, $user['email'], $user['full_name']);

if ($sent) {
    $_SESSION['success'] = "Verification email sent! Please check your inbox.";
}
else {
    $_SESSION['error'] = "Failed to send verification email. Please try again.";
}

header("Location: /student/dashboard.php");
exit;
