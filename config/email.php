<?php
// Email Configuration for Hostinger
// SMTP Settings - Update these with your Hostinger email credentials

define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465); // or 587 for TLS
define('SMTP_USERNAME', 'noreply@yourcompany.com'); // Your Hostinger email
define('SMTP_PASSWORD', 'your-email-password'); // Your email password
define('SMTP_FROM_EMAIL', 'noreply@yourcompany.com');
define('SMTP_FROM_NAME', 'Myworld HRMS');

// Function to send email using PHPMailer
function sendEmail($to, $subject, $body)
{
    require_once 'vendor/PHPMailer/PHPMailer.php';
    require_once 'vendor/PHPMailer/SMTP.php';
    require_once 'vendor/PHPMailer/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
        $mail->Port = SMTP_PORT;

        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to generate 6-digit OTP
function generateOTP()
{
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to send OTP email
function sendOTPEmail($email, $name, $otp)
{
    $subject = "Password Reset OTP - Myworld HRMS";

    $body = "
    <html>
    <head>
        <style>
            body { font-family: 'Arial', sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 30px; text-align: center; color: white; }
            .content { padding: 30px; }
            .otp-box { background: #f0f9ff; border: 2px dashed #3b82f6; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0; }
            .otp-code { font-size: 32px; font-weight: bold; color: #1e40af; letter-spacing: 8px; }
            .footer { background: #f8fafc; padding: 20px; text-align: center; color: #64748b; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1 style='margin: 0;'>Password Reset Request</h1>
            </div>
            <div class='content'>
                <p>Hello <strong>{$name}</strong>,</p>
                <p>We received a request to reset your password for your Myworld HRMS account.</p>
                <p>Use the following OTP to reset your password:</p>
                
                <div class='otp-box'>
                    <div style='color: #64748b; font-size: 14px; margin-bottom: 10px;'>Your OTP Code</div>
                    <div class='otp-code'>{$otp}</div>
                    <div style='color: #64748b; font-size: 12px; margin-top: 10px;'>Valid for 10 minutes</div>
                </div>
                
                <p style='color: #ef4444; font-size: 14px;'><strong>⚠️ Security Notice:</strong> If you didn't request this password reset, please ignore this email or contact your administrator immediately.</p>
                
                <p style='color: #64748b; font-size: 14px;'>This is an automated email. Please do not reply.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Myworld HRMS by Wishluv Buildcon. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    return sendEmail($email, $subject, $body);
}
?>