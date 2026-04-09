<?php
namespace UTP\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Email Notification Service
 *
 * Handles all outbound emails using PHPMailer with SMTP.
 * Uses a shared professional HTML layout for brand consistency.
 */
class Mailer
{
    /**
     * Create a pre-configured PHPMailer instance.
     */
    public static function createMailer(): PHPMailer
    {
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

    // ─── Shared Email Layout ───────────────────────────────────────────

    /**
     * Wrap email content in a professional branded HTML layout.
     *
     * @param string $preheader   Short preview text shown in inbox
     * @param string $innerHtml   The main body content HTML
     * @param string $footerExtra Optional extra footer line
     * @return string             Complete HTML email
     */
    public static function wrapLayout(string $preheader, string $innerHtml, string $footerExtra = ''): string
    {
        $year = date('Y');

        return <<<HTML
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    <title>UTP Scholarship System</title>
    <!--[if mso]>
    <noscript><xml><o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings></xml></noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#f0f2f5;font-family:'Segoe UI',Roboto,'Helvetica Neue',Arial,sans-serif;-webkit-font-smoothing:antialiased;">
    <!-- Preheader (hidden inbox preview text) -->
    <div style="display:none;font-size:1px;color:#f0f2f5;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        {$preheader}
    </div>

    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f0f2f5;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" width="600" style="max-width:600px;width:100%;">

                    <!-- Logo Header -->
                    <tr>
                        <td align="center" style="padding-bottom:24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="background:linear-gradient(135deg,#e8630a,#f59e0b);width:44px;height:44px;border-radius:12px;text-align:center;vertical-align:middle;font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-1px;">
                                        U
                                    </td>
                                    <td style="padding-left:12px;font-size:18px;font-weight:700;color:#1a1a2e;letter-spacing:-0.3px;">
                                        UTP Scholarship System
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main Card -->
                    <tr>
                        <td style="background-color:#ffffff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.06);overflow:hidden;">

                            <!-- Orange accent bar -->
                            <div style="height:4px;background:linear-gradient(90deg,#e8630a,#f59e0b,#e8630a);"></div>

                            <!-- Content -->
                            <td style="padding:40px 36px 32px;">
                                {$innerHtml}
                            </td>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:28px 16px 0;text-align:center;">
                            <p style="margin:0 0 6px;font-size:13px;color:#9ca3af;line-height:1.5;">
                                Universiti Teknologi PETRONAS<br>
                                32610 Seri Iskandar, Perak, Malaysia
                            </p>
                            {$footerExtra}
                            <p style="margin:12px 0 0;font-size:12px;color:#c4c9d4;">
                                &copy; {$year} UTP Scholarship &amp; Course Eligibility System. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Generate a styled CTA button.
     */
    private static function ctaButton(string $url, string $label, string $bgColor = '#e8630a'): string
    {
        return <<<HTML
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:32px 0;">
    <tr>
        <td align="center">
            <a href="{$url}" target="_blank"
               style="display:inline-block;padding:14px 36px;background:{$bgColor};color:#ffffff;text-decoration:none;border-radius:8px;font-size:15px;font-weight:600;letter-spacing:0.3px;mso-padding-alt:0;">
                <!--[if mso]><i style="letter-spacing:36px;mso-font-width:-100%;mso-text-raise:21pt;">&nbsp;</i><![endif]-->
                {$label}
                <!--[if mso]><i style="letter-spacing:36px;mso-font-width:-100%;">&nbsp;</i><![endif]-->
            </a>
        </td>
    </tr>
</table>
HTML;
    }

    /**
     * Generate a divider line.
     */
    private static function divider(): string
    {
        return '<div style="height:1px;background:#eef0f4;margin:28px 0;"></div>';
    }

    // ─── Email Methods ────────────────────────────────────────────────

    /**
     * Send an email verification link to a newly registered user.
     */
    public static function sendVerificationEmail(string $userId, string $userEmail, string $userName): bool
    {
        if (getenv('APP_ENV') === 'testing') {
            return true;
        }

        $systemUrl = getenv('APP_URL') ?: 'http://localhost';
        $token = bin2hex(random_bytes(32));

        try {
            /** @phpstan-ignore function.notFound */
            $db = getDB();
            $db->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$userId]);
            $stmt = $db->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))");
            $stmt->execute([$userId, $token]);
        } catch (\Exception $e) {
            error_log('Token save failed: ' . $e->getMessage());
            return false;
        }

        $safeName = htmlspecialchars($userName);
        $verifyUrl = "{$systemUrl}/auth/verify-email.php?token={$token}&id={$userId}";
        $button = self::ctaButton($verifyUrl, '✉️&nbsp;&nbsp;Verify Email Address');

        $innerHtml = <<<HTML
<div style="text-align:center;margin-bottom:28px;">
    <div style="display:inline-block;width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#e8630a 0%,#f59e0b 100%);line-height:64px;text-align:center;font-size:28px;">
        ✉️
    </div>
</div>

<h1 style="margin:0 0 8px;font-size:24px;font-weight:700;color:#1a1a2e;text-align:center;line-height:1.3;">
    Welcome to UTP, {$safeName}!
</h1>
<p style="margin:0 0 24px;font-size:15px;color:#6b7280;text-align:center;line-height:1.6;">
    Thank you for registering. Please verify your email address to unlock full access to the scholarship and eligibility system.
</p>

{$button}

<p style="margin:0;font-size:13px;color:#9ca3af;text-align:center;line-height:1.5;">
    This link will expire in <strong style="color:#6b7280;">24 hours</strong>.<br>
    If you didn't create an account, you can safely ignore this email.
</p>
HTML;

        $body = self::wrapLayout(
            "Verify your email to get started with UTP Scholarship System",
            $innerHtml
        );

        try {
            $mail = self::createMailer();
            $mail->addAddress($userEmail, $userName);
            $mail->Subject = 'Verify Your Email — UTP Scholarship System';
            $mail->Body = $body;
            $mail->AltBody = "Welcome to UTP, {$userName}! Verify your email: {$verifyUrl}";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Verification email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send an application status update notification.
     */
    public static function sendApplicationStatusEmail(string $userEmail, string $userName, string $status, string $programmeName, string $adminNotes = ''): bool
    {
        if (getenv('APP_ENV') === 'testing') {
            return true;
        }

        $systemUrl = getenv('APP_URL') ?: 'http://localhost';
        $safeName = htmlspecialchars($userName);
        $safeProg = htmlspecialchars($programmeName);

        // Status-specific theming
        $config = [
            'approved' => [
                'color'   => '#059669',
                'bg'      => '#ecfdf5',
                'border'  => '#a7f3d0',
                'icon'    => '🎉',
                'heading' => 'Congratulations!',
                'message' => "We are delighted to inform you that your application for <strong>{$safeProg}</strong> has been <strong>approved</strong>.",
            ],
            'rejected' => [
                'color'   => '#dc2626',
                'bg'      => '#fef2f2',
                'border'  => '#fecaca',
                'icon'    => '📋',
                'heading' => 'Application Update',
                'message' => "After careful review, we regret to inform you that your application for <strong>{$safeProg}</strong> was <strong>not successful</strong> at this time.",
            ],
            'processing' => [
                'color'   => '#d97706',
                'bg'      => '#fffbeb',
                'border'  => '#fde68a',
                'icon'    => '⏳',
                'heading' => 'Application Received',
                'message' => "Your application for <strong>{$safeProg}</strong> has been received and is now <strong>under review</strong> by our admissions team.",
            ],
        ];

        $c = $config[$status] ?? $config['processing'];

        $notesHtml = '';
        if (!empty($adminNotes)) {
            $safeNotes = nl2br(htmlspecialchars($adminNotes));
            $notesHtml = <<<HTML
<div style="background:#f8fafc;border-left:4px solid #e8630a;padding:16px 20px;margin:24px 0;border-radius:0 8px 8px 0;">
    <p style="margin:0 0 6px;font-size:12px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:0.5px;">
        Message from Admissions
    </p>
    <p style="margin:0;font-size:14px;color:#374151;line-height:1.6;font-style:italic;">
        {$safeNotes}
    </p>
</div>
HTML;
        }

        $button = self::ctaButton("{$systemUrl}/auth/login.php", '📊&nbsp;&nbsp;Go to Dashboard');
        $divider = self::divider();

        $innerHtml = <<<HTML
<div style="text-align:center;margin-bottom:28px;">
    <div style="display:inline-block;width:64px;height:64px;border-radius:50%;background:{$c['bg']};border:2px solid {$c['border']};line-height:64px;text-align:center;font-size:28px;">
        {$c['icon']}
    </div>
</div>

<h1 style="margin:0 0 8px;font-size:24px;font-weight:700;color:#1a1a2e;text-align:center;line-height:1.3;">
    {$c['heading']}
</h1>

<p style="margin:0 0 20px;font-size:15px;color:#6b7280;text-align:center;line-height:1.6;">
    Dear {$safeName},
</p>

<!-- Status Badge -->
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom:20px;">
    <tr>
        <td align="center">
            <div style="display:inline-block;background:{$c['bg']};border:1px solid {$c['border']};border-radius:24px;padding:8px 24px;">
                <span style="font-size:14px;font-weight:700;color:{$c['color']};text-transform:uppercase;letter-spacing:1px;">
                    {$c['icon']}&nbsp; Status: {$status}
                </span>
            </div>
        </td>
    </tr>
</table>

<p style="margin:0 0 4px;font-size:15px;color:#374151;line-height:1.7;text-align:center;">
    {$c['message']}
</p>

{$notesHtml}

{$divider}

<p style="margin:0 0 4px;font-size:13px;color:#9ca3af;text-align:center;">
    Log in to view full details and next steps.
</p>

{$button}
HTML;

        $body = self::wrapLayout(
            "Your application for {$programmeName} — status: {$status}",
            $innerHtml,
            '<p style="margin:8px 0 0;font-size:12px;color:#c4c9d4;">This is an automated notification. Please do not reply to this email.</p>'
        );

        try {
            $mail = self::createMailer();
            $mail->addAddress($userEmail, $userName);
            $mail->Subject = "Application {$status}: {$programmeName} — UTP";
            $mail->Body = $body;
            $mail->AltBody = "Dear {$userName}, your application for {$programmeName} is now: {$status}. Log in at {$systemUrl}/auth/login.php for details.";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Status email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a professional password reset email body.
     *
     * @param string $userName  User's full name
     * @param string $resetLink The password reset URL
     * @return string           Complete HTML email body
     */
    public static function buildPasswordResetEmail(string $userName, string $resetLink): string
    {
        $safeName = htmlspecialchars($userName);
        $safeLink = htmlspecialchars($resetLink);
        $button = self::ctaButton($safeLink, '🔑&nbsp;&nbsp;Reset My Password');

        $innerHtml = <<<HTML
<div style="text-align:center;margin-bottom:28px;">
    <div style="display:inline-block;width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#7c3aed 0%,#a78bfa 100%);line-height:64px;text-align:center;font-size:28px;">
        🔒
    </div>
</div>

<h1 style="margin:0 0 8px;font-size:24px;font-weight:700;color:#1a1a2e;text-align:center;line-height:1.3;">
    Password Reset Request
</h1>

<p style="margin:0 0 24px;font-size:15px;color:#6b7280;text-align:center;line-height:1.6;">
    Dear {$safeName}, we received a request to reset your password. Click the button below to choose a new one.
</p>

{$button}

<div style="background:#fef3c7;border:1px solid #fde68a;border-radius:8px;padding:14px 18px;margin:24px 0 0;">
    <p style="margin:0;font-size:13px;color:#92400e;line-height:1.5;">
        ⚠️ This link will expire in <strong>1 hour</strong> for security reasons.<br>
        If you did not request a password reset, please ignore this email — your account is safe.
    </p>
</div>
HTML;

        return self::wrapLayout(
            "Reset your UTP Scholarship System password",
            $innerHtml,
            '<p style="margin:8px 0 0;font-size:12px;color:#c4c9d4;">If the button doesn\'t work, copy this link: ' . $safeLink . '</p>'
        );
    }
}
