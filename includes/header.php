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
    <link rel="stylesheet" href="assets/css/style.css?v=2.6">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Chatbot CSS -->
    <link rel="stylesheet" href="assets/css/chatbot.css?v=2.0">

    <!-- Desktop Dashboard CSS -->
    <link rel="stylesheet" href="assets/css/desktop-dashboard.css">

    <style>
        /* Header Styles */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 25px;
            position: sticky;
            top: 0;
            z-index: 9999;
            background: white;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            overflow: visible;
        }

        .header-greeting {
            margin: 0;
            font-size: 1.1rem;
            color: #1e293b;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .header-greeting {
                font-size: 0.95rem;
                line-height: 1.3;
            }
        }

        .header-date {
            margin: 0;
            font-size: 0.85rem;
            color: #475569;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            padding: 8px 16px;
            border-radius: 12px;
            border: 1px solid #bae6fd;
            box-shadow: 0 2px 4px rgba(14, 165, 233, 0.1);
        }

        @media (max-width: 768px) {
            .header-date {
                display: none !important;
            }
        }

        .header-date i {
            width: 16px;
            height: 16px;
            color: #0284c7;
        }

        .date-separator {
            width: 4px;
            height: 4px;
            background: #94a3b8;
            border-radius: 50%;
            margin: 0 4px;
        }

        .action-icon-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #1e293b;
            text-decoration: none;
            border: none;
            cursor: pointer;
            position: relative;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .action-icon-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.12);
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        }

        .action-icon-btn i {
            width: 22px;
            height: 22px;
            stroke-width: 2;
        }

        .notification-badge {
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
            display: block !important;
        }

        span.desktop-only,
        a.desktop-only,
        i.desktop-only {
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

            /* Force Hide Sidebar by default */
            .sidebar {
                display: none;
            }

            /* Mobile Sidebar Open State - FORCE EXPANDED LOOK */
            .sidebar.mobile-open {
                display: flex !important;
                flex-direction: column;
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: 280px !important;
                /* Force full width */
                z-index: 10001;
                background: #ffffff !important;
                /* Clean white background */
                box-shadow: 4px 0 25px rgba(0, 0, 0, 0.15);
                animation: slideInLeft 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                border-right: 1px solid #f1f5f9;
                padding-bottom: 20px;
            }

            /* Override Collapsed Styles on Mobile */
            .sidebar.mobile-open .sidebar-inner {
                width: 100% !important;
                padding: 0 !important;
            }

            .sidebar.mobile-open .brand-info,
            .sidebar.mobile-open .nav-item span,
            .sidebar.mobile-open .sub-nav-item span,
            .sidebar.mobile-open .user-details,
            .sidebar.mobile-open .sidebar-footer a[title="Logout"],
            .sidebar.mobile-open .chevron-icon {
                display: block !important;
                /* Force text to show */
                opacity: 1 !important;
                visibility: visible !important;
                width: auto !important;
            }

            .sidebar.mobile-open .sidebar-header {
                padding: 20px 24px !important;
                justify-content: flex-start !important;
                border-bottom: 1px solid #f1f5f9;
                gap: 12px;
            }

            .sidebar.mobile-open .logo-text {
                font-size: 1.25rem !important;
                color: #1e293b !important;
            }

            .sidebar.mobile-open .sidebar-nav {
                padding: 16px !important;
                overflow-y: auto;
            }

            /* Fix nav items spacing in mobile */
            .sidebar.mobile-open .nav-item {
                justify-content: flex-start !important;
                padding: 12px 16px !important;
                margin-bottom: 4px;
                border-radius: 12px;
            }

            .sidebar.mobile-open .nav-item .icon {
                margin-right: 12px !important;
                margin-left: 0 !important;
            }

            .sidebar.mobile-open .sub-nav {
                padding-left: 0 !important;
                background: #f8fafc;
                border-radius: 12px;
                margin-top: 4px;
            }

            .sidebar.mobile-open .sub-nav-item {
                padding-left: 48px !important;
                /* Indent sub items */
                color: #64748b !important;
            }

            /* Fix Mobile Sidebar Active/Hover States */
            .sidebar.mobile-open .nav-item:hover,
            .sidebar.mobile-open .nav-item.active {
                background: #f1f5f9 !important;
                /* Light grey background */
                color: #0f172a !important;
                /* Dark text for contrast */
            }

            .sidebar.mobile-open .nav-item:hover .icon,
            .sidebar.mobile-open .nav-item.active .icon {
                color: #4f46e5 !important;
                /* Brand color for icon */
            }

            .sidebar.mobile-open .nav-item span {
                color: #334155 !important;
                /* Standard dark slate for text */
                font-weight: 500 !important;
            }

            .sidebar.mobile-open .nav-item.active span {
                color: #0f172a !important;
                /* Darker for active */
                font-weight: 600 !important;
            }

            /* Submenu items */
            .sidebar.mobile-open .sub-nav-item:hover,
            .sidebar.mobile-open .sub-nav-item.active {
                color: #4f46e5 !important;
                background: transparent !important;
            }

            .sidebar.mobile-open .sub-nav-item span {
                color: #64748b !important;
            }

            .sidebar.mobile-open .sub-nav-item.active span,
            .sidebar.mobile-open .sub-nav-item:hover span {
                color: #4f46e5 !important;
            }

            /* Improve User Info in Mobile Sidebar */
            .sidebar.mobile-open .sidebar-footer {
                padding: 16px !important;
                border-top: 1px solid #f1f5f9;
                margin-top: auto;
                /* Push to bottom */
            }

            .sidebar.mobile-open .user-info {
                padding: 12px 16px !important;
                background: #f8fafc;
                margin: 0 !important;
                border-radius: 12px;
                display: flex !important;
                align-items: center;
                gap: 12px;
                flex: 1;
                /* Allow flexible width to share space */
                min-width: 0;
                /* Enable text truncation/wrapping inside flex child */
            }

            .sidebar.mobile-open .user-details {
                display: flex !important;
                flex-direction: column;
                justify-content: center;
                overflow: visible !important;
                /* Allow full name show */
            }

            .sidebar.mobile-open .user-name {
                display: block !important;
                font-size: 0.95rem !important;
                font-weight: 600 !important;
                color: #1e293b !important;
                white-space: normal !important;
                /* Allow text wrapping if needed */
                overflow: visible !important;
                line-height: 1.3;
            }

            .sidebar.mobile-open .user-role {
                display: block !important;
                font-size: 0.75rem !important;
                color: #64748b !important;
            }

            .sidebar.mobile-open .user-avatar {
                width: 40px !important;
                height: 40px !important;
                min-width: 40px !important;
                border-radius: 10px !important;
            }

            /* Ensure logout icon is visible and spaced */
            .sidebar.mobile-open .sidebar-footer a[title="Logout"] {
                display: flex !important;
                align-items: center;
                justify-content: center;
                width: 36px;
                height: 36px;
                border-radius: 8px;
                background: #fff0f0;
                color: #ef4444;
                margin-left: 8px !important;
            }

            /* Hide the desktop toggle button on mobile sidebar */
            .sidebar.mobile-open .sidebar-toggle-btn {
                display: none !important;
            }

            /* Main Content Reset */
            .main-content {
                margin-left: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                display: block !important;
            }

            /* Header Adjustments */
            .header {
                padding: 12px 15px !important;
                background: white !important;
                position: sticky;
                top: 0;
                z-index: 1001;
                box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
                display: flex !important;
                align-items: center !important;
                gap: 10px !important;
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

            /* Profile specific corrections */
            .sidebar.mobile-open .user-info {
                padding: 16px !important;
                background: #f8fafc;
                margin: 0 16px;
                border-radius: 16px;
                display: flex !important;
                gap: 12px;
            }

            /* Sidebar Overlay */
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(15, 23, 42, 0.6);
                /* Darker blur overlay */
                backdrop-filter: blur(4px);
                z-index: 10000;
                animation: fadeIn 0.3s ease-out;
            }

            .sidebar-overlay.active {
                display: block;
            }

            @keyframes slideInLeft {
                from {
                    transform: translateX(-100%);
                }

                to {
                    transform: translateX(0);
                }
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                }

                to {
                    opacity: 1;
                }
            }
        }
    </style>

    <script>
        function toggleMobileChat() {
            const cw = document.getElementById('chatWindow');
            if (cw) cw.classList.toggle('active');
        }

        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }
    </script>

    <!-- Mobile Sidebar Overlay -->
    <div class="sidebar-overlay" onclick="toggleMobileSidebar()"></div>
</head>

<body>
    <div class="app-container">
        <!-- Sidebar Included Here -->
        <?php include 'includes/sidebar.php'; ?>

        <div class="main-content">
            <!-- Header (Unified Responsive) -->
            <header class="header glass-panel">
                <!-- Mobile: Profile Link (Left) -->
                <!-- Mobile: Hamburger Menu (Left) -->
                <button class="mobile-only action-icon-btn" onclick="toggleMobileSidebar()"
                    style="border:none; background:transparent; padding:0; margin-right:5px;">
                    <i data-lucide="menu" style="width: 28px; height: 28px; color: #1e293b;"></i>
                </button>

                <!-- Desktop: Hamburger (Hidden on mobile) -->

                <div style="flex: 1; margin-left: 10px;">
                    <h2 class="header-greeting">
                        <?php
                        $display_name = $_SESSION['first_name'] ?? explode(' ', $_SESSION['user_name'] ?? 'User')[0];
                        echo '<span class="mobile-only">Hello, ' . htmlspecialchars($display_name) . '!</span>';
                        // Desktop name removed to avoid duplication
                        ?>
                    </h2>
                    <div class="header-date">
                        <i data-lucide="calendar-days" class="desktop-only"></i>
                        <span class="desktop-only"><?= date('l, d F Y') ?></span>
                        <span class="date-separator desktop-only"></span>
                        <i data-lucide="clock" class="desktop-only"></i>
                        <span class="desktop-only" id="live-time"></span>
                    </div>
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
                    <a href="view_notices.php" class="action-icon-btn notification-bell" title="Notifications">
                        <i data-lucide="bell"></i>
                        <?php if ($unread_count > 0): ?>
                            <span class="notification-badge"><?= $unread_count ?></span>
                        <?php endif; ?>
                    </a>

                    <!-- Profile/Logout Section -->
                    <div class="desktop-only" style="margin-left: 8px;">
                        <a href="logout.php" class="action-icon-btn" title="Logout"
                            style="color: #ef4444; background: #fee2e2;">
                            <i data-lucide="log-out"></i>
                        </a>
                    </div>
                </div>
            </header>

            <script>
                // Ensure icons are created even if script loads late or page crashes partially
                (function () {
                    function initLucide() {
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }
                    if (document.readyState === "loading") {
                        document.addEventListener("DOMContentLoaded", initLucide);
                    } else {
                        initLucide();
                    }
                    // Backup call
                    setTimeout(initLucide, 500);
                    setTimeout(initLucide, 2000); // Second backup
                })();
            </script>