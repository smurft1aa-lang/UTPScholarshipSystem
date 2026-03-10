<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

setSecurityHeaders();
initSession();

$token = $_GET['token'] ?? '';
if (empty($token) && empty($_POST['token'])) {
    die("Invalid or missing token.");
}
$token = $_POST['token'] ?? $token;

$db = getDB();
$stmt = $db->prepare("SELECT id, user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$record = $stmt->fetch();

if (!$record) {
    $_SESSION['error'] = "Invalid or expired password reset link.";
    header("Location: /auth/forgot-password.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        
        $pwErrors = validatePassword($password);
        if ($password !== $confirm) {
            $error = "Passwords do not match.";
        } else if (!empty($pwErrors)) {
            $error = implode('<br>', $pwErrors);
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $record['user_id']]);
            $db->prepare("DELETE FROM password_resets WHERE user_id = ?")->execute([$record['user_id']]);
            
            require_once __DIR__ . '/../includes/audit.php';
            logAudit($record['user_id'], 'Password Reset Successful');
            
            $_SESSION['success'] = "Password reset successfully. You can now log in.";
            header("Location: /auth/login.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set New Password</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <h1>Set New Password</h1>
            <p class="subtitle">Please provide a new strong password.</p>
            <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
            
            <form method="POST">
                <?= csrfField() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-group">
                    <label class="form-label" for="password">New Password</label>
                    <input type="password" id="password" name="password" class="form-input" required>
                    <small style="color:var(--text-muted);">Min 8 characters, 1 uppercase, 1 lowercase, 1 number, 1 special character.</small>
                </div>
                <div class="form-group">
                    <label class="form-label" for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-orange btn-block btn-lg">Reset Password</button>
            </form>
        </div>
    </div>
</body>
</html>
