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
    <link rel="stylesheet" href="assets/css/chatbot.css?v=2.0">

    <style>
        /* Header Styles */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 25px;
        }

        .header-greeting {
            margin: 0;
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 700;
        }

        .header-date {
            margin: 0;
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 500;
        }

        .action-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            text-decoration: none;
            border: none;
            cursor: pointer;
            position: relative;
        }

        .action-icon-btn i {
            width: 20px;
            height: 20px;
        }

        .badge {
            position: absolute;
            top: -2px;
            right: -2px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.65rem;
            font-weight: 800;
            border: 2px solid white;
        }

        .profile-link-mobile img {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        /* Mobile/Desktop Visibility Toggles */
        .mobile-only {
            display: none !important;
        }

        .desktop-only {
            display: inline-block !important;
        }

        /* Mobile Specifics */
        @media (max-width: 768px) {
            .mobile-only {
                display: inline-block !important;
            }

            .desktop-only {
                display: none !important;
            }

            /* Force Hide Sidebar */
            .sidebar {
                display: none !important;
            }

            /* Ensure Main Content is Visible */
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                display: block !important;
            }

            .header {
                padding: 15px 20px !important;
                background: white !important;
                position: sticky;
                top: 0;
                z-index: 1001;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            }

            .action-icon-btn {
                background: #f8fafc;
                border: 1px solid #f1f5f9;
                color: #475569;
            }

            .header-actions {
                display: flex;
                align-items: center;
                gap: 8px;
            }
        }
    </style>

    <script>
        function toggleMobileChat() {
            const cw = document.getElementById('chatWindow');
            if (cw) cw.classList.toggle('active');
        }
    </script>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar Included Here -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <!-- Header (Unified Responsive) -->
            <header class="header glass-panel">
                <!-- Mobile: Profile Link (Left) -->
                <a href="profile.php" class="mobile-only profile-link-mobile">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['first_name'] ?? 'User') ?>&background=ffd6a8&color=d97706&size=128"
                        alt="Profile">
                </a>

                <!-- Desktop: Hamburger (Hidden on mobile) -->
                <div class="desktop-only hamburger-trigger" onclick="toggleSidebar()" style="cursor: pointer;">
                    <i data-lucide="menu"></i>
                </div>

                <div style="flex: 1; margin-left: 10px;">
                    <h2 class="header-greeting">
                        <?php
                        $display_name = $_SESSION['first_name'] ?? explode(' ', $_SESSION['user_name'] ?? 'User')[0];
                        echo '<span class="mobile-only">Hello, ' . htmlspecialchars($display_name) . '!</span>';
                        echo '<span class="desktop-only">' . htmlspecialchars($display_name) . '</span>';
                        ?>
                    </h2>
                    <p class="header-date">
                        <span class="mobile-only"><?= date('D, d M') ?> • Good Morning</span>
                        <span class="desktop-only"><?= date('D, d M') ?></span>
                    </p>
                </div>

                <div class="header-actions">
                    <!-- Chat Toggle (Mobile Only) -->
                    <button onclick="toggleMobileChat()" class="mobile-only action-icon-btn">
                        <i data-lucide="message-circle"></i>
                    </button>

                    <?php
                    $unread_stmt = $conn->prepare("
                        SELECT COUNT(*) FROM notices n 
                        WHERE n.id NOT IN (SELECT notice_id FROM notice_reads WHERE employee_id = :uid)
                    ");
                    $unread_stmt->execute(['uid' => $_SESSION['user_id']]);
                    $unread_count = $unread_stmt->fetchColumn();
                    ?>
                    <a href="view_notices.php" class="action-icon-btn notification-bell">
                        <i data-lucide="bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="badge"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            </header>