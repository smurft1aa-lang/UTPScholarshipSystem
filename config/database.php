<?php
/**
 * Database Configuration
 * MySQL connection via PDO — credentials loaded from .env
 */

// Load .env file (lightweight, no Composer dependency needed)
function loadEnv($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = array_map('trim', explode('=', $line, 2));
        if (!getenv($key)) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

// Init telemetry globally before any DB interaction
require_once __DIR__ . '/../includes/telemetry.php';

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'utp_scholarship');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        startTimer('db_connect');
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            $time = endTimer('db_connect');
            if ($time > 200) {
                trackEvent('Slow DB Connection', ['time_ms' => $time], 'WARNING');
            }
        } catch (PDOException $e) {
            http_response_code(500);
            trackEvent('Database Connection Failed', ['exception' => $e], 'CRITICAL');
            if (getenv('APP_ENV') === 'testing') throw $e;
            die('Database connection failed. Please check your configuration.');
        }
    }
    return $pdo;
}
