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


<!-- Mobile Drawer Overlay -->
<div class="drawer-overlay" id="drawerOverlay" onclick="toggleMobileDrawer()"></div>

<!-- Mobile Drawer (Hamburger Menu) -->
<div class="mobile-drawer" id="mobileDrawer">
    <div
        style="padding: 2rem 1.5rem; background: linear-gradient(135deg, #6366f1, #a855f7); color: white; border-radius: 0 !important;">
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

    <div class="drawer-body" style="padding: 1rem 0; flex: 1; overflow-y: auto;">
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

            <!-- Payroll Dropdown for Mobile -->
            <div class="nav-group-mobile">
                <div class="nav-item-mobile dropdown-toggle-mobile" onclick="toggleMobileSubNav('payrollSubMobile', this)">
                    <i data-lucide="banknote"></i>
                    <span>Payroll</span>
                    <i data-lucide="chevron-down" class="chevron-icon-mobile"
                        style="margin-left: auto; width: 16px; transition: transform 0.2s;"></i>
                </div>
                <div id="payrollSubMobile" class="sub-nav-mobile">
                    <a href="javascript:void(0)" class="sub-nav-item-mobile">
                        <i data-lucide="file-down" style="width: 16px; height: 16px;"></i>
                        <span>Download Salary Slip <small style="opacity: 0.6;">(Soon)</small></span>
                    </a>
                </div>
            </div>

            <!-- Dynamic Policy Dropdown for Mobile -->
            <div class="nav-group-mobile">
                <?php
                // Fetch active policies for mobile
                $policy_stmt = $conn->query("SELECT slug, title, icon FROM policies WHERE is_active = 1 ORDER BY display_order ASC");
                $active_policies = $policy_stmt->fetchAll();

                $is_policy_page = (strpos($current_page, 'view_policy.php') !== false);
                ?>
                <div class="nav-item-mobile dropdown-toggle-mobile <?= $is_policy_page ? 'active' : '' ?>"
                    onclick="toggleMobileSubNav('policySubMobile', this)">
                    <i data-lucide="book-open"></i>
                    <span>Company Policies</span>
                    <i data-lucide="chevron-down" class="chevron-icon-mobile"
                        style="margin-left: auto; width: 16px; transition: transform 0.2s; transform: <?= $is_policy_page ? 'rotate(180deg)' : 'rotate(0deg)' ?>"></i>
                </div>
                <div id="policySubMobile" class="sub-nav-mobile <?= $is_policy_page ? 'show' : '' ?>">
                    <?php foreach ($active_policies as $policy): ?>
                        <a href="view_policy.php?slug=<?= $policy['slug'] ?>"
                            class="sub-nav-item-mobile <?= (isset($_GET['slug']) && $_GET['slug'] == $policy['slug']) ? 'active' : '' ?>">
                            <i data-lucide="<?= htmlspecialchars($policy['icon']) ?>" style="width: 16px; height: 16px;"></i>
                            <span><?= htmlspecialchars($policy['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

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

            <a href="admin_resignations.php"
                class="nav-item-mobile <?= is_active_mobile('admin_resignations.php', $current_page) ?>">
                <i data-lucide="user-x"></i> Resignations
            </a>

            <!-- Admin Onboarding Dropdown -->
            <div class="nav-group-mobile">
                <?php
                $onboardingPages = ['designation.php', 'department.php', 'employees.php'];
                $is_onboarding_active = false;
                foreach ($onboardingPages as $p) {
                    if (strpos($current_page, $p) !== false) {
                        $is_onboarding_active = true;
                        break;
                    }
                }
                ?>
                <div class="nav-item-mobile dropdown-toggle-mobile <?= $is_onboarding_active ? 'active' : '' ?>"
                    onclick="toggleMobileSubNav('onboardingSubMobile', this)">
                    <i data-lucide="users"></i>
                    <span>Onboarding</span>
                    <i data-lucide="chevron-down" class="chevron-icon-mobile"
                        style="margin-left: auto; width: 16px; transition: transform 0.2s; transform: <?= $is_onboarding_active ? 'rotate(180deg)' : 'rotate(0deg)' ?>"></i>
                </div>
                <div id="onboardingSubMobile" class="sub-nav-mobile <?= $is_onboarding_active ? 'show' : '' ?>">
                    <a href="designation.php"
                        class="sub-nav-item-mobile <?= is_active_mobile('designation.php', $current_page) ?>">
                        <i data-lucide="briefcase" style="width:16px;"></i> Designations
                    </a>
                    <a href="department.php"
                        class="sub-nav-item-mobile <?= is_active_mobile('department.php', $current_page) ?>">
                        <i data-lucide="building-2" style="width:16px;"></i> Departments
                    </a>
                    <a href="employees.php"
                        class="sub-nav-item-mobile <?= is_active_mobile('employees.php', $current_page) ?>">
                        <i data-lucide="users" style="width:16px;"></i> Employee List
                    </a>
                </div>
            </div>

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
        cursor: pointer;
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

    /* Mobile Sub-nav styles */
    .sub-nav-mobile {
        display: none;
        background: #fdfdfd;
        padding-left: 1rem;
    }

    .sub-nav-mobile.show {
        display: block;
    }

    .sub-nav-item-mobile {
        padding: 0.75rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #475569;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 500;
        transition: all 0.2s;
        border-left: 1px solid #e2e8f0;
        margin-left: 1.5rem;
    }

    .sub-nav-item-mobile.active {
        color: #6366f1;
        border-left-color: #6366f1;
        background: #f8fafc;
    }

    .sub-nav-item-mobile i {
        width: 16px;
        height: 16px;
        opacity: 0.7;
    }
</style>

<script>
    function toggleMobileDrawer() {
        const drawer = document.getElementById('mobileDrawer');
        const overlay = document.getElementById('drawerOverlay');
        drawer.classList.toggle('open');
        overlay.classList.toggle('show');
    }

    function toggleMobileSubNav(id, btn) {
        const subNav = document.getElementById(id);
        const icon = btn.querySelector('.chevron-icon-mobile');

        const isOpening = !subNav.classList.contains('show');

        subNav.classList.toggle('show');

        if (isOpening) {
            icon.style.transform = "rotate(180deg)";
        } else {
            icon.style.transform = "rotate(0deg)";
        }
    }
</script>