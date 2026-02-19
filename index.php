<?php
session_start();
// Redirect if Employee (Must be before any output)
if (isset($_SESSION['user_role']) && strcasecmp(trim($_SESSION['user_role']), 'Employee') === 0) {
    header("Location: employee_dashboard.php");
    exit;
}

// Include necessary files
require_once 'config/db.php';
include 'includes/header.php';

// --- STATS CALCULATION ---

// 1. Total Employees
$stmt = $conn->query("SELECT COUNT(*) FROM employees");
$total_employees = $stmt->fetchColumn();

// 2. Present Today
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date = :date AND (status IN ('Present', 'On Time', 'Late', 'Half Day') OR clock_in IS NOT NULL)");
$stmt->execute(['date' => $today]);
$present_today = $stmt->fetchColumn();

// 3. Late Today (for On Time Calc)
$stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date = :date AND status = 'Late'");
$stmt->execute(['date' => $today]);
$late_today = $stmt->fetchColumn();

// Note: On Time = (Present - Late) / Present * 100 which might be weird if 0 present.
// Alternative: On Time % of Total Employees or just % of Present who are on time.
// Let's go with: Present On Time = (Present - Late). % = (Present On Time / Present) * 100
$on_time_percentage = 0;
if ($present_today > 0) {
    $on_time_percentage = round((($present_today - $late_today) / $present_today) * 100);
}

// --- CHART DATA (Last 7 Days) ---
// We will generate the bar heights dynamically.
$chart_data = [];
$chart_days = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_days[] = date('D', strtotime($d)); // Mon, Tue...

    $s = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date = :d AND (status IN ('Present', 'On Time', 'Late', 'Half Day') OR clock_in IS NOT NULL)");
    $s->execute(['d' => $d]);
    $count = $s->fetchColumn();
    // Normalize height for CSS (max 100%) - assuming max employees is total_employees
    $height = ($total_employees > 0) ? round(($count / $total_employees) * 100) : 0;
    $chart_data[] = $height;
}


// --- DEPARTMENT DATA ---
$sql_dept = "SELECT d.name, COUNT(e.id) as emp_count 
             FROM departments d 
             LEFT JOIN employees e ON d.id = e.department_id 
             GROUP BY d.id 
             ORDER BY emp_count DESC LIMIT 5";
$dept_stats = $conn->query($sql_dept)->fetchAll();

?>

<style>
    @media (max-width: 768px) {
        .page-content {
            background: #f5f7fa !important;
            min-height: 100vh !important;
            padding: 1rem !important;
        }

        .dashboard-header {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.5rem;
        }

        .page-subtitle {
            font-size: 0.85rem;
        }

        .dashboard-actions {
            width: 100%;
        }

        .stats-grid {
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
        }

        .stats-card {
            padding: 1.25rem !important;
        }

        .dashboard-content-grid {
            grid-template-columns: 1fr !important;
            gap: 1.5rem;
        }

        .card {
            margin-bottom: 1rem;
        }

        .chart-placeholder {
            height: 200px !important;
        }

        .bar-chart {
            padding: 0 0.5rem;
        }

        .bar-wrapper {
            width: 30px !important;
        }

        .label {
            font-size: 0.7rem;
        }

        .department-list {
            gap: 1rem !important;
        }

        .dept-info {
            font-size: 0.85rem;
        }

        .dept-count {
            font-size: 0.7rem !important;
        }
    }
</style>

<div class="page-content dashboard">
    <div class="dashboard-header">
        <div>
            <h2 class="page-title">Admin Dashboard</h2>
            <p class="page-subtitle">Welcome back, Admin! Here's what's happening today.</p>
        </div>
        <div class="dashboard-actions">
            <!-- <button class="btn-primary">+ Add Schedule</button> -->
            <span style="color:#64748b; font-size:0.9rem;"><?= date('l, d F Y') ?></span>
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <!-- Card 1: Total Employees -->
        <div class="card stats-card" style="border-left: 4px solid #3b82f6;">
            <div class="stats-icon-wrapper">
                <i data-lucide="users" class="icon" style="color: hsl(220, 70%, 50%); width:24px; height:24px;"></i>
            </div>
            <div class="stats-info">
                <span class="stats-title">Total Employees</span>
                <div class="stats-value-row">
                    <h3 class="stats-value"><?= $total_employees ?></h3>
                </div>
            </div>
        </div>



        <!-- Card 3: On Time % -->
        <div class="card stats-card" style="border-left: 4px solid #8b5cf6;">
            <div class="stats-icon-wrapper">
                <i data-lucide="clock" class="icon" style="color: hsl(260, 70%, 60%); width:24px; height:24px;"></i>
            </div>
            <div class="stats-info">
                <span class="stats-title">On Time Strength</span>
                <div class="stats-value-row">
                    <h3 class="stats-value"><?= $on_time_percentage ?>%</h3>
                    <span class="stats-trend" style="font-size:0.8rem; color:#64748b;">
                        of present
                    </span>
                </div>
            </div>
        </div>

        <!-- Card 4: Late Today (Replaced Revenue) -->
        <div class="card stats-card" style="border-left: 4px solid #f59e0b;">
            <div class="stats-icon-wrapper">
                <i data-lucide="alert-circle" class="icon"
                    style="color: hsl(40, 90%, 50%); width:24px; height:24px;"></i>
            </div>
            <div class="stats-info">
                <span class="stats-title">Late Arrivals</span>
                <div class="stats-value-row">
                    <h3 class="stats-value"><?= $late_today ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="dashboard-content-grid">
        <!-- Chart Section -->
        <div class="card left-panel">
            <div class="card-header">
                <h3>Attendance Overview (Last 7 Days)</h3>
            </div>
            <div class="chart-placeholder">
                <!-- Dynamic Bar Chart -->
                <div class="bar-chart">
                    <?php foreach ($chart_data as $key => $height): ?>
                        <div class="bar-wrapper">
                            <div class="bar" style="height: <?= $height ?>%;"></div>
                            <span class="label"><?= $chart_days[$key] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Department List -->
        <div class="card right-panel">
            <div class="card-header">
                <h3>Employees by Department</h3>
            </div>
            <div class="department-list">
                <?php
                $colors = ['var(--color-primary)', 'var(--color-secondary)', 'orange', 'teal', 'dodgerblue'];
                $i = 0;
                foreach ($dept_stats as $dept):
                    $color = $colors[$i % count($colors)];
                    // Calculate width % relative to total employees
                    $width = ($total_employees > 0) ? round(($dept['emp_count'] / $total_employees) * 100) : 0;
                    $i++;
                    ?>
                    <div class="dept-item">
                        <div class="dept-info">
                            <span><?= htmlspecialchars($dept['name']) ?></span>
                            <span class="dept-count"><?= $dept['emp_count'] ?> Employees</span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?= $width ?>%; background-color: <?= $color ?>;">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($dept_stats)): ?>
                    <p style="padding:1rem; color:#64748b; text-align:center;">No departments found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>