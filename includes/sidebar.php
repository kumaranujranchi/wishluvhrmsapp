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
        <div class="logo-icon">HR</div>
        <h1 class="logo-text">Unity HR</h1>
    </div>

    <nav class="sidebar-nav">
        <a href="index.php" class="nav-item <?php echo isActive('index'); ?>">
            <i data-lucide="layout-dashboard" class="icon"></i>
            <span>Dashboard</span>
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
                <a href="add_employee.php" class="sub-nav-item <?php echo isActive('add_employee'); ?>">
                    <i data-lucide="user-plus" class="icon" style="width:16px;height:16px;"></i>
                    <span>Add Employee</span>
                </a>
            </div>
        </div>

        <a href="#" class="nav-item">
            <i data-lucide="calendar-check" class="icon"></i>
            <span>Attendance</span>
        </a>

        <a href="#" class="nav-item">
            <i data-lucide="coffee" class="icon"></i>
            <span>Leaves</span>
        </a>

        <a href="#" class="nav-item">
            <i data-lucide="palmtree" class="icon"></i>
            <span>Holidays</span>
        </a>

        <a href="#" class="nav-item">
            <i data-lucide="banknote" class="icon"></i>
            <span>Payroll</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar"><?= $userInitials ?></div>
            <div class="user-details">
                <span class="user-name"><?= htmlspecialchars($userName) ?></span>
                <span class="user-role"><?= htmlspecialchars($userRole) ?></span>
            </div>
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