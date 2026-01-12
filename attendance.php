<?php
require_once 'config/db.php';
include 'includes/header.php';

// Date Filter
$filter_date = $_GET['date'] ?? date('Y-m-d');

// --- STATS CALCULATION ---
$total_employees = $conn->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$present_count = $conn->prepare("SELECT COUNT(DISTINCT employee_id) FROM attendance WHERE date = :date");
$present_count->execute(['date' => $filter_date]);
$present_count = $present_count->fetchColumn();

$late_count = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date = :date AND status = 'Late'");
$late_count->execute(['date' => $filter_date]);
$late_count = $late_count->fetchColumn();

// Avg Hours
$avg_hours = $conn->prepare("SELECT AVG(total_hours) FROM attendance WHERE date = :date AND total_hours > 0");
$avg_hours->execute(['date' => $filter_date]);
$avg = round($avg_hours->fetchColumn(), 1);

// --- CHART DATA (Last 7 Days) ---
$chart_labels = [];
$chart_present = [];
$chart_late = [];

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($d));
    
    // Present
    $p = $conn->prepare("SELECT COUNT(DISTINCT employee_id) FROM attendance WHERE date = :d");
    $p->execute(['d' => $d]);
    $chart_present[] = $p->fetchColumn();
    
    // Late
    $l = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date = :d AND status = 'Late'");
    $l->execute(['d' => $d]);
    $chart_late[] = $l->fetchColumn();
}


// --- ATTENDANCE LIST ---
// Fetch attendance combined with employee details
$sql = "SELECT a.*, e.first_name, e.last_name, e.employee_code, e.avatar, d.name as dept_name 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id 
        LEFT JOIN departments d ON e.department_id = d.id 
        WHERE a.date = :date 
        ORDER BY a.clock_in DESC";
$stmt = $conn->prepare($sql);
$stmt->execute(['date' => $filter_date]);
$attendance_records = $stmt->fetchAll();

// CSV Export Logic
if (isset($_POST['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_' . $filter_date . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Employee', 'Code', 'Department', 'Date', 'Status', 'Clock In', 'Location In', 'Clock Out', 'Location Out', 'Total Hours']);
    
    foreach ($attendance_records as $row) {
        fputcsv($output, [
            $row['first_name'] . ' ' . $row['last_name'],
            $row['employee_code'],
            $row['dept_name'],
            $row['date'],
            $row['status'],
            $row['clock_in'],
            $row['clock_in_address'],
            $row['clock_out'],
            $row['clock_out_address'],
            $row['total_hours']
        ]);
    }
    fclose($output);
    exit;
}
?>

<div class="page-content">
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="card stats-card" style="border-left: 4px solid #3b82f6;">
           <div class="stats-info">
               <span class="stats-title">Total Employees</span>
               <h3 class="stats-value"><?= $total_employees ?></h3>
           </div>
           <div class="stats-icon-wrapper"><i data-lucide="users" class="icon" style="color:#3b82f6;"></i></div>
        </div>
        <div class="card stats-card" style="border-left: 4px solid #10b981;">
           <div class="stats-info">
               <span class="stats-title">Present Today</span>
               <h3 class="stats-value"><?= $present_count ?></h3>
           </div>
           <div class="stats-icon-wrapper"><i data-lucide="user-check" class="icon" style="color:#10b981;"></i></div>
        </div>
        <div class="card stats-card" style="border-left: 4px solid #f59e0b;">
           <div class="stats-info">
               <span class="stats-title">Late Today</span>
               <h3 class="stats-value"><?= $late_count ?></h3>
           </div>
           <div class="stats-icon-wrapper"><i data-lucide="clock" class="icon" style="color:#f59e0b;"></i></div>
        </div>
        <div class="card stats-card" style="border-left: 4px solid #8b5cf6;">
           <div class="stats-info">
               <span class="stats-title">Avg. Hours</span>
               <h3 class="stats-value"><?= $avg ?> hr</h3>
           </div>
           <div class="stats-icon-wrapper"><i data-lucide="timer" class="icon" style="color:#8b5cf6;"></i></div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="content-grid" style="grid-template-columns: 1fr; margin-bottom: 2rem;">
        <div class="card">
            <div class="card-header">
                <h3>Attendance Trends (Last 7 Days)</h3>
            </div>
            <div style="height: 300px; padding: 1rem;">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Filters & List -->
    <div class="card">
        <div class="card-header" style="justify-content: space-between;">
            <h3>Daily Attendance</h3>
            <form method="GET" class="filter-form" style="display:flex; gap:10px;">
                <input type="date" name="date" class="form-control" value="<?= $filter_date ?>" onchange="this.form.submit()">
                <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem;">Filter</button>
            </form>
             <form method="POST">
                <button type="submit" name="export_csv" class="btn-primary" style="background:var(--color-success); border-color:var(--color-success);">
                    <i data-lucide="download" style="width:16px; margin-right:5px;"></i> Export CSV
                </button>
            </form>
        </div>
        
        <!-- Desktop View -->
        <div class="desktop-only">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Status</th>
                            <th>Check In</th>
                            <th>Location In</th>
                            <th>Check Out</th>
                            <th>Location Out</th>
                            <th>Total Hrs</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $row): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div style="width:35px; height:35px; background:#f1f5f9; border-radius:50%; display:flex; align-items:center; justify-content:center; overflow:hidden; font-weight:bold; color:#64748b;">
                                            <?php if ($row['avatar']): ?>
                                                <img src="<?= $row['avatar'] ?>" style="width:100%; height:100%; object-fit:cover;">
                                            <?php else: ?>
                                                <?= strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:500;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                            <div style="font-size:0.75rem; color:#64748b;"><?= htmlspecialchars($row['dept_name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $sColor = match($row['status']) {
                                            'Present' => 'background:#dcfce7; color:#166534;',
                                            'Late' => 'background:#fef9c3; color:#854d0e;',
                                            'Half Day' => 'background:#ffedd5; color:#9a3412;',
                                            'Absent' => 'background:#fee2e2; color:#991b1b;',
                                            default => 'background:#f1f5f9; color:#475569;'
                                        };
                                    ?>
                                    <span class="badge" style="<?= $sColor ?>"><?= $row['status'] ?></span>
                                </td>
                                <td style="font-weight:500;"><?= date('h:i A', strtotime($row['clock_in'])) ?></td>
                                <td>
                                    <div style="font-size:0.8rem; max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($row['clock_in_address']) ?>">
                                        <i data-lucide="map-pin" style="width:12px; vertical-align:middle; color:#64748b;"></i>
                                        <?= htmlspecialchars($row['clock_in_address'] ?? '-') ?>
                                    </div>
                                </td>
                                <td style="font-weight:500;"><?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : '<span style="color:#cbd5e1;">--:--</span>' ?></td>
                                <td>
                                    <div style="font-size:0.8rem; max-width:150px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($row['clock_out_address']) ?>">
                                        <?php if($row['clock_out_address']): ?>
                                            <i data-lucide="map-pin" style="width:12px; vertical-align:middle; color:#64748b;"></i>
                                            <?= htmlspecialchars($row['clock_out_address']) ?>
                                        <?php else: ?>
                                            <span style="color:#cbd5e1;">-</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="font-weight:600;"><?= $row['total_hours'] ? $row['total_hours'] . ' hr' : '-' ?></td>
                                <td style="text-align:right;">
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= $row['clock_in_lat'] ?>,<?= $row['clock_in_lng'] ?>" target="_blank" class="btn-icon" title="View Map" style="display:inline-flex; align-items:center; justify-content:center;">
                                        <i data-lucide="map" style="width:16px;"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($attendance_records)): ?>
                            <tr><td colspan="8" style="text-align:center; padding:2rem; color:#64748b;">No attendance records for this date.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile View -->
        <div class="mobile-only">
            <div class="mobile-card-list">
                <?php if (empty($attendance_records)): ?>
                    <div style="text-align:center; padding:2rem; color:#64748b;">No attendance records for this date.</div>
                <?php else: ?>
                    <?php foreach ($attendance_records as $row): ?>
                        <div class="mobile-card">
                            <div class="mobile-card-header" onclick="this.parentElement.classList.toggle('expanded')">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="width:35px; height:35px; background:#f1f5f9; border-radius:10px; display:flex; align-items:center; justify-content:center; overflow:hidden; font-weight:bold; color:#64748b; font-size:0.75rem;">
                                        <?php if ($row['avatar']): ?>
                                            <img src="<?= $row['avatar'] ?>" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <?= strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:600; font-size:0.9rem; color:#1e293b;"><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></div>
                                        <div style="font-size:0.75rem; color:#64748b;"><?= date('h:i A', strtotime($row['clock_in'])) ?> - <?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : 'In' ?></div>
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?php 
                                        $sColorMobile = match($row['status']) {
                                            'Present' => 'background:#dcfce7; color:#166534;',
                                            'Late' => 'background:#fef9c3; color:#854d0e;',
                                            'Half Day' => 'background:#ffedd5; color:#9a3412;',
                                            'Absent' => 'background:#fee2e2; color:#991b1b;',
                                            default => 'background:#f1f5f9; color:#475569;'
                                        };
                                    ?>
                                    <span class="badge" style="font-size:0.7rem; <?= $sColorMobile ?>"><?= $row['status'] ?></span>
                                    <i data-lucide="chevron-down" class="toggle-icon" style="width:18px;"></i>
                                </div>
                            </div>
                            <div class="mobile-card-body">
                                <div class="mobile-field">
                                    <span class="mobile-label">Department</span>
                                    <span class="mobile-value"><?= htmlspecialchars($row['dept_name'] ?? '-') ?></span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Total Hours</span>
                                    <span class="mobile-value" style="font-weight:600; color:#3b82f6;"><?= $row['total_hours'] ? $row['total_hours'] . ' hr' : '-' ?></span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Check-In Location</span>
                                    <span class="mobile-value" style="font-size:0.8rem; line-height:1.4; color:#64748b;"><?= htmlspecialchars($row['clock_in_address'] ?? '-') ?></span>
                                </div>
                                <?php if($row['clock_out_address']): ?>
                                    <div class="mobile-field">
                                        <span class="mobile-label">Check-Out Location</span>
                                        <span class="mobile-value" style="font-size:0.8rem; line-height:1.4; color:#64748b;"><?= htmlspecialchars($row['clock_out_address']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div style="margin-top:1.5rem;">
                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= $row['clock_in_lat'] ?>,<?= $row['clock_in_lng'] ?>" target="_blank" class="btn-primary" style="width:100%; justify-content:center; background:#f1f5f9; color:#475569; border:1px solid #e2e8f0; text-decoration:none;">
                                        <i data-lucide="map" style="width:16px; margin-right:8px;"></i> View on Google Maps
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Chart Config
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [{
                label: 'Present',
                data: <?= json_encode($chart_present) ?>,
                backgroundColor: '#10b981',
                borderRadius: 4
            }, {
                label: 'Late',
                data: <?= json_encode($chart_late) ?>,
                backgroundColor: '#f59e0b',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>