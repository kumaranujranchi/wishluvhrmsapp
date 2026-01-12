<?php
// Test Email Sending
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';
require_once 'config/email.php';

echo "<h2>Email Configuration Test</h2>";

// Check PHPMailer files
$phpmailer_path = __DIR__ . '/vendor/PHPMailer/';
echo "<p>PHPMailer Path: " . $phpmailer_path . "</p>";
echo "<p>PHPMailer.php exists: " . (file_exists($phpmailer_path . 'PHPMailer.php') ? 'YES ✅' : 'NO ❌') . "</p>";
echo "<p>SMTP.php exists: " . (file_exists($phpmailer_path . 'SMTP.php') ? 'YES ✅' : 'NO ❌') . "</p>";
echo "<p>Exception.php exists: " . (file_exists($phpmailer_path . 'Exception.php') ? 'YES ✅' : 'NO ❌') . "</p>";

echo "<hr>";

// Check email configuration
echo "<h3>SMTP Configuration:</h3>";
echo "<p>Host: " . SMTP_HOST . "</p>";
echo "<p>Port: " . SMTP_PORT . "</p>";
echo "<p>Username: " . SMTP_USERNAME . "</p>";
echo "<p>From Email: " . SMTP_FROM_EMAIL . "</p>";
echo "<p>From Name: " . SMTP_FROM_NAME . "</p>";

echo "<hr>";

// Test OTP generation
echo "<h3>OTP Generation Test:</h3>";
$test_otp = generateOTP();
echo "<p>Generated OTP: <strong>" . $test_otp . "</strong></p>";

echo "<hr>";

// Test database connection for OTP table
echo "<h3>Database Test:</h3>";
try {
    $stmt = $conn->query("SHOW TABLES LIKE 'password_reset_otps'");
    $table_exists = $stmt->fetch();
    echo "<p>password_reset_otps table exists: " . ($table_exists ? 'YES ✅' : 'NO ❌ - Run migration_otp.sql') . "</p>";
} catch (PDOException $e) {
    echo "<p style='color: red;'>Database Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test email sending (uncomment to test)
echo "<h3>Email Sending Test:</h3>";
echo "<p><strong>To test email sending, uncomment the code below and refresh this page.</strong></p>";

/*
try {
    $test_email = "your-test-email@example.com"; // Change this
    $result = sendOTPEmail($test_email, "Test User", "123456");
    echo "<p style='color: green;'>Email sent successfully! ✅</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Email Error: " . $e->getMessage() . "</p>";
}
*/

echo "<hr>";
echo "<p><a href='forgot_password_otp.php'>Go to Forgot Password Page</a></p>";
?>