<?php
/**
 * Mailer Component
 * Centralizes standard email sending
 */

function sendVerificationEmail($userId, $email, $fullName) {
    $db = getDB();
    $db->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$userId]);

    $token = bin2hex(random_bytes(32));
    $db->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))")->execute([$userId, $token]);

    $appUrl = getenv('APP_URL') ?: 'http://localhost';
    $mailFrom = getenv('MAIL_FROM') ?: 'noreply@utp.edu.my';
    $verifyLink = rtrim($appUrl, '/') . "/verify-email.php?token=" . urlencode($token);
    
    $subject = "Verify Your UTP Application Account";
    $message = "Hello $fullName,\n\nPlease verify your email address by clicking the link below:\n\n$verifyLink\n\nThis link will expire in 24 hours.\n\nThank you.";
    $headers = "From: $mailFrom\r\nReply-To: $mailFrom";
    
    $mailSent = @mail($email, $subject, $message, $headers);
    if (!$mailSent && function_exists('trackEvent')) {
        trackEvent('Email Failed to Send', ['email' => $email, 'type' => 'verification'], 'WARNING');
    }
    return $mailSent;
}
