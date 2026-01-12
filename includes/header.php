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

// Prevent caching of pages with user-specific data
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!-- Google Fonts -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Delius&family=Outfit:wght@300;400;500;600;700&display=swap"
    rel="stylesheet">

<!-- Main CSS -->
<link rel="stylesheet" href="assets/css/style.css?v=1.2">

<!-- Lucide Icons -->
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
                    <?php
                    // Fetch unread notice count
                    $unread_stmt = $conn->prepare("
                        SELECT COUNT(*) FROM notices n 
                        WHERE n.id NOT IN (SELECT notice_id FROM notice_reads WHERE employee_id = :uid)
                    ");
                    $unread_stmt->execute(['uid' => $_SESSION['user_id']]);
                    $unread_count = $unread_stmt->fetchColumn();
                    ?>
                    <a href="view_notices.php" class="action-btn" title="Notices"
                        style="position: relative; text-decoration: none; color: inherit;">
                        <i data-lucide="bell" class="icon" style="width: 20px;"></i>
                        <?php if ($unread_count > 0): ?>
                            <span
                                style="position: absolute; top: -5px; right: -5px; background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 800; border: 2px solid white;">
                                <?= $unread_count ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </header>

            <style>
                @media (max-width: 768px) {
                    .mobile-hamburger-trigger {
                        display: block !important;
                    }
                }
            </style>