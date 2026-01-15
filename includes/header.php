<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/logo.png">
    <link rel="shortcut icon" href="assets/logo.png" type="image/x-icon">

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#6366f1">
    <meta name="description" content="Myworld HRMS - Attendance, Leave, Payroll Management System">
    <link rel="manifest" href="manifest.json">

    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" href="assets/images/icon-192.png">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="HRMS">

    <!-- Social Share (Open Graph / Facebook) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Myworld HRMS">
    <meta property="og:description" content="Human Resource Management System - Attendance, Leave, Payroll">
    <meta property="og:image" content="assets/logo.png">

    <!-- Social Share (Twitter) -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Myworld HRMS">
    <meta name="twitter:description" content="Human Resource Management System - Attendance, Leave, Payroll">
    <meta name="twitter:image" content="assets/logo.png">

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

    <!-- Chatbot CSS -->
    <link rel="stylesheet" href="assets/css/chatbot.css">
</head>

<body>
    <div class="app-container">
        <!-- Sidebar Included Here -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <!-- Header (Desktop Only) -->
            <header class="header glass-panel desktop-only">
                <!-- Mobile: Profile Link (Left) -->
                <a href="profile.php" class="mobile-profile-link mobile-only"
                    style="margin-right: 12px; text-decoration: none;">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['first_name'] ?? 'User') ?>&background=ffd6a8&color=d97706&size=128"
                        alt="Profile"
                        style="width: 44px; height: 44px; border-radius: 50%; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                </a>

                <!-- Desktop: Hamburger (Hidden on mobile now as per request) -->
                <div class="mobile-hamburger-trigger" onclick="toggleMobileDrawer()"
                    style="display: none; cursor: pointer; margin-right: 15px; color: #1e293b;">
                    <i data-lucide="menu" style="width: 24px; height: 24px;"></i>
                </div>

                <div style="flex: 1;">
                    <h2 class="header-greeting" style="margin:0; font-size:1.1rem; color:#1e293b; font-weight: 700;">
                        <?php
                        $display_name = $_SESSION['first_name'] ?? explode(' ', $_SESSION['user_name'] ?? 'User')[0];
                        echo '<span class="desktop-greeting">' . htmlspecialchars($display_name) . '</span>';
                        echo '<span class="mobile-greeting">Hello, ' . htmlspecialchars($display_name) . '!</span>';
                        ?>
                    </h2>
                    <p class="header-date" style="margin:0; font-size:0.75rem; color:#64748b; font-weight: 500;">
                        <span class="desktop-date"><?= date('D, d M') ?></span>
                        <span class="mobile-date"><?= date('D, d M') ?> •
                            <?= date('H') < 12 ? 'Good Morning' : (date('H') < 17 ? 'Good Afternoon' : 'Good Evening') ?></span>
                    </p>
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
                    <a href="view_notices.php" class="notification-bell" title="Notices"
                        style="position: relative; text-decoration: none; color: #1e293b; background: #f8fafc; width: 45px; height: 45px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.03);">
                        <i data-lucide="bell" class="icon" style="width: 22px;"></i>
                        <?php if ($unread_count > 0): ?>
                            <span
                                style="position: absolute; top: 8px; right: 8px; background: #ef4444; color: white; border-radius: 50%; width: 18px; height: 18px; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; font-weight: 800; border: 2px solid white;">
                                <?= $unread_count ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </header>

            <style>
                /* Desktop default */
                .mobile-greeting,
                .mobile-date,
                .mobile-profile-link {
                    display: none;
                }

                .desktop-greeting,
                .desktop-date {
                    display: inline;
                }

                .notification-bell {
                    background: transparent !important;
                    width: auto !important;
                    height: auto !important;
                    box-shadow: none !important;
                }

                @media (max-width: 768px) {

                    /* Strict Hide Desktop Header */
                    .desktop-only {
                        display: none !important;
                    }

                    /* Mobile Override */
                    .desktop-greeting,
                    .desktop-date,
                    .mobile-hamburger-trigger {
                        display: none !important;
                    }

                    .mobile-greeting,
                    .mobile-date,
                    .mobile-profile-link {
                        display: inline-block !important;
                    }

                    .header {
                        padding: 12px 20px !important;
                        background: white !important;
                        box-shadow: none !important;
                        display: flex !important;
                        align-items: center !important;
                    }

                    .header h2 {
                        font-size: 1.1rem !important;
                        margin-bottom: 2px !important;
                    }

                    .header p {
                        font-size: 0.75rem !important;
                        margin: 0 !important;
                    }

                    .notification-bell {
                        width: 44px !important;
                        height: 44px !important;
                        background: white !important;
                        border-radius: 50% !important;
                        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05) !important;
                        border: 1px solid #f1f5f9 !important;
                    }
                }
            </style>