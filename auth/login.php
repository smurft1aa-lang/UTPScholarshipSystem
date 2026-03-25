<?php
/**
 * Login Page
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">

</head>
<body>
    <div class="split-layout">
        <!-- Left Panel containing the form -->
        <div class="left-panel">
            <a href="/landing.php" class="brand">
                <span class="brand-icon"></span>
                UTP
            </a>

            <div class="login-container">
                <h1>Welcome Back</h1>
                <p class="subtitle">Log in to your UTP application account</p>

                <?php if ($error): ?>
                    <div class="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <?= csrfField() ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-input" placeholder="name@example.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required autocomplete="current-password">
                    </div>
                    
                    <button type="submit" class="btn-submit">Log In</button>
                    
                    <a href="/auth/forgot-password.php" class="forgot-password">Forgot password?</a>
                </form>

                <div class="auth-footer">
                    Don't have an account? <a href="/auth/signup.php">Sign Up</a>
                </div>
                <!-- Optional: kept for backwards compatibility if they had a back link -->
                <div class="back-link">
                    <a href="/landing.php">&larr; Go back to landing page</a>
                </div>
            </div>
        </div>

        <!-- Right Panel containing the pattern illustration -->
        <div class="right-panel"></div>
    </div>
</body>
</html>
