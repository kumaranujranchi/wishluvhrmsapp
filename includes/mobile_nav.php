<?php
// Mobile Navigation and Drawer for HRMS
$userRole = $_SESSION['user_role'] ?? 'Employee';
$current_page = basename($_SERVER['PHP_SELF']);

function is_active_mobile($page, $current)
{
    // Handle pages with query strings or variants
    return (strpos($current, $page) !== false) ? 'active' : '';
}
?>

<!-- Bottom Navigation Bar (Mobile) -->
<nav class="bottom-nav">
    <?php if ($userRole === 'Employee'): ?>
        <a href="employee_dashboard.php"
            class="bottom-nav-item <?= is_active_mobile('employee_dashboard.php', $current_page) ?>">
            <i data-lucide="home"></i>
            <span>Home</span>
        </a>
        <a href="attendance_view.php" class="bottom-nav-item <?= is_active_mobile('attendance_view.php', $current_page) ?>">
            <i data-lucide="calendar-check"></i>
            <span>Punch</span>
        </a>
        <a href="leave_apply.php" class="bottom-nav-item <?= is_active_mobile('leave_apply.php', $current_page) ?>">
            <i data-lucide="coffee"></i>
            <span>Leaves</span>
        </a>
    <?php else: ?>
        <a href="index.php" class="bottom-nav-item <?= is_active_mobile('index.php', $current_page) ?>">
            <i data-lucide="layout-dashboard"></i>
            <span>Dashboard</span>
        </a>
        <a href="attendance.php" class="bottom-nav-item <?= is_active_mobile('attendance.php', $current_page) ?>">
            <i data-lucide="calendar"></i>
            <span>Logs</span>
        </a>
        <a href="employees.php" class="bottom-nav-item <?= is_active_mobile('employees.php', $current_page) ?>">
            <i data-lucide="users"></i>
            <span>Staff</span>
        </a>
    <?php endif; ?>

    <a href="javascript:void(0)" class="bottom-nav-item" onclick="toggleMobileDrawer()">
        <i data-lucide="menu"></i>
        <span>More</span>
    </a>
</nav>

<!-- Mobile Drawer Overlay -->
<div class="drawer-overlay" id="drawerOverlay" onclick="toggleMobileDrawer()"></div>

<!-- Mobile Drawer (Hamburger Menu) -->
<div class="mobile-drawer" id="mobileDrawer">
    <div style="padding: 2rem 1.5rem; background: linear-gradient(135deg, #6366f1, #a855f7); color: white;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <div
                style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i data-lucide="user" style="width: 30px; height: 30px;"></i>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 1.1rem;"><?= htmlspecialchars($_SESSION['user_name'] ?? 'User') ?></h3>
                <p style="margin: 0; font-size: 0.8rem; opacity: 0.8;"><?= htmlspecialchars($userRole) ?> Account</p>
            </div>
        </div>
    </div>

    <div style="padding: 1rem 0; height: calc(100% - 130px); overflow-y: auto;">
        <?php if ($userRole === 'Employee'): ?>
            <!-- EMPLOYEE DRAWER LINKS -->
            <a href="employee_dashboard.php"
                class="nav-item-mobile <?= is_active_mobile('employee_dashboard.php', $current_page) ?>">
                <i data-lucide="layout-dashboard"></i> Dashboard
            </a>
            <a href="attendance_view.php"
                class="nav-item-mobile <?= is_active_mobile('attendance_view.php', $current_page) ?>">
                <i data-lucide="calendar-check"></i> Attendance
            </a>
            <a href="leave_apply.php" class="nav-item-mobile <?= is_active_mobile('leave_apply.php', $current_page) ?>">
                <i data-lucide="coffee"></i> Leave Request
            </a>
            <a href="view_holidays.php" class="nav-item-mobile <?= is_active_mobile('view_holidays.php', $current_page) ?>">
                <i data-lucide="calendar-days"></i> Holidays
            </a>
            <a href="view_notices.php" class="nav-item-mobile <?= is_active_mobile('view_notices.php', $current_page) ?>">
                <i data-lucide="bell"></i> Notice Board
            </a>
            <a href="view_policy.php" class="nav-item-mobile <?= is_active_mobile('view_policy.php', $current_page) ?>">
                <i data-lucide="book-open"></i> Company Policies
            </a>
            <a href="resignation.php" class="nav-item-mobile <?= is_active_mobile('resignation.php', $current_page) ?>">
                <i data-lucide="log-out"></i> Leaving Us
            </a>
            <a href="leave_manager_approval.php"
                class="nav-item-mobile <?= is_active_mobile('leave_manager_approval.php', $current_page) ?>">
                <i data-lucide="users"></i> Team Requests
            </a>
        <?php else: ?>
            <!-- ADMIN DRAWER LINKS -->
            <a href="index.php" class="nav-item-mobile <?= is_active_mobile('index.php', $current_page) ?>">
                <i data-lucide="layout-dashboard"></i> Home Dashboard
            </a>
            <a href="leave_admin.php" class="nav-item-mobile <?= is_active_mobile('leave_admin.php', $current_page) ?>">
                <i data-lucide="shield-check"></i> Leave Approvals
            </a>

            <div
                style="padding: 10px 1.5rem; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">
                Onboarding</div>
            <a href="designation.php" class="nav-item-mobile <?= is_active_mobile('designation.php', $current_page) ?>">
                <i data-lucide="briefcase"></i> Designations
            </a>
            <a href="department.php" class="nav-item-mobile <?= is_active_mobile('department.php', $current_page) ?>">
                <i data-lucide="building-2"></i> Departments
            </a>
            <a href="employees.php" class="nav-item-mobile <?= is_active_mobile('employees.php', $current_page) ?>">
                <i data-lucide="users"></i> Employee List
            </a>

            <div
                style="padding: 10px 1.5rem; font-size: 0.75rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px;">
                Management</div>
            <a href="attendance.php" class="nav-item-mobile <?= is_active_mobile('attendance.php', $current_page) ?>">
                <i data-lucide="calendar-check"></i> Attendance Logs
            </a>
            <a href="leave.php" class="nav-item-mobile <?= is_active_mobile('leave.php', $current_page) ?>">
                <i data-lucide="coffee"></i> All Leaves
            </a>
            <a href="holidays.php" class="nav-item-mobile <?= is_active_mobile('holidays.php', $current_page) ?>">
                <i data-lucide="calendar-days"></i> Manage Holidays
            </a>
            <a href="locations.php" class="nav-item-mobile <?= is_active_mobile('locations.php', $current_page) ?>">
                <i data-lucide="map-pin"></i> Branch Locations
            </a>
            <a href="admin_notices.php" class="nav-item-mobile <?= is_active_mobile('admin_notices.php', $current_page) ?>">
                <i data-lucide="megaphone"></i> Manage Notices
            </a>
            <a href="policy.php" class="nav-item-mobile <?= is_active_mobile('policy.php', $current_page) ?>">
                <i data-lucide="book-open"></i> Policy Settings
            </a>
        <?php endif; ?>

        <div style="border-top: 1px solid #f1f5f9; margin: 1rem 0;"></div>

        <a href="profile.php" class="nav-item-mobile <?= is_active_mobile('profile.php', $current_page) ?>">
            <i data-lucide="user-circle"></i> My Profile
        </a>
        <a href="logout.php" class="nav-item-mobile" style="color: #ef4444; font-weight: 600;">
            <i data-lucide="log-out"></i> Logout
        </a>
    </div>
</div>

<style>
    .nav-item-mobile {
        padding: 0.85rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #1e293b;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .nav-item-mobile i {
        width: 20px;
        height: 20px;
        color: #64748b;
    }

    .nav-item-mobile.active {
        background: #f8fafc;
        color: #6366f1;
        border-left: 4px solid #6366f1;
    }

    .nav-item-mobile.active i {
        color: #6366f1;
    }

    .mobile-drawer {
        display: flex;
        flex-direction: column;
    }
</style>

<script>
    function toggleMobileDrawer() {
        const drawer = document.getElementById('mobileDrawer');
        const overlay = document.getElementById('drawerOverlay');
        drawer.classList.toggle('open');
        overlay.classList.toggle('show');
    }
</script>