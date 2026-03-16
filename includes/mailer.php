<?php
/**
 * Consolidated Email Notifications
 * Uses PHPMailer for SMTP support (Mailtrap compatible)
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function createMailer(): PHPMailer
{
    $autoloadPath = __DIR__ . '/../vendor/autoload.php';
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = getenv('MAIL_HOST') ?: 'sandbox.smtp.mailtrap.io';
    $mail->SMTPAuth = true;
    $mail->Username = getenv('MAIL_USERNAME') ?: '';
    $mail->Password = getenv('MAIL_PASSWORD') ?: '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int)(getenv('MAIL_PORT') ?: 2525);
    $mail->setFrom(getenv('MAIL_FROM') ?: 'noreply@utp.edu.my', 'UTP Scholarship System');
    $mail->isHTML(true);
    return $mail;
}

if (!function_exists('sendVerificationEmail')) {
    function sendVerificationEmail(string $userId, string $userEmail, string $userName): bool
    {
        if (getenv('APP_ENV') === 'testing')
            return true;

        $systemUrl = getenv('APP_URL') ?: 'http://localhost';
        $token = bin2hex(random_bytes(32));

        // Save token to database
        try {
            $db = getDB();
            $db->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$userId]);
            $stmt = $db->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))");
            $stmt->execute([$userId, $token]);
        }
        catch (\Exception $e) {
            error_log('Token save failed: ' . $e->getMessage());
            return false;
        }

        try {
            $mail = createMailer();
            $mail->addAddress($userEmail, $userName);
            $mail->Subject = 'UTP System - Verify Your Email';
            $mail->Body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <h2 style='color: #f26522;'>Welcome to UTP Scholarship & Course Eligibility System</h2>
                <p>Dear " . htmlspecialchars($userName) . ",</p>
                <p>Please click the link below to verify your email address:</p>
                <p>
                    <a href='{$systemUrl}/auth/verify-email.php?token={$token}&id={$userId}'
                       style='display:inline-block;padding:10px 20px;background:#f26522;color:#fff;text-decoration:none;border-radius:4px;'>
                        Verify Email Address
                    </a>
                </p>
                <p style='color:#888;font-size:0.85rem;'>If you did not register, please ignore this email.</p>
            </body>
            </html>";
            $mail->send();
            return true;
        }
        catch (Exception $e) {
            error_log('Verification email failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('sendApplicationStatusEmail')) {
    function sendApplicationStatusEmail(string $userEmail, string $userName, string $status, string $programmeName, string $adminNotes = ''): bool
    {
        if (getenv('APP_ENV') === 'testing')
            return true;

        $systemUrl = getenv('APP_URL') ?: 'http://localhost';

        $statusColor = '#3f51b5';
        if ($status === 'approved')
            $statusColor = '#4caf50';
        elseif ($status === 'rejected')
            $statusColor = '#f44336';

        $notesHtml = '';
        if (!empty($adminNotes)) {
            $notesHtml = "<div style='background:#f9f9f9;border-left:4px solid #ccc;padding:10px 15px;margin:20px 0;font-style:italic;'>
                <strong>Admin Notes:</strong><br>" . nl2br(htmlspecialchars($adminNotes)) . "
            </div>";
        }

        $body = "
        <html>
        <body style='font-family:Arial,sans-serif;color:#333;'>
            <div style='max-width:600px;margin:0 auto;padding:20px;border:1px solid #eee;border-radius:8px;'>
                <div style='background:#f8f9fa;padding:15px;text-align:center;border-bottom:2px solid #f26522;font-weight:bold;font-size:1.2rem;'>
                    Universiti Teknologi PETRONAS
                </div>
                <div style='padding:20px 0;'>
                    <p>Dear <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                    <p>There has been an update regarding your application for <strong>" . htmlspecialchars($programmeName) . "</strong>.</p>
                    <p>Your application status is now:
                        <span style='display:inline-block;padding:6px 12px;color:#fff;background:{$statusColor};border-radius:4px;font-weight:600;text-transform:uppercase;'>
                            " . htmlspecialchars($status) . "
                        </span>
                    </p>
                    {$notesHtml}
                    <p style='text-align:center;margin:30px 0;'>
                        <a href='{$systemUrl}/auth/login.php'
                           style='display:inline-block;padding:10px 20px;background:#f26522;color:#fff;text-decoration:none;border-radius:4px;'>
                            Go to Dashboard
                        </a>
                    </p>
                </div>
                <div style='margin-top:30px;font-size:0.85rem;color:#777;text-align:center;border-top:1px solid #eee;padding-top:15px;'>
                    This is an automated message. Please do not reply directly to this email.
                </div>
            </div>
        </body>
        </html>";

        try {
            $mail = createMailer();
            $mail->addAddress($userEmail, $userName);
            $mail->Subject = 'UTP Application Status Update: ' . ucfirst($status);
            $mail->Body = $body;
            $mail->send();
            return true;
        }
        catch (Exception $e) {
            error_log('Status email failed: ' . $e->getMessage());
            return false;
        }
    }
}