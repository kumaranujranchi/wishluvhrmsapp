<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $conn->prepare("
                SELECT e.*, d.name as designation_name 
                FROM employees e 
                LEFT JOIN designations d ON e.designation_id = d.id 
                WHERE e.email = :email
            ");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login Success - Regenerate session ID for security
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];
                // Store designation if available, else fallback to Role
                $_SESSION['user_designation'] = !empty($user['designation_name']) ? $user['designation_name'] : $user['role'];

                // Explicitly set cache-control headers to prevent stale dashboards
                header("Pragma: no-cache");

                $role = trim($user['role']); // Ensure no whitespace issues
                if (strtolower($role) === 'employee') {
                    header("Location: employee_dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "System Error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Myworld HRMS</title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="shortcut icon" href="assets/logo.png" type="image/x-icon">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Delius:wght@400&display=swap" rel="stylesheet">
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#6366f1">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="assets/images/icon-192.png">

    <!-- Social Share (Open Graph / Facebook) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Login - Myworld HRMS">
    <meta property="og:description" content="Sign in to your Myworld HRMS account">
    <meta property="og:image" content="assets/logo.png">

    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="login-page">

    <div class="login-card-app">
        <div class="login-header">
            <div class="login-logo-container">
                <img src="assets/logo.png" alt="Myworld HRMS" class="login-brand-logo">
            </div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to your HRMS account</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <i data-lucide="alert-circle" style="width: 18px; height: 18px;"></i>
                <span><?= $error ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="login-form">
            <div class="input-group">
                <label class="form-label">Email Address</label>
                <div class="input-wrapper">
                    <i data-lucide="mail" class="input-icon"></i>
                    <input type="email" name="email" class="form-control form-input-enhanced"
                        placeholder="anuj.kumar@wishluvbuildcon.com" required>
                </div>
            </div>

            <div class="input-group">
                <label class="form-label">Password</label>
                <div class="input-wrapper">
                    <i data-lucide="lock" class="input-icon"></i>
                    <input type="password" name="password" id="loginPassword" class="form-control form-input-enhanced"
                        placeholder="••••••••" required>
                    <button type="button" onclick="togglePasswordLogin()" class="password-toggle">
                        <i data-lucide="eye" id="eyeShow"></i>
                        <i data-lucide="eye-off" id="eyeHide" style="display: none;"></i>
                    </button>
                </div>
            </div>

            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" class="custom-checkbox">
                    <span>Remember me</span>
                </label>
                <a href="forgot_password_otp.php" class="forgot-link">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-primary login-btn">
                <span>SIGN IN</span>
                <i data-lucide="arrow-right" style="width: 20px; height: 20px;"></i>
            </button>
        </form>

        <div class="login-footer">
            &copy; <?= date('Y') ?> Myworld HRMS. All rights reserved.
        </div>
    </div>

    <style>
        html,
        body.login-page {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            -webkit-overflow-scrolling: touch;
        }

        body.login-page {
            font-family: 'Delius', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            position: relative;
            touch-action: pan-y;
        }

        body.login-page::before {
            content: '';
            position: absolute;
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            top: -250px;
            left: -250px;
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
            z-index: 0;
        }

        body.login-page::after {
            content: '';
            position: absolute;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.08) 0%, transparent 70%);
            border-radius: 50%;
            bottom: -200px;
            right: -200px;
            animation: float 15s ease-in-out infinite reverse;
            pointer-events: none;
            z-index: 0;
        }

        @keyframes gradientShift {
            0% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }

            100% {
                background-position: 0% 50%;
            }
        }

        @keyframes float {

            0%,
            100% {
                transform: translate(0, 0) rotate(0deg);
            }

            33% {
                transform: translate(30px, -30px) rotate(120deg);
            }

            66% {
                transform: translate(-20px, 20px) rotate(240deg);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card-app {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.2) inset;
            position: relative;
            z-index: 1;
            animation: slideUp 0.6s ease-out;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .login-logo-container {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: center;
        }

        .login-brand-logo {
            height: 80px;
            width: auto;
            filter: drop-shadow(0 4px 12px rgba(99, 102, 241, 0.3));
            animation: logoFloat 3s ease-in-out infinite;
        }

        @keyframes logoFloat {

            0%,
            100% {
                transform: translateY(0px);
            }

            50% {
                transform: translateY(-10px);
            }
        }

        .login-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .login-subtitle {
            font-size: 0.95rem;
            color: #64748b;
            font-weight: 400;
        }

        .error-msg {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-left: 4px solid #ef4444;
            color: #991b1b;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideUp 0.4s ease-out;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .input-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.25rem;
        }

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            width: 20px;
            height: 20px;
            color: #94a3b8;
            pointer-events: none;
            z-index: 1;
        }

        .form-input-enhanced {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.95rem;
            font-family: 'Delius', sans-serif;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .form-input-enhanced:focus {
            outline: none;
            border-color: #667eea;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        .form-input-enhanced::placeholder {
            color: #cbd5e1;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            padding: 0.5rem;
            display: flex;
            align-items: center;
            transition: color 0.2s ease;
        }

        .password-toggle:hover {
            color: #667eea;
        }

        .password-toggle i {
            width: 20px;
            height: 20px;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: -0.5rem;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
            cursor: pointer;
            user-select: none;
        }

        .custom-checkbox {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
            cursor: pointer;
        }

        .forgot-link {
            font-size: 0.875rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .forgot-link:hover {
            color: #764ba2;
        }

        .login-btn {
            width: 100%;
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px -5px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px -5px rgba(102, 126, 234, 0.5);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
            font-size: 0.85rem;
            color: #94a3b8;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        /* Mobile UI Enhancement - App-like Experience */
        @media (max-width: 640px) {
            body.login-page {
                background: #ffffff;
                align-items: flex-start;
                overflow-y: auto !important;
                overflow-x: hidden !important;
            }

            body.login-page::before,
            body.login-page::after {
                display: none;
            }

            .login-card-app {
                margin: 0;
                padding: 2.5rem 1.5rem;
                width: 100%;
                max-width: none;
                min-height: 100vh;
                border-radius: 0;
                box-shadow: none;
                background: transparent;
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                animation: none;
                backdrop-filter: none;
            }

            .login-header {
                margin-bottom: 2rem;
                text-align: left;
            }

            .login-logo-container {
                margin-bottom: 1.5rem;
                justify-content: flex-start;
            }

            .login-brand-logo {
                height: 50px;
                filter: none;
            }

            .login-title {
                font-size: 1.75rem;
                text-align: left;
            }

            .login-subtitle {
                text-align: left;
                font-size: 0.95rem;
            }

            .login-form {
                gap: 1.25rem;
            }

            .input-group {
                gap: 0.35rem;
            }

            .form-label {
                font-size: 0.85rem;
            }

            .form-input-enhanced {
                padding: 0.75rem 1rem 0.75rem 3rem;
                font-size: 0.95rem;
                border-radius: 12px;
                background: #f1f5f9;
                border: 2px solid transparent;
            }

            .form-input-enhanced:focus {
                background: #ffffff;
                border-color: #667eea;
            }

            .input-icon {
                left: 1rem;
                width: 18px;
                height: 18px;
            }

            .login-btn {
                padding: 0.875rem;
                border-radius: 12px;
                font-size: 1rem;
                margin-top: 0.75rem;
            }

            .login-footer {
                margin-top: 3rem;
                padding: 1.5rem 0;
                border-top: 1px solid #f1f5f9;
                font-size: 0.8rem;
            }

            .form-options {
                margin-top: -0.25rem;
            }
        }
    </style>

    <script>
        function togglePasswordLogin() {
            const passwordInput = document.getElementById('loginPassword');
            const showIcon = document.getElementById('eyeShow');
            const hideIcon = document.getElementById('eyeHide');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                showIcon.style.display = 'none';
                hideIcon.style.display = 'block';
            } else {
                passwordInput.type = 'password';
                showIcon.style.display = 'block';
                hideIcon.style.display = 'none';
            }
        }
        lucide.createIcons();
    </script>
    <script src="/assets/js/pwa.js"></script>
</body>

</html>