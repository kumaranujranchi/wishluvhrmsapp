<!DOCTYPE html>
<html lang="en">

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- PWA Meta Tags -->
<meta name="theme-color" content="#6366f1">
<meta name="description" content="Myworld HRMS - Attendance, Leave, Payroll Management System">
<link rel="manifest" href="/manifest.json">

<!-- Apple Touch Icons -->
<link rel="apple-touch-icon" href="/assets/images/icon-192.png">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="HRMS">

<title>Myworld Admin</title>
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Main CSS -->
<link rel="stylesheet" href="assets/css/style.css">

<!-- Lucide Icons (Script for development/demo purposes) -->
<script src="https://unpkg.com/lucide@latest"></script>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar Included Here -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <!-- Header Included Here -->
            <header class="header glass-panel">
                <div class="mobile-hamburger-trigger" onclick="toggleMobileDrawer()"
                    style="display: none; cursor: pointer; margin-right: 15px; color: #1e293b;">
                    <i data-lucide="menu" style="width: 24px; height: 24px;"></i>
                </div>

                <div style="flex: 1;">
                    <h2 style="margin:0; font-size:1.1rem; color:#1e293b; font-weight: 700;">
                        <?php
                        $display_name = $_SESSION['first_name'] ?? explode(' ', $_SESSION['user_name'] ?? 'User')[0];
                        echo htmlspecialchars($display_name);
                        ?>
                    </h2>
                    <p style="margin:0; font-size:0.75rem; color:#64748b; font-weight: 500;"><?= date('D, d M') ?></p>
                </div>

                <div class="header-actions">
                    <button class="action-btn" title="Notifications">
                        <i data-lucide="bell" class="icon" style="width: 20px;"></i>
                    </button>
                </div>
            </header>

            <style>
                @media (max-width: 768px) {
                    .mobile-hamburger-trigger {
                        display: block !important;
                    }
                }
            </style>