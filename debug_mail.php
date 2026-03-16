<?php
require_once __DIR__ . '/includes/init.php';

// Show all errors
ini_set('display_errors', 1);
error_reporting(E_ALL);

requireLogin();

$userId = $_SESSION['user_id'];
$db = getDB();
$stmt = $db->prepare("SELECT email_verified, email, full_name FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

echo "<h3>User info:</h3>";
echo "Name: " . htmlspecialchars($user['full_name']) . "<br>";
echo "Email: " . htmlspecialchars($user['email']) . "<br>";
echo "Verified: " . ($user['email_verified'] ? 'Yes' : 'No') . "<br><br>";

echo "<h3>Attempting to send email...</h3>";

$result = sendVerificationEmail($userId, $user['email'], $user['full_name']);

if ($result) {
    echo "<h2 style='color:green'>✅ Email sent! Check Mailtrap now.</h2>";
} else {
    echo "<h2 style='color:red'>❌ Email failed to send.</h2>";
    echo "<p>Check your PHP error log for details.</p>";
}
?>
