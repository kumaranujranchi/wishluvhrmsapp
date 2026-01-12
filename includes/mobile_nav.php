<?php
// Mobile Navigation and Drawer for HRMS
$role = $_SESSION['user_role'] ?? 'employee';
$current_page = basename($_SERVER['PHP_SELF']);

function is_active_mobile($page, $current)
{
    return ($page === $current) ? 'active' : '';
}
?>

<!-- Bottom Navigation Bar (Mobile) -->
<nav class="bottom-nav">
    <a href="index.php" class="bottom-nav-item <?= is_active_mobile('index.php', $current_page) ?>">
        <i data-lucide="home"></i>
        <span>Home</span>
    </a>

    <a href="attendance_view.php" class="bottom-nav-item <?= is_active_mobile('attendance_view.php', $current_page) ?>">
        <i data-lucide="calendar-check"></i>
        <span>Punch</span>
    </a>

    <?php if ($role === 'admin'): ?>
        <a href="employees.php" class="bottom-nav-item <?= is_active_mobile('employees.php', $current_page) ?>">
            <i data-lucide="users"></i>
            <span>Staff</span>
        </a>
    <?php else: ?>
        <a href="leave_apply.php" class="bottom-nav-item <?= is_active_mobile('leave_apply.php', $current_page) ?>">
            <i data-lucide="coffee"></i>
            <span>Leaves</span>
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
        <div
            style="width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
            <i data-lucide="user" style="width: 30px; height: 30px;"></i>
        </div>
        <h3 style="margin: 0; font-size: 1.1rem;">
            <?= $_SESSION['user_name'] ?>
        </h3>
        <p style="margin: 0; font-size: 0.8rem; opacity: 0.8;">
            <?= ucfirst($role) ?> Account
        </p>
    </div>

    <div style="padding: 1rem 0;">
        <!-- Common Links -->
        <a href="index.php" class="nav-item <?= is_active_mobile('index.php', $current_page) ?>"
            style="padding: 1rem 1.5rem; display: flex; align-items: center; gap: 12px; color: #1e293b; text-decoration: none;">
            <i data-lucide="layout-dashboard" style="width: 20px;"></i> Dashboard
        </a>

        <?php if ($role === 'admin'): ?>
            <a href="attendance.php" class="nav-item <?= is_active_mobile('attendance.php', $current_page) ?>"
                style="padding: 1rem 1.5rem; display: flex; align-items: center; gap: 12px; color: #1e293b; text-decoration: none;">
                <i data-lucide="calendar" style="width: 20px;"></i> Attendance Log
            </a>
            <a href="leave.php" class="nav-item <?= is_active_mobile('leave.php', $current_page) ?>"
                style="padding: 1rem 1.5rem; display: flex; align-items: center; gap: 12px; color: #1e293b; text-decoration: none;">
                <i data-lucide="coffee" style="width: 20px;"></i> Leave Requests
            </a>
            <a href="holidays.php" class="nav-item <?= is_active_mobile('holidays.php', $current_page) ?>"
                style="padding: 1rem 1.5rem; display: flex; align-items: center; gap: 12px; color: #1e293b; text-decoration: none;">
                <i data-lucide="calendar-days" style="width: 20px;"></i> Holidays
            </a>
            <a href="locations.php" class="nav-item <?= is_active_mobile('locations.php', $current_page) ?>"
                style="padding: 1rem 1.5rem; display: flex; align-items: center; gap: 12px; color: #1e293b; text-decoration: none;">
                <i data-lucide="map-pin" style="width: 20px;"></i> Branch Locations
            </a>
            <a href="policy.php" class="nav-item <?= is_active_mobile('policy.php', $current_page) ?>"
                style="padding: 1rem 1.5rem; display: flex; align-items: center; gap: 12px; color: #1e293b; text-decoration: none;">
                <i data-lucide="book-open" style="width: 20px;"></i> Company Policies
            </a>
        <?php else: ?>
            <a href="view_holidays.php" class="nav-item <?= is_active_mobile('view_holidays.php', $current_page) ?>"
                style="padding: 1rem 1.5rem; display: flex; align-items: center; gap: 12px; color: #1e293b; text-decoration: none;">
                <i data-lucide="calendar-days" style="width: 20px;"></i> Holidays
            </a>
            <a href="view_policy.php" class="nav-item <?= is_active_mobile('view_policy.php', $current_page) ?>"
                style="padding: 1rem 1.5rem; display: flex; align-items: center; gap: 12px; color: #1e293b; text-decoration: none;">
                <i data-lucide="book-open" style="width: 20px;"></i> Policies
            </a>
        <?php endif; ?>

        <div style="border-top: 1px solid #f1f5f9; margin: 1rem 0;"></div>

        <a href="logout.php" class="nav-item"
            style="padding: 1rem 1.5rem; display: flex; align-items: center; gap: 12px; color: #ef4444; text-decoration: none; font-weight: 600;">
            <i data-lucide="log-out" style="width: 20px;"></i> Logout
        </a>
    </div>
</div>

<script>
    function toggleMobileDrawer() {
        const drawer = document.getElementById('mobileDrawer');
        const overlay = document.getElementById('drawerOverlay');
        drawer.classList.toggle('open');
        overlay.classList.toggle('show');
    }
</script>