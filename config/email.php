<?php
// Email Configuration for Hostinger
// SMTP Settings - Hostinger Email

define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587); // TLS port
define('SMTP_USERNAME', 'noreply@wishluvbuildcon.com');
define('SMTP_PASSWORD', 'Wishluv@2025');
define('SMTP_FROM_EMAIL', 'noreply@wishluvbuildcon.com');
define('SMTP_FROM_NAME', 'Myworld HRMS');

// Function to generate styled HTML email template
function getHtmlEmailTemplate($title, $bodyContent, $ctaUrl = null, $ctaText = null)
{
    $year = date('Y');

    // Button HTML
    $buttonHtml = '';
    if ($ctaUrl && $ctaText) {
        $buttonHtml = "
            <div style='text-align: center; margin: 30px 0;'>
                <a href='{$ctaUrl}' style='background-color: #4f46e5; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; display: inline-block;'>{$ctaText}</a>
            </div>
        ";
    }

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 0; line-height: 1.6; color: #334155; }
            .email-container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            .header { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); padding: 30px; text-align: center; color: #ffffff; }
            .content { padding: 40px 30px; }
            .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
            h1 { margin: 0; font-size: 24px; font-weight: 600; }
            p { margin-bottom: 15px; }
            .highlight { color: #4f46e5; font-weight: 600; }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>{$title}</h1>
            </div>
            <div class='content'>
                {$bodyContent}
                {$buttonHtml}
            </div>
            <div class='footer'>
                <p>&copy; {$year} Myworld HRMS by Wishluv Buildcon. All rights reserved.</p>
                <p>This is an automated notification. Please do not reply directly to this email.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

// Function to send email using PHPMailer
function sendEmail($to, $subject, $body)
{
    // Try multiple possible paths for PHPMailer
    $possible_paths = [
        __DIR__ . '/../vendor/PHPMailer/',
        __DIR__ . '/vendor/PHPMailer/',
        dirname(__DIR__) . '/vendor/PHPMailer/',
        $_SERVER['DOCUMENT_ROOT'] . '/vendor/PHPMailer/',
    ];

    $phpmailer_path = null;
    foreach ($possible_paths as $path) {
        if (file_exists($path . 'PHPMailer.php')) {
            $phpmailer_path = $path;
            break;
        }
    }

    if (!$phpmailer_path) {
        // Fallback or error
        // For now, if we can't find it, we might want to just return false or log valid error
        return false;
    }

    require_once $phpmailer_path . 'PHPMailer.php';
    require_once $phpmailer_path . 'SMTP.php';
    require_once $phpmailer_path . 'Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        //$mail->SMTPDebug = 2; // Disable debug for production usage in scripts
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;

        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );

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
        // Log error if needed: error_log($mail->ErrorInfo);
        return false;
    }
}

// Function to generate 6-digit OTP
function generateOTP()
{
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to send OTP email (Updated to use new template)
function sendOTPEmail($email, $name, $otp)
{
    $subject = "Password Reset OTP - Myworld HRMS";
    $content = "
        <p>Hello <strong>{$name}</strong>,</p>
        <p>We received a request to reset your password. Use the following OTP to proceed:</p>
        
        <div style='background: #f0f9ff; border: 2px dashed #3b82f6; border-radius: 8px; padding: 20px; text-align: center; margin: 20px 0;'>
            <div style='color: #64748b; font-size: 14px; margin-bottom: 10px;'>Your OTP Code</div>
            <div style='font-size: 32px; font-weight: bold; color: #1e40af; letter-spacing: 8px;'>{$otp}</div>
            <div style='color: #64748b; font-size: 12px; margin-top: 10px;'>Valid for 10 minutes</div>
        </div>
        
        <p style='color: #ef4444; font-size: 14px;'><strong>⚠️ Security Notice:</strong> If you didn't request this, please ignore this email.</p>
    ";

    $body = getHtmlEmailTemplate("Password Reset Request", $content);
    return sendEmail($email, $subject, $body);
}
?>