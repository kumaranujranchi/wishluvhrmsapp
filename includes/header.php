<!DOCTYPE html>
<html lang="en">

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                <div style="flex: 1;">
                    <h2 style="margin:0; font-size:1.25rem; color:#1e293b;">Welcome,
                        <?php
                           $display_name = $_SESSION['first_name'] ?? explode(' ', $_SESSION['user_name'] ?? 'User')[0];
                           echo htmlspecialchars($display_name);
                        ?>
                    </h2>
                    <p style="margin:0; font-size:0.85rem; color:#64748b;"><?= date('l, d F Y') ?></p>
                </div>

                <div class="header-actions">
                    <button class="action-btn" title="Notifications">
                        <i data-lucide="bell" class="icon"></i>
                    </button>
                    <!-- User Icon Removed as per request -->
                </div>
            </header>