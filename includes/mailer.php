<?php
/**
 * Consolidated Email Notifications
 */

function sendVerificationEmail(string $userId, string $userEmail, string $userName): bool {
    if (getenv('APP_ENV') === 'testing') return true;

    $mailFrom = getenv('MAIL_FROM') ?: 'noreply@utp.edu.my';
    $systemUrl = getenv('APP_URL') ?: 'http://localhost';
    
    $subject = "UTP System - Verify Your Email";
    $token = bin2hex(random_bytes(32));
    
    // In a real app, you would save this token to the user record for validation
    
    $message = "
    <html>
    <body>
        <h2>Welcome to UTP Scholarship & Course Eligibility System</h2>
        <p>Dear " . htmlspecialchars($userName) . ",</p>
        <p>Please click the link below to verify your email address:</p>
        <p><a href='$systemUrl/auth/verify.php?token=$token&id=$userId'>Verify Email Address</a></p>
    </body>
    </html>
    ";

    $headers = [
        "MIME-Version: 1.0",
        "Content-type: text/html; charset=utf-8",
        "From: $mailFrom",
        "Reply-To: $mailFrom"
    ];

    return @mail($userEmail, $subject, $message, implode("\r\n", $headers));
}

function sendApplicationStatusEmail(string $userEmail, string $userName, string $status, string $programmeName, string $adminNotes = ''): bool {
    if (getenv('APP_ENV') === 'testing') return true;

    $mailFrom = getenv('MAIL_FROM') ?: 'noreply@utp.edu.my';
    $systemUrl = getenv('APP_URL') ?: 'http://localhost';

    $subject = "UTP Application Status Update: " . ucfirst($status);
    
    $statusColor = '#3f51b5'; // processing blue
    if ($status === 'approved') {
        $statusColor = '#4caf50'; // success green
    } elseif ($status === 'rejected') {
        $statusColor = '#f44336'; // danger red
    }

    $message = "
    <html>
    <head>
        <style>
            body { font-family: 'Inter', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px; }
            .header { background: #f8f9fa; padding: 15px; text-align: center; border-bottom: 2px solid #f26522; font-weight: bold; font-size: 1.2rem; }
            .status-badge { display: inline-block; padding: 6px 12px; color: white; background-color: $statusColor; border-radius: 4px; font-weight: 600; text-transform: uppercase; font-size: 0.9rem; }
            .content { padding: 20px 0; }
            .footer { margin-top: 30px; font-size: 0.85rem; color: #777; text-align: center; border-top: 1px solid #eee; padding-top: 15px; }
            .btn { display: inline-block; padding: 10px 20px; background: #f26522; color: #fff; text-decoration: none; border-radius: 4px; font-weight: 500; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>Universiti Teknologi PETRONAS</div>
            <div class='content'>
                <p>Dear <strong>" . htmlspecialchars($userName) . "</strong>,</p>
                <p>There has been an update regarding your recent application for <strong>" . htmlspecialchars($programmeName) . "</strong>.</p>
                
                <p>Your application status is now: <span class='status-badge'>" . htmlspecialchars($status) . "</span></p>
                ";

    if (!empty($adminNotes)) {
        $message .= "<div style='background: #f9f9f9; border-left: 4px solid #ccc; padding: 10px 15px; margin: 20px 0; font-style: italic;'>
                    <strong>Admin Notes:</strong><br>" . nl2br(htmlspecialchars($adminNotes)) . "
                  </div>";
    }

    $message .= "
                <p>To view your full application history and status, please log in to your dashboard:</p>
                <p style='text-align: center; margin: 30px 0;'>
                    <a href='$systemUrl/auth/login.php' class='btn'>Go to Dashboard</a>
                </p>
                <p>Thank you.</p>
            </div>
            <div class='footer'>
                This is an automated message from the UTP Scholarship & Course Eligibility System.<br>
                Please do not reply directly to this email.
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = [
        "MIME-Version: 1.0",
        "Content-type: text/html; charset=utf-8",
        "From: $mailFrom",
        "Reply-To: $mailFrom"
    ];

    $mailSent = @mail($userEmail, $subject, $message, implode("\r\n", $headers));
    
    if (!$mailSent) {
        if (function_exists('trackEvent')) {
            trackEvent('Status Email Failed', ['email' => $userEmail, 'status' => $status], 'WARNING');
        }
    } else {
        if (function_exists('trackEvent')) {
            trackEvent('Status Email Sent', ['email' => $userEmail, 'status' => $status]);
        }
    }
    
    return $mailSent;
}
