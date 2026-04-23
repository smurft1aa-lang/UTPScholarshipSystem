<?php

declare(strict_types=1);

namespace UTP\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Email Notification Service
 *
 * Handles all outbound emails using PHPMailer with SMTP.
 * Uses a formal university letterhead layout inspired by
 * traditional admission letters (Harvard, Oxford, etc.).
 */
class Mailer
{
    private const UTP_LOGO = 'https://www.utp.edu.my/SiteAssets/UTP-logo2.png';
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
        $mail->Port = (int) (getenv('MAIL_PORT') ?: 2525);
        $mail->setFrom(getenv('MAIL_FROM') ?: 'noreply@utp.edu.my', 'UTP Office of Admissions');
        $mail->isHTML(true);
        return $mail;
    }

    // ─── Formal Letterhead Layout ──────────────────────────────────────

    /**
     * Wrap email body in a formal university letterhead.
     *
     * @param string $preheader Hidden inbox preview text
     * @param string $bodyHtml  The letter body paragraphs
     * @return string           Complete HTML email
     */
    public static function wrapLayout(string $preheader, string $bodyHtml): string
    {
        $logo = self::UTP_LOGO;
        $year = date('Y');
        $date = date('j F Y');
        // e.g. "9 April 2026"

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UTP — Office of Admissions</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f0;font-family:Georgia,'Times New Roman',Times,serif;">

    <!-- Preheader -->
    <div style="display:none;font-size:1px;color:#f5f5f0;line-height:1px;max-height:0;overflow:hidden;">
        {$preheader}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f5f0;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width:640px;width:100%;background:#ffffff;border:1px solid #d4d0c8;">

                    <!-- ═══════ LETTERHEAD ═══════ -->
                    <tr>
                        <td style="padding:32px 48px 0;border-bottom:2px solid #1a1a2e;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="80" valign="top">
                                        <img src="{$logo}" alt="UTP Crest" width="70" style="display:block;width:70px;height:auto;border:0;">
                                    </td>
                                    <td valign="top" style="padding-left:16px;">
                                        <p style="margin:0;font-family:'Palatino Linotype',Palatino,Georgia,serif;font-size:13px;font-weight:bold;color:#1a1a2e;text-transform:uppercase;letter-spacing:2px;">
                                            Universiti Teknologi PETRONAS
                                        </p>
                                        <p style="margin:2px 0 0;font-family:Georgia,serif;font-size:12px;font-weight:bold;color:#444;letter-spacing:0.5px;">
                                            OFFICE OF ADMISSIONS AND FINANCIAL AID
                                        </p>
                                        <p style="margin:4px 0 16px;font-family:Georgia,serif;font-size:10.5px;color:#777;letter-spacing:0.3px;">
                                            32610 Seri Iskandar &bull; Perak Darul Ridzuan &bull; Malaysia
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- ═══════ DATE ═══════ -->
                    <tr>
                        <td style="padding:28px 48px 0;">
                            <p style="margin:0;font-family:Georgia,serif;font-size:14px;color:#333;text-align:right;">
                                {$date}
                            </p>
                        </td>
                    </tr>

                    <!-- ═══════ LETTER BODY ═══════ -->
                    <tr>
                        <td style="padding:24px 48px 40px;font-family:Georgia,serif;font-size:14.5px;color:#1a1a2e;line-height:1.75;">
                            {$bodyHtml}
                        </td>
                    </tr>

                    <!-- ═══════ FOOTER SEPARATOR ═══════ -->
                    <tr>
                        <td style="padding:0 48px;">
                            <div style="height:1px;background-color:#d4d0c8;"></div>
                        </td>
                    </tr>

                    <!-- ═══════ FOOTER ═══════ -->
                    <tr>
                        <td style="padding:20px 48px 28px;font-family:Georgia,serif;font-size:11px;color:#999;line-height:1.6;">
                            <p style="margin:0;">
                                This is an official communication from Universiti Teknologi PETRONAS.<br>
                                If you did not expect this email, you may disregard it.
                            </p>
                            <p style="margin:10px 0 0;font-size:10.5px;color:#bbb;">
                                &copy; {$year} Universiti Teknologi PETRONAS. All rights reserved.
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
        $bodyHtml = <<<HTML
<p style="margin:0 0 20px;">Dear {$safeName},</p>

<p style="margin:0 0 16px;text-indent:2em;">
    Thank you for registering with the Universiti Teknologi PETRONAS Scholarship &amp; Course Eligibility System. We are pleased to welcome you to our platform.
</p>

<p style="margin:0 0 16px;text-indent:2em;">
    To complete your registration and gain full access to the system, please verify your email address by clicking the button below:
</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px auto;">
    <tr>
        <td align="center" style="border-radius:8px;background-color:#e8630a;">
            <a href="{$verifyUrl}" target="_blank" style="display:inline-block;padding:14px 40px;font-family:'Inter',Arial,sans-serif;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:8px;letter-spacing:0.3px;">Verify My Email</a>
        </td>
    </tr>
</table>

<p style="margin:0 0 20px;text-align:center;font-size:11.5px;color:#999;">
    If the button doesn't work, copy and paste this link into your browser:<br>
    <a href="{$verifyUrl}" style="color:#666;word-break:break-all;font-size:11px;">{$verifyUrl}</a>
</p>

<p style="margin:0 0 16px;text-indent:2em;">
    Please note that this verification link will expire in 24 hours. Should you require a new link, you may request one from your dashboard after logging in.
</p>

<p style="margin:0 0 16px;text-indent:2em;">
    If you did not create an account with our system, please disregard this email. No further action is required on your part.
</p>

<p style="margin:0 0 6px;">Yours sincerely,</p>
<p style="margin:0;font-weight:bold;">UTP Office of Admissions</p>
<p style="margin:0;font-size:12.5px;color:#555;">Universiti Teknologi PETRONAS</p>
HTML;
        $body = self::wrapLayout("Verify your email address — UTP Scholarship System", $bodyHtml);
        try {
            $mail = self::createMailer();
            $mail->addAddress($userEmail, $userName);
            $mail->Subject = 'Email Verification — Universiti Teknologi PETRONAS';
            $mail->Body = $body;
            $mail->AltBody = "Dear {$userName}, Please verify your email by visiting: {$verifyUrl}";
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
        $loginUrl = "{$systemUrl}/auth/login.php";
        // Build status-specific paragraphs
        $statusParagraph = '';
        $closingAdvice = '';
        if ($status === 'approved') {
            $statusParagraph = <<<HTML
<p style="margin:0 0 16px;text-indent:2em;">
    I am delighted to inform you that the Admissions Committee has reviewed your application for the <strong>{$safeProg}</strong> programme, and I am pleased to confirm that your application has been <strong>approved</strong>. Please accept our sincere congratulations for your outstanding achievements.
</p>

<p style="margin:0 0 16px;text-indent:2em;">
    In making each admission decision, the Committee keeps in mind that the excellence of Universiti Teknologi PETRONAS depends most of all on the talent and promise of the people assembled here, particularly our students. In voting to offer you admission, the Committee has demonstrated its firm belief that you can make important contributions during your university years and beyond.
</p>
HTML;
            $closingAdvice = <<<HTML
<p style="margin:0 0 16px;text-indent:2em;">
    A complete admission packet will be sent to your registered email address in the coming days. We very much hope that you will decide to attend UTP, and we look forward to having you join us.
</p>
HTML;
        } elseif ($status === 'rejected') {
            $statusParagraph = <<<HTML
<p style="margin:0 0 16px;text-indent:2em;">
    Thank you for your interest in Universiti Teknologi PETRONAS and for taking the time to submit your application for the <strong>{$safeProg}</strong> programme. After careful consideration by the Admissions Committee, I regret to inform you that we are unable to offer you a place at this time.
</p>

<p style="margin:0 0 16px;text-indent:2em;">
    This year, we received a significant number of applications from many talented and highly qualified candidates. The decision was an exceptionally difficult one, and does not diminish the value of your academic achievements.
</p>
HTML;
            $closingAdvice = <<<HTML
<p style="margin:0 0 16px;text-indent:2em;">
    We encourage you to consider applying again in a future intake. Should you have any questions or require further clarification, please do not hesitate to contact the Office of Admissions.
</p>
HTML;
        } else {
            // processing / submitted
            $statusParagraph = <<<HTML
<p style="margin:0 0 16px;text-indent:2em;">
    I am writing to acknowledge that your application for the <strong>{$safeProg}</strong> programme has been received by the Admissions Committee and is currently <strong>under review</strong>.
</p>

<p style="margin:0 0 16px;text-indent:2em;">
    Each application is given careful and thorough consideration. We anticipate completing our review within the coming weeks and will notify you of the Committee's decision at the earliest opportunity.
</p>
HTML;
            $closingAdvice = <<<HTML
<p style="margin:0 0 16px;text-indent:2em;">
    In the meantime, should you have any questions or wish to provide additional documentation, please do not hesitate to contact us through your student dashboard.
</p>
HTML;
        }

        // Admin notes block (formal style)
        $notesHtml = '';
        if (!empty($adminNotes)) {
            $safeNotes = nl2br(htmlspecialchars($adminNotes));
            $notesHtml = <<<HTML
<div style="margin:16px 0 20px;padding:14px 20px;border-left:3px solid #1a1a2e;background:#fafaf8;">
    <p style="margin:0 0 4px;font-size:11.5px;font-weight:bold;color:#555;text-transform:uppercase;letter-spacing:1px;">Remarks from the Admissions Office:</p>
    <p style="margin:0;font-size:13.5px;color:#333;line-height:1.7;font-style:italic;">{$safeNotes}</p>
</div>
HTML;
        }

        $bodyHtml = <<<HTML
<p style="margin:0 0 20px;">Dear {$safeName},</p>

{$statusParagraph}

{$notesHtml}

{$closingAdvice}

<p style="margin:0 0 16px;text-indent:2em;">
    You may view the full details of your application status by logging in to your student portal at <a href="{$loginUrl}" style="color:#1a1a2e;text-decoration:underline;">utp.edu.my</a>.
</p>

<p style="margin:0 0 6px;">Yours sincerely,</p>
<p style="margin:0;font-weight:bold;">The Admissions Committee</p>
<p style="margin:0;font-size:12.5px;color:#555;">Office of Admissions and Financial Aid</p>
<p style="margin:0;font-size:12.5px;color:#555;">Universiti Teknologi PETRONAS</p>
HTML;
        $body = self::wrapLayout("Application update: {$programmeName} — {$status}", $bodyHtml);
        try {
            $mail = self::createMailer();
            $mail->addAddress($userEmail, $userName);
            $mail->Subject = "Application Status Update — Universiti Teknologi PETRONAS";
            $mail->Body = $body;
            $mail->AltBody = "Dear {$userName}, Your application for {$programmeName} has been updated to: {$status}. Log in at {$loginUrl} for details.";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('Status email failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Generate a formal password reset email body.
     */
    public static function buildPasswordResetEmail(string $userName, string $resetLink): string
    {
        $safeName = htmlspecialchars($userName);
        $safeLink = htmlspecialchars($resetLink);
        $bodyHtml = <<<HTML
<p style="margin:0 0 20px;">Dear {$safeName},</p>

<p style="margin:0 0 16px;text-indent:2em;">
    We received a request to reset the password associated with your account on the UTP Scholarship &amp; Course Eligibility System. If you made this request, please click the button below to set a new password:
</p>

<table role="presentation" cellpadding="0" cellspacing="0" style="margin:24px auto;">
    <tr>
        <td align="center" style="border-radius:8px;background-color:#e8630a;">
            <a href="{$safeLink}" target="_blank" style="display:inline-block;padding:14px 40px;font-family:'Inter',Arial,sans-serif;font-size:15px;font-weight:700;color:#ffffff;text-decoration:none;border-radius:8px;letter-spacing:0.3px;">Reset My Password</a>
        </td>
    </tr>
</table>

<p style="margin:0 0 20px;text-align:center;font-size:11.5px;color:#999;">
    If the button doesn't work, copy and paste this link into your browser:<br>
    <a href="{$safeLink}" style="color:#666;word-break:break-all;font-size:11px;">{$safeLink}</a>
</p>

<p style="margin:0 0 16px;text-indent:2em;">
    For security purposes, this link will expire in one (1) hour. After this period, you will need to submit a new request through the login page.
</p>

<p style="margin:0 0 16px;text-indent:2em;">
    If you did not request a password reset, please ignore this message. Your account remains secure and no changes have been made.
</p>

<p style="margin:0 0 6px;">Yours sincerely,</p>
<p style="margin:0;font-weight:bold;">UTP System Administration</p>
<p style="margin:0;font-size:12.5px;color:#555;">Universiti Teknologi PETRONAS</p>
HTML;
        return self::wrapLayout("Password reset request — UTP Scholarship System", $bodyHtml);
    }
}
