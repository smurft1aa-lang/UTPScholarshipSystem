<?php
session_start();
require_once __DIR__ . '/config/database.php';
$db = getDB();
$stmt = $db->query("SELECT id, role FROM users WHERE role='admin' LIMIT 1");
$admin = $stmt->fetch();
if ($admin) {
    $_SESSION['user_id'] = $admin['id'];
    $_SESSION['role'] = $admin['role'];
    header('Location: /admin/applications.php');
} else {
    echo "No admin found.";
}
