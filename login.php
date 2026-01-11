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
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, hsl(210, 20%, 98%), hsl(220, 20%, 95%));
            margin: 0;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            background: white;
            padding: 2.5rem;
            border-radius: 1.5rem;
            box-shadow:
                0 20px 25px -5px rgb(0 0 0 / 0.1),
                0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .app-logo {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, hsl(250, 84%, 65%), hsl(320, 80%, 60%));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.25rem;
            margin: 0 auto 1rem auto;
        }

        .login-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .login-subtitle {
            color: #64748b;
            font-size: 0.95rem;
        }

        .login-btn {
            width: 100%;
            padding: 0.875rem;
            font-size: 1rem;
            margin-top: 1rem;
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
    </style>
</head>

<body>

    <div class="login-card">
        <div class="login-header">
            <!-- <div class="app-logo">HR</div> -->
            <img src="assets/logo.png" alt="Myworld Logo"
                style="width: 80px; height: 80px; object-fit: contain; margin-bottom: 1rem; border-radius: 16px;">
            <h1 class="login-title">Welcome Back</h1>
            <p class="login-subtitle">Sign in to access your dashboard</p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="input-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="admin@company.com" required>
            </div>

            <div class="input-group">
                <label class="form-label">Password</label>
                <div style="position: relative;">
                    <input type="password" name="password" id="loginPassword" class="form-control"
                        placeholder="••••••••" required>
                    <button type="button" onclick="togglePasswordLogin()"
                        style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #64748b; padding: 0; display: flex; align-items: center;">
                        <i data-lucide="eye" id="eyeShow"></i>
                        <i data-lucide="eye-off" id="eyeHide" style="display: none;"></i>
                    </button>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <label
                    style="display: flex; align-items: center; gap: 0.5rem; font-size: 0.9rem; color: #64748b; cursor: pointer;">
                    <input type="checkbox" style="accent-color: var(--color-primary);"> Remember me
                </label>
                <a href="#"
                    style="font-size: 0.9rem; color: hsl(250, 84%, 60%); text-decoration: none; font-weight: 500;">Forgot
                    password?</a>
            </div>

            <button type="submit" class="btn-primary login-btn">Sign In</button>
        </form>
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