<?php
// Function to check active link
function isActive($page)
{
    $current_page = basename($_SERVER['PHP_SELF'], ".php");
    return ($current_page == $page) ? 'active' : '';
}

// Function to check if a group should be open or active
function isGroupOpen($pages, $include_view_policy = false)
{
    $current_page = basename($_SERVER['PHP_SELF'], ".php");
    if ($include_view_policy && $current_page == 'view_policy')
        return 'open active';
    return in_array($current_page, $pages) ? 'open active' : '';
}

// Get User Info from Session
$userName = $_SESSION['user_name'] ?? 'Admin User';
$userRole = $_SESSION['user_role'] ?? 'Super Admin';
$userDesignation = $_SESSION['user_designation'] ?? $userRole; // Use designation for display
$userInitials = strtoupper(substr($userName, 0, 2));
?>
<aside class="sidebar">
    <div class="sidebar-inner">
        <div class="sidebar-header">
            <!-- Logo with rounded corners -->
            <img src="assets/logo.png" alt="Myworld Logo"
                style="width: 45px; height: 45px; object-fit: contain; border-radius: 12px;">
            <div class="brand-info">
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


                <!-- Attendance Group -->
                <div class="nav-group">
                    <?php
                    $attendancePages = ['attendance_view', 'regularization_request', 'regularization_status'];
                    $attendanceState = isGroupOpen($attendancePages);
                    ?>
                    <button
                        class="nav-item nav-group-toggle <?= strpos($attendanceState, 'active') !== false ? 'active' : '' ?>"
                        onclick="toggleSubNav('attendanceSubNav', this)">
                        <i data-lucide="calendar-check" class="icon"></i>
                        <span>Attendance</span>
                        <i data-lucide="chevron-right" class="icon chevron-icon"
                            style="transition: transform 0.2s; transform: <?= strpos($attendanceState, 'open') !== false ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                    </button>
                    <div id="attendanceSubNav"
                        class="sub-nav <?= strpos($attendanceState, 'open') !== false ? 'open' : '' ?>">
                        <a href="attendance_view.php" class="sub-nav-item <?php echo isActive('attendance_view'); ?>">
                            <i data-lucide="clock" class="icon" style="width:16px;height:16px;"></i>
                            <span>My Attendance</span>
                        </a>
                        <a href="regularization_request.php"
                            class="sub-nav-item <?php echo isActive('regularization_request'); ?>">
                            <i data-lucide="edit" class="icon" style="width:16px;height:16px;"></i>
                            <span>Request Regularization</span>
                        </a>
                        <a href="regularization_status.php"
                            class="sub-nav-item <?php echo isActive('regularization_status'); ?>">
                            <i data-lucide="file-text" class="icon" style="width:16px;height:16px;"></i>
                            <span>My Requests</span>
                        </a>
                    </div>
                </div>

                <!-- Leave Management Group -->
                <div class="nav-group">
                    <?php
                    $leavePages = ['leave_apply', 'leave_manager_approval'];
                    $leaveState = isGroupOpen($leavePages);
                    ?>
                    <button class="nav-item dropdown-btn <?= $leaveState ?>" onclick="toggleSubNav('leaveSubNav', this)">
                        <i data-lucide="palmtree" class="icon"></i>
                        <span>Leaves</span>
                        <i data-lucide="chevron-right" class="icon chevron-icon"
                            style="transition: transform 0.2s; transform: <?= strpos($leaveState, 'open') !== false ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                    </button>
                    <div id="leaveSubNav" class="sub-nav <?= strpos($leaveState, 'open') !== false ? 'open' : '' ?>">
                        <a href="leave_apply.php" class="sub-nav-item <?php echo isActive('leave_apply'); ?>">
                            <i data-lucide="file-text" class="icon" style="width:16px;height:16px;"></i>
                            <span>Apply Leave</span>
                        </a>
                        <?php
                        // Optional: Only show if manager, but user asked to keep together
                        // Check if user is a manager for anyone
                        $isManager = $conn->prepare("SELECT id FROM employees WHERE reporting_manager_id = :uid LIMIT 1");
                        $isManager->execute(['uid' => $_SESSION['user_id']]);
                        if ($isManager->fetch()):
                            ?>
                            <a href="leave_manager_approval.php"
                                class="sub-nav-item <?php echo isActive('leave_manager_approval'); ?>">
                                <i data-lucide="users" class="icon" style="width:16px;height:16px;"></i>
                                <span>Team Requests</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <a href="view_holidays.php" class="nav-item <?php echo isActive('view_holidays'); ?>">
                    <i data-lucide="calendar-days" class="icon"></i>
                    <span>Holidays</span>
                </a>

                <a href="view_notices.php" class="nav-item <?php echo isActive('view_notices'); ?>">
                    <i data-lucide="bell" class="icon"></i>
                    <span>Notice Board</span>
                </a>

                <!-- Payroll Group -->
                <div class="nav-group">
                    <?php
                    $payrollPages = ['salary_slip'];
                    $payrollState = isGroupOpen($payrollPages);
                    ?>
                    <button class="nav-item dropdown-btn <?= $payrollState ?>"
                        onclick="toggleSubNav('payrollSubNav', this)">
                        <i data-lucide="banknote" class="icon"></i>
                        <span>Payroll</span>
                        <i data-lucide="chevron-right" class="icon chevron-icon"
                            style="transition: transform 0.2s; transform: <?= strpos($payrollState, 'open') !== false ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                    </button>
                    <div id="payrollSubNav" class="sub-nav <?= strpos($payrollState, 'open') !== false ? 'open' : '' ?>">
                        <a href="javascript:void(0)" class="sub-nav-item">
                            <i data-lucide="file-down" class="icon" style="width:16px;height:16px;"></i>
                            <span>Download Salary Slip <span style="font-size: 0.7rem; opacity: 0.7;">(Coming
                                    Soon)</span></span>
                        </a>
                    </div>
                </div>

                <a href="view_policy.php" class="nav-item <?php echo isActive('view_policy'); ?>">
                    <i data-lucide="book-open" class="icon"></i>
                    <span>Policies</span>
                </a>

                <a href="resignation.php" class="nav-item <?php echo isActive('resignation'); ?>">
                    <i data-lucide="log-out" class="icon"></i>
                    <span>Leaving Us</span>
                </a>



            <?php else: ?>
                <!-- ADMIN MENU (Existing) -->
                <a href="index.php" class="nav-item <?php echo isActive('index'); ?>">
                    <i data-lucide="layout-dashboard" class="icon"></i>
                    <span>Dashboard</span>
                </a>

                <!-- Admin Leave Group -->
                <div class="nav-group">
                    <?php
                    $adminLeavePages = ['leave_admin', 'leave'];
                    $adminLeaveState = isGroupOpen($adminLeavePages);
                    ?>
                    <button class="nav-item dropdown-btn <?= $adminLeaveState ?>"
                        onclick="toggleSubNav('adminLeaveSubNav', this)">
                        <i data-lucide="palmtree" class="icon"></i>
                        <span>Leave Management</span>
                        <i data-lucide="chevron-right" class="icon chevron-icon"
                            style="transition: transform 0.2s; transform: <?= strpos($adminLeaveState, 'open') !== false ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                    </button>
                    <div id="adminLeaveSubNav"
                        class="sub-nav <?= strpos($adminLeaveState, 'open') !== false ? 'open' : '' ?>">
                        <a href="leave_admin.php" class="sub-nav-item <?php echo isActive('leave_admin'); ?>">
                            <i data-lucide="shield-check" class="icon" style="width:16px;height:16px;"></i>
                            <span>Pending Approvals</span>
                        </a>
                        <a href="leave.php" class="sub-nav-item <?php echo isActive('leave'); ?>">
                            <i data-lucide="history" class="icon" style="width:16px;height:16px;"></i>
                            <span>Leave History</span>
                        </a>
                    </div>
                </div>

                <a href="admin_resignations.php" class="nav-item <?php echo isActive('admin_resignations'); ?>">
                    <i data-lucide="user-x" class="icon"></i>
                    <span>Resignations</span>
                </a>

                <!-- Employee Onboarding Group -->
                <div class="nav-group">
                    <?php
                    $onboardingPages = ['designation', 'department', 'employees', 'add_employee'];
                    $onboardingState = isGroupOpen($onboardingPages);
                    ?>
                    <button class="nav-item dropdown-btn <?= $onboardingState ?>"
                        onclick="toggleSubNav('employeeSubNav', this)">
                        <i data-lucide="users" class="icon"></i>
                        <span>Onboarding</span>
                        <i data-lucide="chevron-right" class="icon chevron-icon"
                            style="transition: transform 0.2s; transform: <?= strpos($onboardingState, 'open') !== false ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                    </button>
                    <div id="employeeSubNav"
                        class="sub-nav <?= strpos($onboardingState, 'open') !== false ? 'open' : '' ?>">
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


                <!-- Admin Attendance Group -->
                <div class="nav-group">
                    <?php
                    $adminAttendancePages = ['attendance', 'regularization_manage'];
                    $adminAttendanceState = isGroupOpen($adminAttendancePages);
                    ?>
                    <button
                        class="nav-item nav-group-toggle <?= strpos($adminAttendanceState, 'active') !== false ? 'active' : '' ?>"
                        onclick="toggleSubNav('adminAttendanceSubNav', this)">
                        <i data-lucide="calendar-check" class="icon"></i>
                        <span>Attendance</span>
                        <i data-lucide="chevron-right" class="icon chevron-icon"
                            style="transition: transform 0.2s; transform: <?= strpos($adminAttendanceState, 'open') !== false ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                    </button>
                    <div id="adminAttendanceSubNav"
                        class="sub-nav <?= strpos($adminAttendanceState, 'open') !== false ? 'open' : '' ?>">
                        <a href="attendance.php" class="sub-nav-item <?php echo isActive('attendance'); ?>">
                            <i data-lucide="users" class="icon" style="width:16px;height:16px;"></i>
                            <span>View Attendance</span>
                        </a>
                        <a href="regularization_manage.php"
                            class="sub-nav-item <?php echo isActive('regularization_manage'); ?>">
                            <i data-lucide="settings" class="icon" style="width:16px;height:16px;"></i>
                            <span>Regularization</span>
                        </a>
                    </div>
                </div>



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
                    <span class="user-role"><?= htmlspecialchars($userDesignation) ?></span>
                </a>
            </div>
            <a href="logout.php" title="Logout" style="color: #94a3b8; margin-left: auto;">
                <i data-lucide="log-out" style="width: 20px;"></i>
            </a>
        </div>
    </div> <!-- Close sidebar-inner properly -->
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