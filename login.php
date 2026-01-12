<?php
session_start();
require_once 'config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $conn->prepare("SELECT * FROM employees WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Login Success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_email'] = $user['email'];

                header("Location: index.php");
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
    <title>Login - Myworld</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://unpkg.com/lucide@latest"></script>
</head>

<body class="login-page">

    <div class="login-card-app">
        <div class="login-header">
            <div class="login-logo-app">MW</div>
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to your HRMS account</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@company.com" required
                    style="border-radius: 12px; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0;">
            </div>

            <div class="input-group">
                <label class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="loginPassword" class="form-control"
                        placeholder="••••••••" required
                        style="border-radius: 12px; padding: 0.75rem 1rem; border: 1.5px solid #e2e8f0;">
                    <button type="button" onclick="togglePasswordLogin()"
                        style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; padding: 0; display: flex; align-items: center;">
                        <i data-lucide="eye" id="eyeShow" style="width: 20px;"></i>
                        <i data-lucide="eye-off" id="eyeHide" style="display: none; width: 20px;"></i>
                    </button>
                </div>
            </div>

            <div
                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; margin-top: 0.5rem;">
                <label
                    style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: #64748b; cursor: pointer;">
                    <input type="checkbox"
                        style="accent-color: #6366f1; width: 16px; height: 16px; border-radius: 4px;"> Remember me
                </label>
                <a href="forgot_password_otp.php"
                    style="font-size: 0.85rem; color: #6366f1; text-decoration: none; font-weight: 600;">Forgot
                    Password?</a>
            </div>

            <button type="submit" class="btn-primary login-btn"
                style="width: 100%; border-radius: 12px; padding: 1rem; font-weight: 700; background: linear-gradient(135deg, #6366f1, #a855f7); border: none; box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);">
                SIGN IN
            </button>
        </form>

        <div style="text-align: center; margin-top: 2rem; font-size: 0.85rem; color: #94a3b8;">
            &copy; <?= date('Y') ?> Myworld HRMS. All rights reserved.
        </div>
    </div>

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
</body>

</html>