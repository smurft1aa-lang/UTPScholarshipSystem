<?php
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'sandbox.smtp.mailtrap.io';
    $mail->SMTPAuth = true;
    $mail->Username = '270b329b66ac79';
    $mail->Password = '9645c8d7d84b60';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 2525;

    $mail->setFrom('noreply@utp.edu.my', 'UTP System');
    $mail->addAddress('testuser@example.com', 'Test User');
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from UTP System';
    $mail->Body = '<h2>It works!</h2><p>Email is configured correctly.</p>';

    $mail->send();
    echo '<h2 style="color:green">✅ SUCCESS! Email sent — check your Mailtrap inbox now.</h2>';
}
catch (Exception $e) {
    echo '<h2 style="color:red">❌ FAILED</h2>';
    echo '<p><strong>Error:</strong> ' . htmlspecialchars($mail->ErrorInfo) . '</p>';
}
?>