<?php
session_start();
require_once 'config/db.php';

$message = "";
$step = $_GET['step'] ?? 'request'; // request, verify, reset

// Step 1: Request Reset (Enter Email & Employee Code)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);
    $employee_code = trim($_POST['employee_code']);

    try {
        $stmt = $conn->prepare("SELECT id, first_name, email FROM employees WHERE email = :email AND employee_code = :code");
        $stmt->execute(['email' => $email, 'code' => $employee_code]);
        $user = $stmt->fetch();

        if ($user) {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Store token in session (simple approach without email)
            $_SESSION['reset_token'] = $reset_token;
            $_SESSION['reset_user_id'] = $user['id'];
            $_SESSION['reset_expires'] = $expires_at;

            header("Location: forgot_password.php?step=reset&token=" . $reset_token);
            exit;
        } else {
            $message = "<div class='error-msg'>Invalid email or employee code.</div>";
        }
    } catch (PDOException $e) {
        $message = "<div class='error-msg'>Error: " . $e->getMessage() . "</div>";
    }
}

// Step 2: Reset Password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_password'])) {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Validate token
    if (!isset($_SESSION['reset_token']) || $_SESSION['reset_token'] !== $token) {
        $message = "<div class='error-msg'>Invalid or expired reset link.</div>";
    } elseif (strtotime($_SESSION['reset_expires']) < time()) {
        $message = "<div class='error-msg'>Reset link has expired. Please request a new one.</div>";
        unset($_SESSION['reset_token'], $_SESSION['reset_user_id'], $_SESSION['reset_expires']);
    } elseif ($new_password !== $confirm_password) {
        $message = "<div class='error-msg'>Passwords do not match.</div>";
    } elseif (strlen($new_password) < 6) {
        $message = "<div class='error-msg'>Password must be at least 6 characters long.</div>";
    } else {
        try {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE employees SET password = :password WHERE id = :id");
            $stmt->execute(['password' => $hashed_password, 'id' => $_SESSION['reset_user_id']]);

            // Clear session
            unset($_SESSION['reset_token'], $_SESSION['reset_user_id'], $_SESSION['reset_expires']);

            $message = "<div class='success-msg'>Password reset successful! You can now login.</div>";
            $step = 'success';
        } catch (PDOException $e) {
            $message = "<div class='error-msg'>Error: " . $e->getMessage() . "</div>";
        }
    }
}

// Verify token for reset step
if ($step == 'reset' && isset($_GET['token'])) {
    if (!isset($_SESSION['reset_token']) || $_SESSION['reset_token'] !== $_GET['token']) {
        $message = "<div class='error-msg'>Invalid reset link.</div>";
        $step = 'request';
    } elseif (strtotime($_SESSION['reset_expires']) < time()) {
        $message = "<div class='error-msg'>Reset link has expired.</div>";
        unset($_SESSION['reset_token'], $_SESSION['reset_user_id'], $_SESSION['reset_expires']);
        $step = 'request';
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
                <?php else: ?>
                    Forgot Password?
                <?php endif; ?>
            </h1>
            <p class="reset-subtitle">
                <?php if ($step == 'success'): ?>
                    Your password has been successfully reset.
                <?php elseif ($step == 'reset'): ?>
                    Enter your new password below
                <?php else: ?>
                    Enter your email and employee code to reset
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
                <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token']) ?>">

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

                <button type="submit" name="request_reset" class="btn-primary reset-btn">
                    Continue
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