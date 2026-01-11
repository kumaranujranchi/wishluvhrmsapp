<?php
// Function to check active link
function isActive($page)
{
    $current_page = basename($_SERVER['PHP_SELF'], ".php");
    return ($current_page == $page) ? 'active' : '';
}
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
            <button class="nav-item dropdown-btn" onclick="toggleSubNav('employeeSubNav', this)">
                <div class="nav-item-content">
                    <i data-lucide="users" class="icon"></i>
                    <span>Employee Onboarding</span>
                </div>
                <i data-lucide="chevron-right" class="icon chevron-icon"></i>
            </button>
            <div id="employeeSubNav" class="sub-nav">
                <a href="designation.php" class="sub-nav-item">
                    <i data-lucide="briefcase" class="icon" style="width:16px;height:16px;"></i>
                    <span>Designation</span>
                </a>
                <a href="department.php" class="sub-nav-item">
                    <i data-lucide="building-2" class="icon" style="width:16px;height:16px;"></i>
                    <span>Department</span>
                </a>
                <a href="add_employee.php" class="sub-nav-item">
                    <i data-lucide="user-plus" class="icon" style="width:16px;height:16px;"></i>
                    <span>Onboard Employee</span>
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
            <div class="user-avatar">AD</div>
            <div class="user-details">
                <span class="user-name">Admin User</span>
                <span class="user-role">Super Admin</span>
            </div>
        </div>
    </div>
</aside>

<script>
    function toggleSubNav(id, btn) {
        const subNav = document.getElementById(id);
        const icon = btn.querySelector('.chevron-icon');

        subNav.classList.toggle('open');

        // Simple icon rotation logic (assuming we want to simulate state change visually)
        if (subNav.classList.contains('open')) {
            // In a real scenario we might swap the icon or rotate it via CSS class
            // For now, let's keep it simple as the original design.
            // You can add a class to rotate the chevron if you like.
            icon.style.transform = "rotate(90deg)";
        } else {
            icon.style.transform = "rotate(0deg)";
        }
    }
</script>