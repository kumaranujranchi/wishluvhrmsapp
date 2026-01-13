<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db.php';
require_once 'config/email.php';

$message = "";
$step = $_GET['step'] ?? 'request'; // request, verify, reset

// Step 1: Request OTP (Enter Email & Employee Code)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_otp'])) {
    $email = trim($_POST['email']);
    $employee_code = trim($_POST['employee_code']);

    try {
        $stmt = $conn->prepare("SELECT id, first_name, last_name, email FROM employees WHERE email = :email AND employee_code = :code");
        $stmt->execute(['email' => $email, 'code' => $employee_code]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate OTP
            $otp = generateOTP();
            $expires_at = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            // Store OTP in database
            $stmt = $conn->prepare("INSERT INTO password_reset_otps (employee_id, email, otp, expires_at) VALUES (:emp_id, :email, :otp, :expires)");
            $stmt->execute([
                'emp_id' => $user['id'],
                'email' => $email,
                'otp' => $otp,
                'expires' => $expires_at
            ]);

            // Send OTP via email
            try {
                $emailSent = sendOTPEmail($email, $user['first_name'] . ' ' . $user['last_name'], $otp);

                if ($emailSent) {
                    $_SESSION['reset_email'] = $email;
                    header("Location: forgot_password_otp.php?step=verify");
                    exit;
                } else {
                    $message = "<div class='error-msg'>Failed to send OTP email. Please check email configuration.</div>";
                }
            } catch (Exception $e) {
                $message = "<div class='error-msg'>Email Error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        } else {
            $message = "<div class='error-msg'>Invalid email or employee code.</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='error-msg'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    } catch (Exception $e) {
        $message = "<div class='error-msg'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

// Step 2: Verify OTP
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_otp'])) {
    $otp = trim($_POST['otp']);
    $email = $_SESSION['reset_email'] ?? '';

    if (!$email) {
        header("Location: forgot_password_otp.php?step=request");
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT * FROM password_reset_otps WHERE email = :email AND otp = :otp AND is_used = 0 AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1");
        $stmt->execute(['email' => $email, 'otp' => $otp]);
        $otp_record = $stmt->fetch();

        if ($otp_record) {
            $_SESSION['verified_otp_id'] = $otp_record['id'];
            $_SESSION['reset_employee_id'] = $otp_record['employee_id'];
            header("Location: forgot_password_otp.php?step=reset");
            exit;
        } else {
            $message = "<div class='error-msg'>Invalid or expired OTP. Please try again.</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='error-msg'>Error: " . $e->getMessage() . "</div>";
    }
}

// Step 3: Reset Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (!isset($_SESSION['verified_otp_id']) || !isset($_SESSION['reset_employee_id'])) {
        header("Location: forgot_password_otp.php?step=request");
        exit;
    }

    if ($new_password !== $confirm_password) {
        $message = "<div class='error-msg'>Passwords do not match.</div>";
    } elseif (strlen($new_password) < 6) {
        $message = "<div class='error-msg'>Password must be at least 6 characters long.</div>";
    } else {
        try {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE employees SET password = :password WHERE id = :id");
            $stmt->execute(['password' => $hashed_password, 'id' => $_SESSION['reset_employee_id']]);

            // Mark OTP as used
            $stmt = $conn->prepare("UPDATE password_reset_otps SET is_used = 1 WHERE id = :id");
            $stmt->execute(['id' => $_SESSION['verified_otp_id']]);

            // Clear session
            unset($_SESSION['reset_email'], $_SESSION['verified_otp_id'], $_SESSION['reset_employee_id']);

            $message = "<div class='success-msg'>Password reset successful! You can now login.</div>";
            $step = 'success';
        } catch (PDOException $e) {
            $message = "<div class='error-msg'>Error: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Myworld</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, hsl(210, 20%, 98%), hsl(220, 20%, 95%));
            margin: 0;
            font-family: 'Outfit', sans-serif;
        }

        .reset-card {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .reset-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .reset-subtitle {
            color: #64748b;
            font-size: 0.9rem;
        }

        .error-msg {
            background-color: #fee2e2;
            color: #991b1b;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .success-msg {
            background-color: #dcfce7;
            color: #166534;
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #334155;
            font-size: 0.9rem;
        }

        .input-group {
            margin-bottom: 1.25rem;
        }

        .reset-btn {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            margin-top: 1rem;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-link a {
            color: hsl(250, 84%, 60%);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .otp-input {
            text-align: center;
            font-size: 1.5rem;
            letter-spacing: 0.5rem;
            font-weight: 600;
        }

        .info-box {
            background: #f0f9ff;
            border-left: 4px solid #3b82f6;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            color: #1e40af;
        }

        @media (max-width: 640px) {
            body {
                background: #ffffff;
                align-items: stretch;
            }

            .reset-card {
                padding: 3rem 2rem;
                border-radius: 0;
                box-shadow: none;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                max-width: none;
            }

            .reset-title {
                font-size: 2rem;
                text-align: left;
            }

            .reset-subtitle {
                text-align: left;
                font-size: 1.05rem;
                margin-bottom: 2rem;
            }

            .form-control {
                padding: 1.1rem 1rem;
                border-radius: 12px;
                background: #f1f5f9;
                border: 2px solid transparent;
            }

            .form-control:focus {
                background: #ffffff;
                border-color: hsl(250, 84%, 60%);
            }

            .reset-btn {
                padding: 1.25rem;
                border-radius: 12px;
                font-weight: 600;
            }

            .back-link {
                margin-top: auto;
                padding: 2rem 0;
            }
        }
    </style>
</head>

<body>
    <div class="reset-card">
        <div class="reset-header">
            <img src="assets/logo.png" alt="Myworld Logo"
                style="width: 60px; height: 60px; object-fit: contain; margin-bottom: 1rem; border-radius: 12px;">
            <h1 class="reset-title">
                <?php if ($step == 'success'): ?>
                    Password Reset!
                <?php elseif ($step == 'reset'): ?>
                    Set New Password
                <?php elseif ($step == 'verify'): ?>
                    Verify OTP
                <?php else: ?>
                    Forgot Password?
                <?php endif; ?>
            </h1>
            <p class="reset-subtitle">
                <?php if ($step == 'success'): ?>
                    Your password has been successfully reset.
                <?php elseif ($step == 'reset'): ?>
                    Enter your new password below
                <?php elseif ($step == 'verify'): ?>
                    Enter the 6-digit OTP sent to your email
                <?php else: ?>
                    We'll send you an OTP to reset your password
                <?php endif; ?>
            </p>
        </div>

        <?= $message ?>

        <?php if ($step == 'success'): ?>
            <div style="text-align: center;">
                <a href="login.php" class="btn-primary"
                    style="display: inline-block; text-decoration: none; padding: 0.875rem 2rem;">
                    <i data-lucide="log-in" style="width: 18px; vertical-align: middle; margin-right: 8px;"></i>
                    Go to Login
                </a>
            </div>

        <?php elseif ($step == 'reset'): ?>
            <form method="POST">
                <div class="input-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="new_password" class="form-control" placeholder="Enter new password"
                        required minlength="6">
                </div>

                <div class="input-group">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password"
                        required minlength="6">
                </div>

                <button type="submit" name="reset_password" class="btn-primary reset-btn">
                    Reset Password
                </button>
            </form>

        <?php elseif ($step == 'verify'): ?>
            <div class="info-box">
                <i data-lucide="mail" style="width: 16px; vertical-align: middle; margin-right: 8px;"></i>
                OTP has been sent to <strong>
                    <?= htmlspecialchars($_SESSION['reset_email'] ?? '') ?>
                </strong>
            </div>

            <form method="POST">
                <div class="input-group">
                    <label class="form-label">Enter OTP</label>
                    <input type="text" name="otp" class="form-control otp-input" placeholder="000000" required maxlength="6"
                        pattern="[0-9]{6}">
                    <small style="color: #64748b; font-size: 0.75rem;">Valid for 10 minutes</small>
                </div>

                <button type="submit" name="verify_otp" class="btn-primary reset-btn">
                    Verify OTP
                </button>
            </form>

            <div style="text-align: center; margin-top: 1rem;">
                <a href="forgot_password_otp.php?step=request"
                    style="color: #64748b; font-size: 0.85rem; text-decoration: none;">
                    Didn't receive OTP? Request again
                </a>
            </div>

        <?php else: ?>
            <form method="POST">
                <div class="input-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" placeholder="your.email@company.com" required>
                </div>

                <div class="input-group">
                    <label class="form-label">Employee Code</label>
                    <input type="text" name="employee_code" class="form-control" placeholder="e.g., WB001" required>
                    <small style="color: #64748b; font-size: 0.75rem;">Enter your employee code for verification</small>
                </div>

                <button type="submit" name="request_otp" class="btn-primary reset-btn">
                    Send OTP
                </button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="login.php">
                <i data-lucide="arrow-left" style="width: 14px; vertical-align: middle;"></i>
                Back to Login
            </a>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>

</html>