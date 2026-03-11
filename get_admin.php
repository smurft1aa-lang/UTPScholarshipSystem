<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();
$stmt = $db->query("SELECT email FROM users WHERE role='admin' LIMIT 1");
$admin = $stmt->fetch();
echo "ADMIN_EMAIL: " . ($admin ? $admin['email'] : 'NONE');
