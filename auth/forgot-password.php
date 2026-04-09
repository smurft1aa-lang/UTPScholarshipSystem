<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

setSecurityHeaders();
initSession();

if (isLoggedIn()) {
    header('Location: /student/dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $ip = getClientIP();
        
        // Rate limit resets (3 per hour)
        if (!checkRateLimit($ip . '_reset', 3, 60)) {
            $error = "Too many reset requests. Please try again later.";
        } else {
            recordLoginAttempt($ip . '_reset');
            
            $db = getDB();
            $stmt = $db->prepare("SELECT id, full_name FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Delete existing tokens
                $db->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$user['id']]);
                
                $token = bin2hex(random_bytes(32));
                $db->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))")->execute([$user['id'], $token]);
                
                $appUrl = getenv('APP_URL') ?: 'http://localhost';
                $resetLink = rtrim($appUrl, '/') . "/auth/reset-password.php?token=" . urlencode($token);
                
                require_once __DIR__ . '/../includes/mailer.php';
                try {
                    $mail = createMailer();
                    $mail->addAddress($email, $user['full_name']);
                    $mail->Subject = 'Reset Your Password — UTP Scholarship System';
                    $mail->Body = \UTP\Services\Mailer::buildPasswordResetEmail($user['full_name'], $resetLink);
                    $mail->AltBody = "Dear {$user['full_name']}, reset your password: {$resetLink}";
                    $mail->send();
                } catch (\Exception $mailEx) {
                    trackEvent('Password Reset Email Failed', ['email' => $email], 'WARNING');
                }

                $appEnv = getenv('APP_ENV') ?: 'production';
                if (in_array($appEnv, ['local', 'development'])) {
                    $success = "DEV MODE — Reset link (email not sent): <a href='" . htmlspecialchars($resetLink) . "' style='color:#f26522;font-weight:600;'>Click here to reset password</a>";
                } else {
                    $success = "If your email is registered, you will receive a reset link shortly.";
                }
            }
            // Always show success to prevent email enumeration
            if (empty($success)) {
                $success = "If your email is registered, you will receive a reset link shortly.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <a href="/" class="navbar-brand flex-center mb-6">
                <span class="brand-icon">U</span>
                UTP System
            </a>
            <h1>Forgot Password</h1>
            <p class="subtitle">Enter your email to receive a reset link</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php if (str_contains($success, '<a ')) { echo $success; } else { echo htmlspecialchars($success); } ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-orange btn-block btn-lg">Send Reset Link</button>
            </form>
            <p class="auth-footer" style="text-align:center; margin-top:16px;">
                <a href="/auth/login.php">Back to Login</a>
            </p>
        </div>
    </div>
</body>
</html>
