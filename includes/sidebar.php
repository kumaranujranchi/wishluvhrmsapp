<?php
// Function to check active link
function isActive($page)
{
    $current_page = basename($_SERVER['PHP_SELF'], ".php");
    return ($current_page == $page) ? 'active' : '';
}

// Function to check if a group should be open
function isGroupOpen($pages)
{
    $current_page = basename($_SERVER['PHP_SELF'], ".php");
    return in_array($current_page, $pages) ? 'open' : '';
}

// Get User Info from Session
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userRole = $_SESSION['user_role'] ?? 'Super Admin';
$userInitials = strtoupper(substr($userName, 0, 2));
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <!-- Logo with rounded corners -->
        <img src="assets/logo.png" alt="Myworld Logo"
            style="width: 45px; height: 45px; object-fit: contain; border-radius: 12px;">
        <div style="display: flex; flex-direction: column; justify-content: center;">
            <h1 class="logo-text" style="line-height: 1;">Myworld</h1>
            <span
                style="font-size: 0.65rem; color: #64748b; font-weight: 500; letter-spacing: 0.5px; margin-top: 2px;">By
                Wishluv Buildcon</span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($userRole === 'Employee'): ?>
            <!-- EMPLOYEE MENU -->
            <a href="employee_dashboard.php" class="nav-item <?php echo isActive('employee_dashboard'); ?>">
                <i data-lucide="layout-dashboard" class="icon"></i>
                <span>Dashboard</span>
            </a>

            <a href="attendance_view.php" class="nav-item <?php echo isActive('attendance_view'); ?>">
                <i data-lucide="calendar-check" class="icon"></i>
                <span>Attendance</span>
            </a>

            <a href="leave_apply.php" class="nav-item <?php echo isActive('leave_apply'); ?>">
                <i data-lucide="coffee" class="icon"></i>
                <span>Leave</span>
            </a>

            <a href="view_holidays.php" class="nav-item <?php echo isActive('view_holidays'); ?>">
                <i data-lucide="calendar-days" class="icon"></i>
                <span>Holidays</span>
            </a>

            <a href="view_notices.php" class="nav-item <?php echo isActive('view_notices'); ?>">
                <i data-lucide="bell" class="icon"></i>
                <span>Notice Board</span>
            </a>

            <!-- Policy Dropdown -->
            <div class="nav-group">
                <?php
                // Fetch active policies from database
                $policy_stmt = $conn->query("SELECT slug, title, icon FROM policies WHERE is_active = 1 ORDER BY display_order ASC");
                $active_policies = $policy_stmt->fetchAll();

                $policyPages = array_column($active_policies, 'slug');
                $isPolicyOpen = isGroupOpen($policyPages);
                ?>
                <button class="nav-item dropdown-btn" onclick="toggleSubNav('policySubNav', this)">
                    <div style="display:flex; align-items:center; gap:0.85rem;">
                        <i data-lucide="book-open" class="icon"></i>
                        <span>Policy</span>
                    </div>
                    <i data-lucide="chevron-right" class="icon chevron-icon"
                        style="transition: transform 0.2s; transform: <?= $isPolicyOpen ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                </button>
                <div id="policySubNav" class="sub-nav <?= $isPolicyOpen ?>">
                    <?php foreach ($active_policies as $policy): ?>
                        <a href="view_policy.php?slug=<?= $policy['slug'] ?>"
                            class="sub-nav-item <?php echo (isset($_GET['slug']) && $_GET['slug'] == $policy['slug']) ? 'active' : ''; ?>">
                            <i data-lucide="<?= htmlspecialchars($policy['icon']) ?>" class="icon"
                                style="width:16px;height:16px;"></i>
                            <span><?= htmlspecialchars($policy['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <a href="resignation.php" class="nav-item <?php echo isActive('resignation'); ?>">
                <i data-lucide="log-out" class="icon"></i>
                <span>Leaving Us</span>
            </a>

            <a href="leave_manager_approval.php" class="nav-item <?php echo isActive('leave_manager_approval'); ?>">
                <i data-lucide="users" class="icon"></i>
                <span>Team Requests</span>
            </a>

        <?php else: ?>
            <!-- ADMIN MENU (Existing) -->
            <a href="index.php" class="nav-item <?php echo isActive('index'); ?>">
                <i data-lucide="layout-dashboard" class="icon"></i>
                <span>Dashboard</span>
            </a>

            <a href="leave_admin.php" class="nav-item <?php echo isActive('leave_admin'); ?>">
                <i data-lucide="shield-check" class="icon"></i>
                <span>Leave Approvals</span>
            </a>

            <!-- Employee Onboarding Group -->
            <div class="nav-group">
                <?php
                $onboardingPages = ['designation', 'department', 'add_employee'];
                $isOpen = isGroupOpen($onboardingPages);
                ?>
                <button class="nav-item dropdown-btn" onclick="toggleSubNav('employeeSubNav', this)">
                    <div style="display:flex; align-items:center; gap:0.85rem;">
                        <i data-lucide="users" class="icon"></i>
                        <span>Onboarding</span>
                    </div>
                    <i data-lucide="chevron-right" class="icon chevron-icon"
                        style="transition: transform 0.2s; transform: <?= $isOpen ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                </button>
                <div id="employeeSubNav" class="sub-nav <?= $isOpen ?>">
                    <a href="designation.php" class="sub-nav-item <?php echo isActive('designation'); ?>">
                        <i data-lucide="briefcase" class="icon" style="width:16px;height:16px;"></i>
                        <span>Designation</span>
                    </a>
                    <a href="department.php" class="sub-nav-item <?php echo isActive('department'); ?>">
                        <i data-lucide="building-2" class="icon" style="width:16px;height:16px;"></i>
                        <span>Department</span>
                    </a>
                    <a href="employees.php"
                        class="sub-nav-item <?php echo isActive('employees') || isActive('add_employee'); ?>">
                        <i data-lucide="users" class="icon" style="width:16px;height:16px;"></i>
                        <span>Employees</span>
                    </a>
                </div>
            </div>

            <a href="attendance.php" class="nav-item <?php echo isActive('attendance'); ?>">
                <i data-lucide="calendar-check" class="icon"></i>
                <span>Attendance</span>
            </a>

            <a href="leave.php" class="nav-item <?php echo isActive('leave'); ?>">
                <i data-lucide="coffee" class="icon"></i>
                <span>Leaves</span>
            </a>

            <a href="holidays.php" class="nav-item <?php echo isActive('holidays'); ?>">
                <i data-lucide="calendar-days" class="icon"></i>
                <span>Holidays</span>
            </a>

            <a href="locations.php" class="nav-item <?php echo isActive('locations'); ?>">
                <i data-lucide="map-pin" class="icon"></i>
                <span>Locations</span>
            </a>

            <a href="admin_notices.php" class="nav-item <?php echo isActive('admin_notices'); ?>">
                <i data-lucide="megaphone" class="icon"></i>
                <span>Manage Notices</span>
            </a>

            <a href="#" class="nav-item">
                <i data-lucide="banknote" class="icon"></i>
                <span>Payroll</span>
            </a>

            <a href="policy.php" class="nav-item <?php echo isActive('policy'); ?>">
                <i data-lucide="book-open" class="icon"></i>
                <span>Policies</span>
            </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= $userInitials ?></div>
            <a href="profile.php" class="user-details" style="text-decoration: none; color: inherit;">
                <span class="user-name" title="View Profile"><?= htmlspecialchars($userName) ?></span>
                <span class="user-role"><?= htmlspecialchars($userRole) ?></span>
            </a>
        </div>
        <a href="logout.php" title="Logout" style="color: #94a3b8; margin-left: auto;">
            <i data-lucide="log-out" style="width: 20px;"></i>
        </a>
    </div>
    <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
        <i data-lucide="chevron-left" style="width: 14px; height: 14px;" id="sidebarToggleIcon"></i>
    </button>
</aside>

<script>
    function toggleSubNav(id, btn) {
        // If sidebar is collapsed, expand it first when clicking a dropdown
        const sidebar = document.querySelector('.sidebar');
        if (sidebar.classList.contains('collapsed')) {
            toggleSidebar(); // Expand
            return; // Let user click again to open sub-nav, or auto-open (optional)
        }

        const subNav = document.getElementById(id);
        const icon = btn.querySelector('.chevron-icon');

        subNav.classList.toggle('open');

        if (subNav.classList.contains('open')) {
            icon.style.transform = "rotate(90deg)";
        } else {
            icon.style.transform = "rotate(0deg)";
        }
    }

    function toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const mainContent = document.querySelector('.main-content');
        const toggleIcon = document.getElementById('sidebarToggleIcon');

        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');

        // Rotate/Change Toggle Icon
        if (sidebar.classList.contains('collapsed')) {
            toggleIcon.style.transform = "rotate(180deg)";
        } else {
            toggleIcon.style.transform = "rotate(0deg)";
        }

        // Optional: Save preference
        // localStorage.setItem('sidebarState', sidebar.classList.contains('collapsed') ? 'collapsed' : 'expanded');
    }
</script>