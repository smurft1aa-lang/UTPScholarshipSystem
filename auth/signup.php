<?php
/**
 * Sign Up Page
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

setSecurityHeaders();
initSession();

if (isLoggedIn()) {
    header('Location: /student/dashboard.php');
    exit;
}

$error = '';
$old = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        $fullName = sanitize($_POST['full_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $icNumber = sanitize($_POST['ic_number'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $old = ['full_name' => $fullName, 'email' => $email, 'ic_number' => $icNumber, 'phone' => $phone];

        // Validate
        if (empty($fullName) || empty($email) || empty($icNumber) || empty($phone) || empty($password)) {
            $error = 'Please fill in all fields.';
        } elseif (!validateEmail($email)) {
            $error = 'Please enter a valid email address.';
        } elseif (preg_match('/@icloud\.com$/i', $email)) {
            $error = 'iCloud email addresses are not accepted. Kindly use an alternative email address.';
        } elseif (!validateICNumber($icNumber)) {
            $error = 'Please enter a valid IC number (12 digits).';
        } elseif (!validatePhone($phone)) {
            $error = 'Please enter a valid phone number.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $pwErrors = validatePassword($password);
            if (!empty($pwErrors)) {
                $error = implode(' ', $pwErrors);
            } else {
                $result = registerUser($fullName, $email, $password, $icNumber, $phone);
                if ($result['success']) {
                    header('Location: /student/dashboard.php');
                    exit;
                } else {
                    $error = $result['error'];
                }
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
    <title>Sign Up - UTP System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/auth.css">
    <link rel="icon" type="image/x-icon" href="/assets/favicon.ico">

</head>
<body>
    <div class="split-layout">
        
        <!-- Right Panel containing the pattern illustration (Moved visually to the left) -->
        <div class="right-panel">
            <a href="/landing.php" class="brand">
                <span class="brand-icon"></span>
                UTP
            </a>
        </div>

        <!-- Left Panel containing the form (Moved visually to the right) -->
        <div class="left-panel">
            <a href="/landing.php" class="brand brand-mobile">
                <span class="brand-icon"></span>
                UTP
            </a>
            <div class="login-container">
                <h1>Create Account</h1>
                <p class="subtitle">Sign up to check your eligibility for UTP programmes</p>

                <?php if ($error): ?>
                    <div class="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST" data-validate="true">
                    <?= csrfField() ?>
                    
                    <div class="form-group">
                        <label class="form-label" for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-input" placeholder="As per IC" required value="<?= htmlspecialchars($old['full_name'] ?? '') ?>">
                        <div class="form-error" id="full_name_error"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-input" placeholder="your@email.com" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                            <div class="form-error" id="email_error"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-input" placeholder="01X-XXXXXXX" required value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                            <div class="form-error" id="phone_error"></div>
                        </div>
                    </div>
                    
                    <div class="form-group" style="margin-bottom:1.5rem;">
                        <label class="form-label" for="ic_number">IC Number</label>
                        <input type="text" id="ic_number" name="ic_number" class="form-input" placeholder="XXXXXX-XX-XXXX" required value="<?= htmlspecialchars($old['ic_number'] ?? '') ?>">
                        <div class="form-error" id="ic_number_error"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="password">Password</label>
                            <input type="password" id="password" name="password" class="form-input" data-validate="password" placeholder="Min 8 characters" required autocomplete="new-password">
                            <div class="form-error" id="password_error"></div>
                            <div id="password_strength" style="font-size:0.75rem;margin-top:4px;font-weight:600;color:#6b7280;position:absolute;top:100%;"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Re-enter password" required autocomplete="new-password">
                            <div class="form-error" id="confirm_password_error"></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Create Account</button>
                    
                </form>

                <div class="auth-footer">
                    Already have an account? <a href="/auth/login.php">Log In</a>
                </div>
            </div>
        </div>
    </div>
    <script nonce="<?= $GLOBALS['csp_nonce'] ?>" src="/assets/js/main.js"></script>
</body>
</html>
