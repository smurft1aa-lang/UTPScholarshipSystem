<?php
define('APP_ENV', 'testing');
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/RateLimiter.php';
require_once __DIR__ . '/includes/UserAuth.php';

$db = getDB();

echo "Testing programmes:\n";
$stmt = $db->query("SELECT id, name FROM programmes ORDER BY id ASC");
$progs = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($progs as $p) {
    if (strpos(strtolower($p['name']), 'chemical') !== false) {
        echo "- Found Chemical Eng: ID {$p['id']} - {$p['name']}\n";
    }
}

echo "\nTesting Rate Limiter:\n";
$db->exec("DELETE FROM login_attempts");
for ($i=1; $i<=6; $i++) {
    loginUser('admin@test.com', 'wrongpassword');
    $stmt = $db->query("SELECT COUNT(*) FROM login_attempts");
    $count = $stmt->fetchColumn();
    $limitState = checkRateLimit('127.0.0.1') ? 'OK' : 'BLOCKED';
    echo "Attempt $i: DB Count=$count, LimitState=$limitState\n";
}
$result = loginUser('admin@test.com', 'wrongpassword');
echo "Result on 7th: " . $result['error'] . "\n";
