<?php
/**
 * Login Page
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

setSecurityHeaders();
initSession();

if (isLoggedIn()) {
    header('Location: ' . (isAdmin() ? '/admin/dashboard.php' : '/student/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Please fill in all fields.';
        } else {
            $result = loginUser($email, $password);
            if ($result['success']) {
                if ($result['role'] === 'admin') {
                    header('Location: /admin/dashboard.php');
                } else {
                    header('Location: /student/dashboard.php');
                }
                exit;
            } else {
                $error = $result['error'];
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
    <title>Log In - UTP System</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="auth-page">
        <div class="auth-card">
            <a href="/" class="navbar-brand flex-center mb-6">
                <span class="brand-icon">U</span>
                UTP System
            </a>
            <h1>Welcome Back</h1>
            <p class="subtitle">Log in to check your eligibility</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" data-validate="true">
                <?= csrfField() ?>
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-input" placeholder="Enter your email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <div class="form-error" id="email_error"></div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                    <div class="form-error" id="password_error"></div>
                </div>
                <div style="text-align: right; margin-bottom: 16px;">
                    <a href="/forgot-password.php" style="font-size: 0.85rem; color: var(--text-secondary);">Forgot password?</a>
                </div>
                <button type="submit" class="btn btn-orange btn-block btn-lg">Log In</button>
            </form>

            <p class="auth-footer">
                Don't have an account? <a href="/signup.php">Sign Up</a>
            </p>
        </div>
    </div>
    <script src="/assets/js/main.js"></script>
</body>
</html>
