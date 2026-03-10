<?php
/**
 * Database Setup Script
 * Run this once to create the database and seed data
 * Usage: php setup_db.php
 */

require_once __DIR__ . '/config/database.php';

$host = DB_HOST;
$port = DB_PORT;
$user = DB_USER;
$pass = DB_PASS;

echo "Connecting to MySQL on port {$port}...\n";

try {
    $pdo = new PDO("mysql:host={$host};port={$port}", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected successfully.\n";
} catch (PDOException $e) {
    die("Connection failed. Please check your .env configuration.\n");
}

// Read and execute setup.sql
$sqlFile = __DIR__ . '/sql/setup.sql';
if (!file_exists($sqlFile)) {
    die("setup.sql not found at: {$sqlFile}\n");
}

$sql = file_get_contents($sqlFile);

// Generate the bcrypt hash for admin password
$adminHash = password_hash('Admin@1234', PASSWORD_BCRYPT, ['cost' => 12]);

// Replace the placeholder hash in the SQL
$sql = preg_replace(
    "/'\\\$2y\\\$12\\\$[^']+'/",
    "'" . $adminHash . "'",
    $sql,
    1
);

// Split into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($s) { return !empty($s) && $s !== ''; }
);

echo "Executing " . count($statements) . " SQL statements...\n";

$count = 0;
foreach ($statements as $stmt) {
    try {
        $pdo->exec($stmt);
        $count++;
    } catch (PDOException $e) {
        // Skip duplicate errors on re-run
        if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'already exists') === false) {
            echo "Warning: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($stmt, 0, 80) . "...\n\n";
        }
    }
}

echo "Executed {$count} statements successfully.\n";
echo "\nDatabase 'utp_scholarship' is ready.\n";
echo "Admin login: admin@utp.edu.my (password set during setup)\n";
echo "\nYou can now access the system at http://localhost/\n";
echo "(Make sure Apache is running and DocumentRoot points to this project)\n";
