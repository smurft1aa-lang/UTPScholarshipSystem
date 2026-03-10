<?php
/**
 * Sign Up Page
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';

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
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: #ffffff;
            color: #111111;
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animations */
        @keyframes fadeSlideRight {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Split Screen Layout */
        .split-layout {
            display: flex;
            width: 100%;
            min-height: 100vh;
            flex-direction: row; /* Natural order: illustration first, then form */
        }
        
        /* Left Panel - Form (now visually on right) */
        .left-panel {
            flex: 1;
            width: 50%;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            animation: fadeSlideRight 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Right Panel - Illustration (now visually on left) */
        .right-panel {
            flex: 1;
            width: 50%;
            background-color: #111111;
            /* Dense grid pattern of abstract human figures */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cpath fill='%230a0a0a' d='M20 16c0-3.3 2.7-6 6-6s6 2.7 6 6-2.7 6-6 6-6-2.7-6-6zm-8 10c-2.2 0-4 1.8-4 4v18h4v8h6v-14h4v14h6v-8h4V30c0-2.2-1.8-4-4-4H12z'%3E%3C/path%3E%3Cpath fill='%23050505' d='M60 10c0-2.2 1.8-4 4-4s4 1.8 4 4-1.8 4-4 4-4-1.8-4-4zm-6 8c-1.7 0-3 1.3-3 3v12h3v18h4V34h3v17h4V33h3v-9c0-1.7-1.3-3-3-3H54zM8 8c0-1.7 1.8-3 4-3s4 1.3 4 3-1.8 3-4 3-4-1.3-4-3zM3 15c-1.7 0-3 1.3-3 3v9h3v18h3V28h3v17h3V26h3v-8c0-1.7-1.3-3-3-3H3z'%3E%3C/path%3E%3Cpath fill='%23080808' d='M40 30c0-2.8 2.2-5 5-5s5 2.2 5 5-2.2 5-5 5-5-2.2-5-5zm-6 8c-1.7 0-3 1.3-3 3v14h3v15h4V52h4v18h4V55h3V41c0-1.7-1.3-3-3-3H34z'%3E%3C/path%3E%3C/svg%3E");
            background-size: 80px 80px;
            background-repeat: repeat;
            border-radius: 0;
            position: relative;
            opacity: 0;
            animation: fadeIn 1s ease-out 0.2s forwards;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 0 1rem; /* Prevent overflowing to edges on smaller screens */
        }

        /* Brand / Header */
        .brand {
            position: absolute;
            top: 2.5rem;
            left: 2.5rem;
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 1.25rem;
            color: #ffffff; /* White text on dark illustration side */
            text-decoration: none;
            letter-spacing: -0.02em;
            z-index: 10;
        }
        .brand:hover {
            opacity: 0.9;
        }
        .brand-icon {
            width: 14px;
            height: 14px;
            background-color: #f26522;
            display: inline-block;
            margin-right: 10px;
        }

        /* Typography */
        h1 {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 0.5rem;
            color: #111111;
        }
        .subtitle {
            font-size: 0.95rem;
            color: #6b7280;
            margin-bottom: 2.5rem;
            font-weight: 400;
            line-height: 1.4;
        }

        /* Alerts */
        .alert {
            padding: 1rem 1.25rem;
            margin-bottom: 2rem;
            font-size: 0.9rem;
            background-color: #fff;
            color: #111111;
            border-left: 3px solid #ef4444;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 1.25rem;
            position: relative;
        }
        .form-row {
            display: flex;
            gap: 1rem;
        }
        .form-row .form-group {
            flex: 1;
        }
        .form-label {
            display: block;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #111111;
        }
        .form-input {
            width: 100%;
            padding: 0.5rem 0;
            font-size: 0.95rem;
            font-family: inherit;
            color: #111111;
            background: transparent;
            border: none;
            border-bottom: 1px solid #d0d0d0;
            outline: none;
            transition: border-color 0.2s ease;
            border-radius: 0;
        }
        .form-input:focus {
            border-bottom-color: #111111;
        }
        .form-error {
            color: #ef4444;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            position: absolute;
        }
        
        /* Buttons */
        .btn-submit {
            width: 100%;
            background-color: #111111;
            color: #ffffff;
            border: 1px solid #111111;
            padding: 1.1rem;
            font-size: 1rem;
            font-weight: 700;
            font-family: inherit;
            cursor: pointer;
            border-radius: 0;
            transition: all 0.2s ease;
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .btn-submit:hover {
            background-color: #ffffff;
            color: #111111;
        }

        /* Footer Links */
        .auth-footer {
            margin-top: 2rem;
            text-align: left;
            font-size: 0.9rem;
            color: #6b7280;
        }
        .auth-footer a {
            color: #111111;
            font-weight: 600;
            text-decoration: none;
        }
        .auth-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            h1 {
                font-size: 3rem;
            }
            .split-layout {
                flex-direction: column;
            }
            .right-panel {
                display: none;
            }
            .left-panel {
                padding: 1.5rem;
                padding-top: 6rem; /* extra space for absolute brand */
                animation: none;
            }
            .brand {
                position: absolute;
                top: 1.5rem;
                left: 1.5rem;
                color: #111111; /* Switch back to black on mobile since right panel is hidden */
            }
            .login-container {
                margin-top: 2rem;
            }
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="split-layout">
        
        <!-- Right Panel containing the pattern illustration (Moved visually to the left) -->
        <div class="right-panel">
            <a href="/landing.html" class="brand">
                <span class="brand-icon"></span>
                UTP
            </a>
        </div>

        <!-- Left Panel containing the form (Moved visually to the right) -->
        <div class="left-panel">
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
                            <input type="password" id="password" name="password" class="form-input" data-validate="password" placeholder="Min 8 characters" required>
                            <div class="form-error" id="password_error"></div>
                            <div id="password_strength" style="font-size:0.75rem;margin-top:4px;font-weight:600;color:#6b7280;position:absolute;top:100%;"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" placeholder="Re-enter password" required>
                            <div class="form-error" id="confirm_password_error"></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">Create Account</button>
                    
                </form>

                <div class="auth-footer">
                    Already have an account? <a href="/login.php">Log In</a>
                </div>
            </div>
        </div>
    </div>
    <script src="/assets/js/main.js"></script>
</body>
</html>
