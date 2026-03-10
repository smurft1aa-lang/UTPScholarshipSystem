<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

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

// Rate limit: 1 per 5 mins using login_attempts tracking
$ip = getClientIP();
if (!checkRateLimit($ip . '_resend', 1, 5)) {
    $_SESSION['error'] = "Please wait 5 minutes before requesting another verification email.";
    header("Location: /student/dashboard.php");
    exit;
}
recordLoginAttempt($ip . '_resend');

sendVerificationEmail($userId, $user['email'], $user['full_name']);

$_SESSION['success'] = "Verification email sent. Please check your inbox.";
header("Location: /student/dashboard.php");
exit;
