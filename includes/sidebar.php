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
<?php if (!defined('IS_KIOSK') && (!isset($_SESSION['user_email']) || $_SESSION['user_email'] !== 'kiosk@wishluvbuildcon.com')): ?>
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
                            <a href="salary_slips.php" class="sub-nav-item <?php echo isActive('salary_slips'); ?>">
                                <i data-lucide="file-down" class="icon" style="width:16px;height:16px;"></i>
                                <span>Download Salary Slip</span>
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
                    <!-- ADMIN MENU -->
                    <a href="index.php" class="nav-item <?php echo isActive('index'); ?>">
                        <i data-lucide="layout-dashboard" class="icon"></i>
                        <span>Dashboard</span>
                    </a>

                    <!-- Attendance & Leave Group -->
                    <div class="nav-group">
                        <?php
                        // pages for the parent group to be open
                        $attendanceLeavePages = ['attendance', 'regularization_manage', 'leave_admin', 'leave'];
                        $attendanceLeaveState = isGroupOpen($attendanceLeavePages);
                        ?>
                        <button class="nav-item dropdown-btn <?= $attendanceLeaveState ?>"
                            onclick="toggleSubNav('attendanceLeaveSubNav', this)">
                            <i data-lucide="calendar-check" class="icon"></i>
                            <span>Attendance & Leave</span>
                            <i data-lucide="chevron-right" class="icon chevron-icon"
                                style="transition: transform 0.2s; transform: <?= strpos($attendanceLeaveState, 'open') !== false ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                        </button>
                        <div id="attendanceLeaveSubNav"
                            class="sub-nav <?= strpos($attendanceLeaveState, 'open') !== false ? 'open' : '' ?>">

                            <!-- Attendance Link -->
                            <a href="attendance.php" class="sub-nav-item <?php echo isActive('attendance'); ?>">
                                <i data-lucide="users" class="icon" style="width:16px;height:16px;"></i>
                                <span>Attendance</span>
                            </a>

                            <!-- Regularization (Kept as it belongs to attendance) -->
                            <a href="regularization_manage.php"
                                class="sub-nav-item <?php echo isActive('regularization_manage'); ?>">
                                <i data-lucide="settings" class="icon" style="width:16px;height:16px;"></i>
                                <span>Regularization</span>
                            </a>

                            <!-- Nested Leave Management Group -->
                            <div class="nav-group" style="margin-left: 10px; border-left: 1px solid #e2e8f0;">
                                <?php
                                $leaveMgmtPages = ['leave_admin', 'leave'];
                                // Simple logic: if any leave page is active, this nested group is open
                                $leaveMgmtState = isGroupOpen($leaveMgmtPages);
                                // Check if we need to force it open if it was already open via JS, but PHP logic is stateless.
                                // However, isGroupOpen follows current page.
                                ?>
                                <button class="nav-item dropdown-btn <?= $leaveMgmtState ?>"
                                    onclick="toggleSubNav('leaveMgmtSubNav', this)"
                                    style="padding-left: 10px; font-size: 0.9em;">
                                    <span>Leave Management</span>
                                    <i data-lucide="chevron-right" class="icon chevron-icon"
                                        style="transition: transform 0.2s; transform: <?= strpos($leaveMgmtState, 'open') !== false ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                                </button>
                                <div id="leaveMgmtSubNav"
                                    class="sub-nav <?= strpos($leaveMgmtState, 'open') !== false ? 'open' : '' ?>">
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

                        </div>
                    </div>

                    <a href="admin_reports.php"
                        class="nav-item <?php echo isActive('admin_reports') || isActive('attendance_report'); ?>">
                        <i data-lucide="file-bar-chart" class="icon"></i>
                        <span>Reports Center</span>
                    </a>

                    <!-- Employee Management Group -->
                    <div class="nav-group">
                        <?php
                        $empMgmtPages = ['employees', 'add_employee', 'admin_resignations', 'admin_enroll_face', 'view_employee', 'edit_employee'];
                        $empMgmtState = isGroupOpen($empMgmtPages);
                        ?>
                        <button class="nav-item dropdown-btn <?= $empMgmtState ?>"
                            onclick="toggleSubNav('empMgmtSubNav', this)">
                            <i data-lucide="users" class="icon"></i>
                            <span>Employee Management</span>
                            <i data-lucide="chevron-right" class="icon chevron-icon"
                                style="transition: transform 0.2s; transform: <?= strpos($empMgmtState, 'open') !== false ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                        </button>
                        <div id="empMgmtSubNav" class="sub-nav <?= strpos($empMgmtState, 'open') !== false ? 'open' : '' ?>">
                            <a href="employees.php"
                                class="sub-nav-item <?php echo isActive('employees') || isActive('view_employee') || isActive('edit_employee'); ?>">
                                <i data-lucide="users" class="icon" style="width:16px;height:16px;"></i>
                                <span>Employees</span>
                            </a>
                            <a href="add_employee.php" class="sub-nav-item <?php echo isActive('add_employee'); ?>">
                                <i data-lucide="user-plus" class="icon" style="width:16px;height:16px;"></i>
                                <span>Onboarding</span>
                            </a>
                            <a href="admin_resignations.php" class="sub-nav-item <?php echo isActive('admin_resignations'); ?>">
                                <i data-lucide="user-x" class="icon" style="width:16px;height:16px;"></i>
                                <span>Resignations</span>
                            </a>
                            <a href="admin_enroll_face.php" class="sub-nav-item <?php echo isActive('admin_enroll_face'); ?>">
                                <i data-lucide="scan-face" class="icon" style="width:16px;height:16px;"></i>
                                <span>Face Enrollment</span>
                            </a>
                        </div>
                    </div>

                    <!-- Organization Setup Group -->
                    <div class="nav-group">
                        <?php
                        $orgSetupPages = ['locations', 'department', 'designation', 'policy', 'holidays'];
                        $orgSetupState = isGroupOpen($orgSetupPages);
                        ?>
                        <button class="nav-item dropdown-btn <?= $orgSetupState ?>"
                            onclick="toggleSubNav('orgSetupSubNav', this)">
                            <i data-lucide="building" class="icon"></i>
                            <span>Organization Setup</span>
                            <i data-lucide="chevron-right" class="icon chevron-icon"
                                style="transition: transform 0.2s; transform: <?= strpos($orgSetupState, 'open') !== false ? 'rotate(90deg)' : 'rotate(0deg)' ?>"></i>
                        </button>
                        <div id="orgSetupSubNav" class="sub-nav <?= strpos($orgSetupState, 'open') !== false ? 'open' : '' ?>">
                            <a href="locations.php" class="sub-nav-item <?php echo isActive('locations'); ?>">
                                <i data-lucide="map-pin" class="icon" style="width:16px;height:16px;"></i>
                                <span>Locations</span>
                            </a>
                            <a href="department.php" class="sub-nav-item <?php echo isActive('department'); ?>">
                                <i data-lucide="building-2" class="icon" style="width:16px;height:16px;"></i>
                                <span>Department</span>
                            </a>
                            <a href="designation.php" class="sub-nav-item <?php echo isActive('designation'); ?>">
                                <i data-lucide="briefcase" class="icon" style="width:16px;height:16px;"></i>
                                <span>Designation</span>
                            </a>
                            <a href="policy.php" class="sub-nav-item <?php echo isActive('policy'); ?>">
                                <i data-lucide="book-open" class="icon" style="width:16px;height:16px;"></i>
                                <span>Policies</span>
                            </a>
                            <a href="holidays.php" class="sub-nav-item <?php echo isActive('holidays'); ?>">
                                <i data-lucide="calendar-days" class="icon" style="width:16px;height:16px;"></i>
                                <span>Holidays</span>
                            </a>
                        </div>
                    </div>

                    <a href="admin_payroll.php" class="nav-item <?php echo isActive('admin_payroll'); ?>">
                        <i data-lucide="banknote" class="icon"></i>
                        <span>Payroll</span>
                    </a>

                    <a href="admin_notices.php" class="nav-item <?php echo isActive('admin_notices'); ?>">
                        <i data-lucide="megaphone" class="icon"></i>
                        <span>Manage Notices</span>
                    </a>

                    <a href="admin_app_update.php" class="nav-item <?php echo isActive('admin_app_update'); ?>">
                        <i data-lucide="smartphone" class="icon"></i>
                        <span>App Update Center</span>
                    </a>

                <?php endif; ?>
            </nav>

            <?php
            // Fetch latest APK URL for the download link
            $apk_link_stmt = $conn->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'latest_apk_url' LIMIT 1");
            $apk_link_stmt->execute();
            $latest_apk_url = $apk_link_stmt->fetchColumn() ?: '#';
            ?>

            <!-- Permanent Download App Link -->
            <div style="padding: 0 1rem; margin-bottom: 1rem;">
                <a href="<?= htmlspecialchars($latest_apk_url) ?>" class="nav-item"
                    style="background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); color: white; border: none; border-radius: 12px; height: 44px; justify-content: center; box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);"
                    download>
                    <i data-lucide="download-cloud" class="icon" style="color: white;"></i>
                    <span style="font-weight: 700;">Download App</span>
                </a>
            </div>

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
<?php endif; ?>

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