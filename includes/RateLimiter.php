<?php
/**
 * Rate Limiter Component
 */

function checkRateLimit($ip, $maxAttempts = 5, $windowMinutes = 1) {
    $db = getDB();

    $threshold = date('Y-m-d H:i:s', strtotime("-$windowMinutes minutes"));

    $stmt = $db->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
    $stmt->execute([$threshold]);

    $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > ?");
    $stmt->execute([$ip, $threshold]);
    $count = $stmt->fetchColumn();

    return $count < $maxAttempts;
}

function recordLoginAttempt($ip) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
    $stmt->execute([$ip]);
}

function clearLoginAttempts($ip) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->execute([$ip]);
}
