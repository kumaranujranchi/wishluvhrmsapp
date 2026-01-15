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

        <!-- Header (Unified Responsive) -->
        <header class="header glass-panel">
            <!-- Mobile: Profile Link (Left) -->
            <a href="profile.php" class="mobile-only profile-link-mobile">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['first_name'] ?? 'User') ?>&background=ffd6a8&color=d97706&size=128"
                    alt="Profile">
            </a>

            <!-- Desktop: Hamburger (Hidden on mobile) -->
            <div class="desktop-only hamburger-trigger" onclick="toggleMobileDrawer()">
                <i data-lucide="menu"></i>
            </div>

            <div style="flex: 1; margin-left: 10px;">
                <h2 class="header-greeting">
                    <?php
                    $display_name = $_SESSION['first_name'] ?? explode(' ', $_SESSION['user_name'] ?? 'User')[0];
                    // Mobile Greeting
                    echo '<span class="mobile-only">Hello, ' . htmlspecialchars($display_name) . '!</span>';
                    // Desktop Greeting
                    echo '<span class="desktop-only">' . htmlspecialchars($display_name) . '</span>';
                    ?>
                </h2>
                <p class="header-date">
                    <!-- Mobile Date -->
                    <span class="mobile-only"><?= date('D, d M') ?> • Good Morning</span>
                    <!-- Desktop Date -->
                    <span class="desktop-only"><?= date('D, d M') ?></span>
                </p>
            </div>

            <div class="header-actions">
                <!-- Chat Toggle (Mobile Only) -->
                <button onclick="toggleMobileChat()" class="mobile-only action-icon-btn" style="margin-right: 8px;">
                    <i data-lucide="message-circle"></i>
                </button>

                <?php
                // Fetch unread notice count
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

        <style>
            /* Shared Styles */
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

                .header {
                    padding: 15px 20px !important;
                    background: rgba(255, 255, 255, 0.95);
                    backdrop-filter: blur(10px);
                    position: sticky;
                    top: 0;
                    z-index: 40;
                }

                .action-icon-btn {
                    background: white;
                    border: 1px solid #f1f5f9;
                    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
                    color: #475569;
                }

                /* Flex alignment for mobile actions */
                .header-actions {
                    display: flex;
                    align-items: center;
                }
            }
        </style>

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