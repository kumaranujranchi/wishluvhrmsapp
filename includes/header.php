<!DOCTYPE html>
<html lang="en">

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>HRMS Admin</title>
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
                <div class="search-bar">
                    <i data-lucide="search" class="icon" style="margin-right:0.75rem; color: #9ca3af;"></i>
                    <input type="text" placeholder="Search in HRMS...">
                    <span
                        style="font-size:0.75rem; background:#f3f4f6; padding:0.2rem 0.5rem; border-radius:4px; color:#6b7280;">CTRL
                        + K</span>
                </div>

                <div class="header-actions">
                    <button class="action-btn" title="Full Screen">
                        <i data-lucide="maximize" class="icon"></i>
                    </button>
                    <button class="action-btn" title="Messages">
                        <i data-lucide="message-square" class="icon"></i>
                        <span class="badge">3</span>
                    </button>
                    <button class="action-btn" title="Notifications">
                        <i data-lucide="bell" class="icon"></i>
                        <span class="badge warning">5</span>
                    </button>
                </div>
            </header>