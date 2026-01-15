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
                        echo '<span class="desktop-greeting">' . htmlspecialchars($display_name) . '</span>';
                        echo '<span class="mobile-greeting">Hello, ' . htmlspecialchars($display_name) . '!</span>';
                        ?>
                    </h2>
                    <p style="margin:0; font-size:0.75rem; color:#64748b; font-weight: 500;">
                        <span class="desktop-date"><?= date('D, d M') ?></span>
                        <span class="mobile-date"><?= date('D, d M') ?> • <?= date('H') < 12 ? 'Good Morning' : (date('H') < 17 ? 'Good Afternoon' : 'Good Evening') ?></span>
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
                /* Desktop - show desktop greeting, hide mobile */
                .mobile-greeting, .mobile-date {
                    display: none;
                }
                .desktop-greeting, .desktop-date {
                    display: inline;
                }

                @media (max-width: 768px) {
                    /* Mobile - show mobile greeting, hide desktop */
                    .desktop-greeting, .desktop-date {
                        display: none;
                    }
                    .mobile-greeting, .mobile-date {
                        display: inline;
                    }

                    .mobile-hamburger-trigger {
                        display: block !important;
                    }

                    .header {
                        padding: 12px 16px !important;
                        background: white !important;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.03) !important;
                    }

                    .header h2::before {
                        content: '';
                        display: inline-block;
                        width: 44px;
                        height: 44px;
                        border-radius: 50%;
                        background: linear-gradient(135deg, #6366f1, #a855f7);
                        margin-right: 12px;
                        vertical-align: middle;
                        background-image: url('https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['first_name'] ?? 'User') ?>&background=random&color=fff&size=128');
                        background-size: cover;
                        border: 2px solid #f1f5f9;
                    }

                    .header h2 {
                        font-size: 1.05rem !important;
                        display: flex;
                        align-items: center;
                    }

                    .header h2::after {
                        content: 'Hello, ';
                        position: absolute;
                        left: -9999px;
                    }

                    .header p {
                        margin-left: 56px !important;
                        font-size: 0.72rem !important;
                    }

                    .header .action-btn {
                        width: 44px !important;
                        height: 44px !important;
                        background: #f8fafc !important;
                        border-radius: 50% !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        box-shadow: 0 4px 10px rgba(0,0,0,0.04) !important;
                    }

                    .header .action-btn i {
                        width: 22px !important;
                        height: 22px !important;
                        color: #1e293b !important;
                    }

                    .header .action-btn span {
                        top: 6px !important;
                        right: 6px !important;
                    }
                }
            </style>