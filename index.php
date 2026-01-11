<?php
// Include necessary files
require_once 'config/db.php';
include 'includes/header.php';
?>

<div class="page-content dashboard">
    <div class="dashboard-header">
        <div>
            <h2 class="page-title">Admin Dashboard</h2>
            <p class="page-subtitle">Welcome back, Admin! Here's what's happening today.</p>
        </div>
        <div class="dashboard-actions">
            <button class="btn-primary">+ Add Schedule</button>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <!-- Card 1 -->
        <div class="card stats-card" style="border-left: 4px solid #3b82f6;">
            <div class="stats-icon-wrapper">
                <i data-lucide="users" class="icon" style="color: hsl(220, 70%, 50%); width:24px; height:24px;"></i>
            </div>
            <div class="stats-info">
                <span class="stats-title">Total Employees</span>
                <div class="stats-value-row">
                    <h3 class="stats-value">154</h3>
                    <span class="stats-trend positive">
                        <i data-lucide="trending-up" style="width:14px; height:14px;"></i> +5%
                    </span>
                </div>
            </div>
        </div>

        <!-- Card 2 -->
        <div class="card stats-card" style="border-left: 4px solid #ec4899;">
            <div class="stats-icon-wrapper">
                <i data-lucide="briefcase" class="icon" style="color: hsl(340, 70%, 50%); width:24px; height:24px;"></i>
            </div>
            <div class="stats-info">
                <span class="stats-title">Total Projects</span>
                <div class="stats-value-row">
                    <h3 class="stats-value">42</h3>
                    <span class="stats-trend positive">
                        <i data-lucide="trending-up" style="width:14px; height:14px;"></i> +12%
                    </span>
                </div>
            </div>
        </div>

        <!-- Card 3 -->
        <div class="card stats-card" style="border-left: 4px solid #10b981;">
            <div class="stats-icon-wrapper">
                <i data-lucide="check-circle" class="icon"
                    style="color: hsl(150, 70%, 40%); width:24px; height:24px;"></i>
            </div>
            <div class="stats-info">
                <span class="stats-title">On Time</span>
                <div class="stats-value-row">
                    <h3 class="stats-value">98%</h3>
                    <span class="stats-trend positive">
                        <i data-lucide="trending-up" style="width:14px; height:14px;"></i> +2%
                    </span>
                </div>
            </div>
        </div>

        <!-- Card 4 -->
        <div class="card stats-card" style="border-left: 4px solid #f59e0b;">
            <div class="stats-icon-wrapper">
                <i data-lucide="dollar-sign" class="icon"
                    style="color: hsl(40, 90%, 50%); width:24px; height:24px;"></i>
            </div>
            <div class="stats-info">
                <span class="stats-title">Revenue (Mo)</span>
                <div class="stats-value-row">
                    <h3 class="stats-value">$54,230</h3>
                    <span class="stats-trend positive">
                        <i data-lucide="trending-up" style="width:14px; height:14px;"></i> +8%
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-content-grid">
        <!-- Chart Section -->
        <div class="card left-panel">
            <div class="card-header">
                <h3>Attendance Overview</h3>
                <button class="icon-btn" style="border:none; background:none; cursor:pointer;">
                    <i data-lucide="more-horizontal" class="icon"></i>
                </button>
            </div>
            <div class="chart-placeholder">
                <!-- Simulated Chart -->
                <div class="bar-chart">
                    <?php
                    $data = [60, 80, 45, 90, 75, 50, 85];
                    $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                    foreach ($data as $key => $h) {
                        echo '<div class="bar-wrapper">
                                <div class="bar" style="height: ' . $h . '%;"></div>
                                <span class="label">' . $days[$key] . '</span>
                              </div>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Department List -->
        <div class="card right-panel">
            <div class="card-header">
                <h3>Employees by Department</h3>
                <button class="icon-btn" style="border:none; background:none; cursor:pointer;">
                    <i data-lucide="more-horizontal" class="icon"></i>
                </button>
            </div>
            <div class="department-list">
                <!-- Item 1 -->
                <div class="dept-item">
                    <div class="dept-info">
                        <span>Engineering</span>
                        <span class="dept-count">45 Employees</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: 80%; background-color: var(--color-primary);">
                        </div>
                    </div>
                </div>

                <!-- Item 2 -->
                <div class="dept-item">
                    <div class="dept-info">
                        <span>Design</span>
                        <span class="dept-count">24 Employees</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: 60%; background-color: var(--color-secondary);">
                        </div>
                    </div>
                </div>

                <!-- Item 3 -->
                <div class="dept-item">
                    <div class="dept-info">
                        <span>Marketing</span>
                        <span class="dept-count">18 Employees</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: 40%; background-color: orange;"></div>
                    </div>
                </div>

                <!-- Item 4 -->
                <div class="dept-item">
                    <div class="dept-info">
                        <span>HR</span>
                        <span class="dept-count">8 Employees</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: 20%; background-color: teal;"></div>
                    </div>
                </div>

                <!-- Item 5 -->
                <div class="dept-item">
                    <div class="dept-info">
                        <span>Sales</span>
                        <span class="dept-count">32 Employees</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: 55%; background-color: dodgerblue;"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>