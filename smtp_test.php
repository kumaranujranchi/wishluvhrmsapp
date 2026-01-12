<?php
// Simple SMTP Test - Direct PHPMailer Test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Direct SMTP Connection Test</h2>";

// Try multiple possible paths for PHPMailer
$possible_paths = [
    __DIR__ . '/vendor/PHPMailer/',
    __DIR__ . '/../vendor/PHPMailer/',
    dirname(__DIR__) . '/vendor/PHPMailer/',
    $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/',
];

$phpmailer_path = null;
foreach ($possible_paths as $path) {
    if (file_exists($path . 'PHPMailer.php')) {
        $phpmailer_path = $path;
        echo "<p style='color: green;'>✅ PHPMailer found at: " . htmlspecialchars($path) . "</p>";
        break;
    }
}

if (!$phpmailer_path) {
    echo "<div style='background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px;'>";
    echo "<h3>❌ PHPMailer Not Found!</h3>";
    echo "<p>Searched in these locations:</p><ul>";
    foreach ($possible_paths as $path) {
        echo "<li>" . htmlspecialchars($path) . "</li>";
    }
    echo "</ul>";
    echo "<p><strong>Solution:</strong> Upload the <code>vendor/PHPMailer/</code> folder to your server.</p>";
    echo "</div>";
    exit;
}

require_once $phpmailer_path . 'PHPMailer.php';
require_once $phpmailer_path . 'SMTP.php';
require_once $phpmailer_path . 'Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Enable verbose debug output
    $mail->isSMTP();
    $mail->Host = 'smtp.hostinger.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'noreply@wishluvbuildcon.com';
    $mail->Password = 'Wishluv@2025';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    // Disable SSL verification
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );

    // Recipients
    $mail->setFrom('noreply@wishluvbuildcon.com', 'Myworld HRMS');
    $mail->addAddress('test@example.com'); // Change this to your test email

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from HRMS';
    $mail->Body = '<h1>Test Email</h1><p>If you receive this, SMTP is working!</p>';

    echo "<pre>";
    $mail->send();
    echo "</pre>";
    echo '<div style="background: #dcfce7; color: #166534; padding: 1rem; border-radius: 8px; margin-top: 1rem;">Email sent successfully! ✅</div>';
} catch (Exception $e) {
    echo "<pre>";
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}\n";
    echo "Exception: {$e->getMessage()}\n";
    echo "</pre>";
    echo '<div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; margin-top: 1rem;">Email failed! ❌</div>';
}
?>