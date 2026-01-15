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
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid #f1f5f9;
            padding: 12px 16px 30px;
            /* Extra padding for safe area */
            display: flex;
            justify-content: space-between;
            z-index: 1000;
        }

        .m-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            color: #94a3b8;
            text-decoration: none;
            width: 20%;
        }

        .m-nav-item.active {
            color: #7C3AED;
        }

        .m-nav-label {
            font-size: 10px;
            font-weight: 600;
        }

        /* Adjust page content to not be hidden behind nav */
        body {
            padding-bottom: 90px;
        }
    }
</style>

<!-- Mobile Bottom Navigation Bar -->
<div class="m-bottom-nav-container">
    <nav class="m-bottom-nav">
        <a href="employee_dashboard.php"
            class="m-nav-item <?= $current_page == 'employee_dashboard.php' ? 'active' : '' ?>">
            <i data-lucide="layout-grid" style="width: 24px;"></i>
            <span class="m-nav-label">Dashboard</span>
        </a>
        <a href="attendance_view.php" class="m-nav-item <?= $current_page == 'attendance_view.php' ? 'active' : '' ?>">
            <i data-lucide="calendar" style="width: 24px;"></i>
            <span class="m-nav-label">Attendance</span>
        </a>
        <a href="leave_apply.php" class="m-nav-item <?= $current_page == 'leave_apply.php' ? 'active' : '' ?>"
            style="position: relative;">
            <i data-lucide="calendar-days" style="width: 24px;"></i>
            <span class="m-nav-label">Leaves</span>
        </a>
        <a href="view_policy.php" class="m-nav-item <?= $current_page == 'view_policy.php' ? 'active' : '' ?>">
            <i data-lucide="book-open" style="width: 24px;"></i>
            <span class="m-nav-label">Policy</span>
        </a>
        <a href="javascript:void(0)" onclick="toggleMobileDrawer()" id="mobileMenuBtn" class="m-nav-item">
            <i data-lucide="menu" style="width: 24px;"></i>
            <span class="m-nav-label">Menu</span>
        </a>
    </nav>
</div>

<!-- Mobile Drawer Overlay -->
<div class="drawer-overlay" id="drawerOverlay"></div>

<!-- Mobile Drawer -->
<div class="mobile-drawer" id="mobileDrawer"
    style="position: fixed; top: 0; right: -280px; width: 280px; height: 100vh; background: white; z-index: 99999;">
    <div class="drawer-header">
        <div class="brand">
            <div class="logo-icon">
                <span style="color:white; font-weight:800; font-size:1.2rem;">HR</span>
            </div>
            <span style="font-weight:700; color:#1e293b; font-size:1.1rem;">HRMS Portal</span>
        </div>
        <button class="close-btn" onclick="toggleMobileDrawer()">
            <i data-lucide="x"></i>
        </button>
    </div>

    <div class="drawer-content">
        <div class="drawer-section">
            <span class="section-title">Menu</span>
            <a href="employee_dashboard.php"
                class="drawer-item <?= $current_page == 'employee_dashboard.php' ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="attendance_view.php"
                class="drawer-item <?= $current_page == 'attendance_view.php' ? 'active' : '' ?>">
                <i data-lucide="calendar-check"></i> Attendance
            </a>
            <a href="leave_apply.php" class="drawer-item <?= $current_page == 'leave_apply.php' ? 'active' : '' ?>">
                <i data-lucide="calendar"></i> Leaves
            </a>
            <a href="holidays.php" class="drawer-item <?= $current_page == 'holidays.php' ? 'active' : '' ?>">
                <i data-lucide="coffee"></i> Holidays
            </a>
            <a href="payroll.php" class="drawer-item <?= $current_page == 'payroll.php' ? 'active' : '' ?>">
                <i data-lucide="banknote"></i> Payroll
            </a>
            <a href="view_policy.php" class="drawer-item <?= $current_page == 'view_policy.php' ? 'active' : '' ?>">
                <i data-lucide="book-open"></i> Policies
            </a>
        </div>

        <div class="drawer-section">
            <span class="section-title">Account</span>
            <a href="profile.php" class="drawer-item <?= $current_page == 'profile.php' ? 'active' : '' ?>">
                <i data-lucide="user"></i> My Profile
            </a>
            <a href="logout.php" class="drawer-item logout">
                <i data-lucide="log-out"></i> Logout
            </a>
        </div>
    </div>
</div>

<script>
    // Make function globally accessible
    window.toggleMobileDrawer = function () {
        console.log('🔧 toggleMobileDrawer called');

        const drawer = document.getElementById('mobileDrawer');
        const overlay = document.getElementById('drawerOverlay');

        console.log('📦 Drawer element:', drawer);
        console.log('🎭 Overlay element:', overlay);

        if (!drawer) {
            console.error('❌ Drawer element not found!');
            return;
        }

        if (!overlay) {
            console.error('❌ Overlay element not found!');
            return;
        }

        // Toggle classes
        drawer.classList.toggle('active');
        overlay.classList.toggle('active');

        console.log('✅ Drawer active:', drawer.classList.contains('active'));
        console.log('✅ Overlay active:', overlay.classList.contains('active'));
    };

    // Test on load
    console.log('🚀 toggleMobileDrawer function loaded');
</script>

<style>
    /* Drawer Styles */
    .mobile-drawer {
        position: fixed;
        top: 0;
        right: -280px;
        width: 280px;
        height: 100%;
        background: white;
        z-index: 9999;
        transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        box-shadow: -5px 0 25px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    }

    .mobile-drawer.active {
        right: 0 !important;
    }

    .drawer-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9998;
        opacity: 0;
        visibility: hidden;
        transition: all 0.3s;
        backdrop-filter: blur(2px);
    }

    .drawer-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .drawer-header {
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #f1f5f9;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logo-icon {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, #4f46e5, #818cf8);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .close-btn {
        background: none;
        border: none;
        color: #64748b;
        cursor: pointer;
        padding: 5px;
    }

    .drawer-content {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }

    .drawer-section {
        margin-bottom: 25px;
    }

    .section-title {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        color: #94a3b8;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin-bottom: 15px;
    }

    .drawer-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        text-decoration: none;
        color: #475569;
        font-weight: 500;
        font-size: 0.95rem;
        border-radius: 12px;
        margin-bottom: 5px;
        transition: all 0.2s;
    }

    .drawer-item:hover {
        background: #f8fafc;
        color: #1e293b;
    }

    .drawer-item.active {
        background: #e0e7ff;
        color: #4338ca;
        font-weight: 600;
    }

    .drawer-item i {
        width: 20px;
        height: 20px;
    }

    .drawer-item.logout {
        color: #ef4444;
    }

    .drawer-item.logout:hover {
        background: #fef2f2;
    }
</style>