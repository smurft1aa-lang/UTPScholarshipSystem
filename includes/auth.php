<?php
/**
 * Authentication & Session Management
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

function sendVerificationEmail($userId, $email, $fullName) {
    $db = getDB();
    $db->prepare("DELETE FROM email_verifications WHERE user_id = ?")->execute([$userId]);

    $token = bin2hex(random_bytes(32));
    $db->prepare("INSERT INTO email_verifications (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))")->execute([$userId, $token]);

    $appUrl = getenv('APP_URL') ?: 'http://localhost';
    $mailFrom = getenv('MAIL_FROM') ?: 'noreply@utp.edu.my';
    $verifyLink = rtrim($appUrl, '/') . "/verify-email.php?token=" . urlencode($token);
    
    $subject = "Verify Your UTP Application Account";
    $message = "Hello $fullName,\n\nPlease verify your email address by clicking the link below:\n\n$verifyLink\n\nThis link will expire in 24 hours.\n\nThank you.";
    $headers = "From: $mailFrom\r\nReply-To: $mailFrom";
    @mail($email, $subject, $message, $headers);
}

function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_lifetime', 0);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        session_start();
        
        $timeout_duration = 1800; // 30 minutes
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
            session_unset();
            session_destroy();
            session_start();
        }
        $_SESSION['last_activity'] = time();
    }
}

function registerUser($fullName, $email, $password, $icNumber, $phone) {
    $db = getDB();

    // Check if email already exists
    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already registered.'];
    }

    // Check if IC already exists
    $cleanIC = preg_replace('/[-\s]/', '', $icNumber);
    $stmt = $db->prepare("SELECT id FROM users WHERE REPLACE(REPLACE(ic_number, '-', ''), ' ', '') = ?");
    $stmt->execute([$cleanIC]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'IC Number already registered.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $db->prepare("INSERT INTO users (full_name, email, password_hash, ic_number, phone, role, email_verified) VALUES (?, ?, ?, ?, ?, 'student', 0)");
    $stmt->execute([$fullName, $email, $hash, $icNumber, $phone]);

    $userId = $db->lastInsertId();

    require_once __DIR__ . '/audit.php';
    logAudit($userId, 'User Registered', 'User', $userId, "Email: $email");

    sendVerificationEmail($userId, $email, $fullName);

    initSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['role'] = 'student';
    $_SESSION['full_name'] = $fullName;
    $_SESSION['email_verified'] = 0;

    return ['success' => true, 'user_id' => $userId];
}

function loginUser($email, $password) {
    $db = getDB();
    $ip = getClientIP();

    if (!checkRateLimit($ip)) {
        return ['success' => false, 'error' => 'Too many login attempts. Please try again later.'];
    }

    $stmt = $db->prepare("SELECT id, full_name, email, password_hash, role, email_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        recordLoginAttempt($ip);
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }

    clearLoginAttempts($ip);

    initSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email_verified'] = $user['email_verified'];

    require_once __DIR__ . '/audit.php';
    logAudit($user['id'], 'User Logged In');

    return ['success' => true, 'role' => $user['role']];
}

function logoutUser() {
    initSession();
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/audit.php';
        logAudit($_SESSION['user_id'], 'User Logged Out');
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function isLoggedIn() {
    initSession();
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    initSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isStudent() {
    initSession();
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name']
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /student/dashboard.php');
        exit;
    }
}

function requireStudent() {
    requireLogin();
    if (!isStudent()) {
        header('Location: /admin/dashboard.php');
        exit;
    }
}

function isVerified() {
    initSession();
    return isset($_SESSION['email_verified']) && (int)$_SESSION['email_verified'] === 1;
}

function requireVerified() {
    requireStudent();
    if (!isVerified()) {
        $_SESSION['error'] = 'Please verify your email to access this page.';
        header('Location: /student/dashboard.php');
        exit;
    }
}
