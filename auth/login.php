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
        }
        
        /* Left Panel - Form */
        .left-panel {
            flex: 1;
            background-color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
            animation: fadeSlideRight 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        /* Right Panel - Illustration */
        .right-panel {
            flex: 1;
            background-color: #111111;
            /* Dense grid pattern of abstract human figures */
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='80' height='80' viewBox='0 0 80 80'%3E%3Cpath fill='%230a0a0a' d='M20 16c0-3.3 2.7-6 6-6s6 2.7 6 6-2.7 6-6 6-6-2.7-6-6zm-8 10c-2.2 0-4 1.8-4 4v18h4v8h6v-14h4v14h6v-8h4V30c0-2.2-1.8-4-4-4H12z'%3E%3C/path%3E%3Cpath fill='%23050505' d='M60 10c0-2.2 1.8-4 4-4s4 1.8 4 4-1.8 4-4 4-4-1.8-4-4zm-6 8c-1.7 0-3 1.3-3 3v12h3v18h4V34h3v17h4V33h3v-9c0-1.7-1.3-3-3-3H54zM8 8c0-1.7 1.8-3 4-3s4 1.3 4 3-1.8 3-4 3-4-1.3-4-3zM3 15c-1.7 0-3 1.3-3 3v9h3v18h3V28h3v17h3V26h3v-8c0-1.7-1.3-3-3-3H3z'%3E%3C/path%3E%3Cpath fill='%23080808' d='M40 30c0-2.8 2.2-5 5-5s5 2.2 5 5-2.2 5-5 5-5-2.2-5-5zm-6 8c-1.7 0-3 1.3-3 3v14h3v15h4V52h4v18h4V55h3V41c0-1.7-1.3-3-3-3H34z'%3E%3C/path%3E%3C/svg%3E");
            background-size: 80px 80px;
            background-repeat: repeat;
            border-radius: 0;
            opacity: 0;
            animation: fadeIn 1s ease-out 0.2s forwards;
        }

        .login-container {
            width: 100%;
            max-width: 380px;
            display: flex;
            flex-direction: column;
            justify-content: center;
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
            color: #111111;
            text-decoration: none;
            letter-spacing: -0.02em;
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
            margin-bottom: 1.75rem;
        }
        .form-label {
            display: block;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111111;
        }
        .form-input {
            width: 100%;
            padding: 0.75rem 0;
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
            margin-top: 1rem;
            margin-bottom: 1.5rem;
        }
        .btn-submit:hover {
            background-color: #ffffff;
            color: #111111;
        }

        .forgot-password {
            display: block;
            text-align: center;
            font-size: 0.85rem;
            color: #111111;
            text-decoration: none;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }

        /* Footer Links */
        .auth-footer {
            margin-top: 3rem;
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
        
        .back-link {
            text-align: left;
            margin-top: 1rem;
        }
        .back-link a {
            font-weight: 400;
            color: #6b7280;
            text-decoration: none;
            font-size: 0.9rem;
        }
        .back-link a:hover {
            text-decoration: underline;
            color: #111111;
        }

        /* Responsive */
        @media (max-width: 768px) {
            h1 {
                font-size: 3rem;
            }
            .right-panel {
                display: none;
            }
            .left-panel {
                padding: 1.5rem;
                animation: none; /* simple display on mobile */
            }
            .brand {
                position: absolute;
                top: 1.5rem;
                left: 1.5rem;
            }
            .login-container {
                margin-top: 5rem;
            }
        }
    </style>
</head>
<body>
    <div class="split-layout">
        <!-- Left Panel containing the form -->
        <div class="left-panel">
            <a href="/landing.html" class="brand">
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
                        <input type="password" id="password" name="password" class="form-input" placeholder="Enter your password" required>
                    </div>
                    
                    <button type="submit" class="btn-submit">Log In</button>
                    
                    <a href="/auth/forgot-password.php" class="forgot-password">Forgot password?</a>
                </form>

                <div class="auth-footer">
                    Don't have an account? <a href="/auth/signup.php">Sign Up</a>
                </div>
                <!-- Optional: kept for backwards compatibility if they had a back link -->
                <div class="back-link">
                    <a href="/landing.html">&larr; Go back to landing page</a>
                </div>
            </div>
        </div>

        <!-- Right Panel containing the pattern illustration -->
        <div class="right-panel"></div>
    </div>
</body>
</html>
