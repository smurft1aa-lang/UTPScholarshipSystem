<?php
/**
 * Role & Authorization Guard Component
 */

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
