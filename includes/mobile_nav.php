<?php
// Determine current page for active state
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Global Mobile Bottom Nav Styles -->
<style>
    /* Default hidden on desktop */
    .m-bottom-nav-container {
        display: none;
    }

    @media (max-width: 768px) {
        .m-bottom-nav-container {
            display: block;
        }

        .m-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-top: 1px solid #f1f5f9;
            padding: 10px 5px 25px 5px;
            /* Bottom padding for safe area */
            display: flex;
            overflow-x: auto;
            /* Enable Horizontal Scroll */
            -webkit-overflow-scrolling: touch;
            /* Smooth scrolling on iOS */
            gap: 15px;
            /* Spacing between items */
            z-index: 1000;
            scrollbar-width: none;
            /* Hide scrollbar Firefox */
        }

        /* Hide Scrollbar Chrome/Safari/Edge */
        .m-bottom-nav::-webkit-scrollbar {
            display: none;
        }

        .m-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            min-width: 65px;
            /* Ensure touch target size */
            color: #64748b;
            flex-shrink: 0;
            /* Prevent shrinking */
        }

        .m-nav-item.active {
            color: #7C3AED;
        }

        .m-nav-item.active .m-nav-label {
            font-weight: 700;
        }

        .m-nav-item.logout {
            color: #ef4444;
        }

        .m-nav-label {
            font-size: 10px;
            font-weight: 500;
            margin-top: 4px;
            white-space: nowrap;
        }

        .m-nav-icon {
            width: 24px;
            height: 24px;
        }

        /* Adjust page content to not be hidden behind nav */
        body {
            padding-bottom: 90px;
        }
    }
</style>

<!-- Mobile Bottom Navigation Bar (Scrollable) -->
<div class="m-bottom-nav-container">
    <nav class="m-bottom-nav">
        <?php
        // Determine user role
        // Fix: Use 'user_role' instead of 'role' as set in login.php
        $user_role = $_SESSION['user_role'] ?? '';
        $is_admin = strcasecmp(trim($user_role), 'Admin') === 0;

        $home_page = $is_admin ? 'index.php' : 'employee_dashboard.php';
        $attendance_page = $is_admin ? 'attendance.php' : 'attendance_view.php';
        ?>

        <!-- 1. Dashboard -->
        <a href="<?= $home_page ?>" class="m-nav-item <?= $current_page == $home_page ? 'active' : '' ?>">
            <i data-lucide="layout-grid" class="m-nav-icon"></i>
            <span class="m-nav-label">Home</span>
        </a>

        <!-- 2. Attendance -->
        <a href="<?= $attendance_page ?>" class="m-nav-item <?= $current_page == $attendance_page ? 'active' : '' ?>">
            <i data-lucide="calendar-check" class="m-nav-icon"></i>
            <span class="m-nav-label">Attend.</span>
        </a>

        <!-- 3. Leaves -->
        <a href="<?= $is_admin ? 'leave.php' : 'leave_apply.php' ?>"
            class="m-nav-item <?= ($current_page == 'leave.php' || $current_page == 'leave_apply.php') ? 'active' : '' ?>">
            <i data-lucide="calendar-days" class="m-nav-icon"></i>
            <span class="m-nav-label">Leaves</span>
        </a>

        <!-- 4. Holidays -->
        <a href="<?= $is_admin ? 'holidays.php' : 'view_holidays.php' ?>"
            class="m-nav-item <?= ($current_page == 'holidays.php' || $current_page == 'view_holidays.php') ? 'active' : '' ?>">
            <i data-lucide="coffee" class="m-nav-icon"></i>
            <span class="m-nav-label">Holiday</span>
        </a>

        <!-- Payroll Removed as requested -->

        <!-- 6. Policy -->
        <a href="<?= $is_admin ? 'policy.php' : 'view_policy.php' ?>"
            class="m-nav-item <?= ($current_page == 'policy.php' || $current_page == 'view_policy.php') ? 'active' : '' ?>">
            <i data-lucide="book-open" class="m-nav-icon"></i>
            <span class="m-nav-label">Policy</span>
        </a>

        <!-- 7. Profile -->
        <a href="profile.php" class="m-nav-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
            <i data-lucide="user" class="m-nav-icon"></i>
            <span class="m-nav-label">Profile</span>
        </a>

        <!-- 8. Logout -->
        <a href="logout.php" class="m-nav-item logout">
            <i data-lucide="log-out" class="m-nav-icon"></i>
            <span class="m-nav-label">Logout</span>
        </a>
    </nav>
</div>

<!-- Clear old JS/Drawer artifacts if any remain in DOM by accident -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const oldDrawer = document.getElementById('mobileDrawer');
        const oldOverlay = document.getElementById('drawerOverlay');
        if (oldDrawer) oldDrawer.remove();
        if (oldOverlay) oldOverlay.remove();
    });
</script>