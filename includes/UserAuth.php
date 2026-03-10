<?php
/**
 * User Authentication Component
 */

function registerUser($fullName, $email, $password, $icNumber, $phone) {
    $db = getDB();

    $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'Email already registered.'];
    }

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

    if (function_exists('initTelemetry')) initTelemetry();

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

    if (function_exists('initTelemetry')) initTelemetry();

    require_once __DIR__ . '/audit.php';
    logAudit($user['id'], 'User Logged In');

    return ['success' => true, 'role' => $user['role']];
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'role' => $_SESSION['role'],
        'full_name' => $_SESSION['full_name']
    ];
}
