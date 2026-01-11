<?php
require_once 'config/db.php';
include 'includes/header.php';

// Filter Date (Default Today)
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// --- STATS CALCULATION ---
// 1. Total Employees
$stmt = $conn->query("SELECT COUNT(*) FROM employees");
$total_employees = $stmt->fetchColumn();

// 2. Present Today
$stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date = :date AND status IN ('Present', 'Late', 'Half Day')");
$stmt->execute(['date' => $filter_date]);
$present_count = $stmt->fetchColumn();

// 3. Late Today
$stmt = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date = :date AND status = 'Late'");
$stmt->execute(['date' => $filter_date]);
$late_count = $stmt->fetchColumn();

// 4. Avg Work Hours (Today)
$stmt = $conn->prepare("SELECT AVG(total_hours) FROM attendance WHERE date = :date AND total_hours > 0");
$stmt->execute(['date' => $filter_date]);
$avg_hours = round($stmt->fetchColumn(), 1);

// --- FETCH ATTENDANCE LIST ---
// Join with employees to get names and departments
$sql = "SELECT e.id, e.first_name, e.last_name, e.employee_code, e.avatar, d.name as dept_name, a.clock_in, a.clock_out, a.status, a.total_hours 
        FROM employees e 
        LEFT JOIN departments d ON e.department_id = d.id 
        LEFT JOIN attendance a ON e.id = a.employee_id AND a.date = :date 
        ORDER BY e.first_name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute(['date' => $filter_date]);
$attendance_list = $stmt->fetchAll();

// --- CHART DATA (Last 7 Days Trend) ---
$chart_labels = [];
$chart_present = [];
$chart_late = [];

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('d M', strtotime($d));

    // Count Present
    $s1 = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date = :d AND status IN ('Present', 'Late', 'Half Day')");
    $s1->execute(['d' => $d]);
    $chart_present[] = $s1->fetchColumn();

    // Count Late
    $s2 = $conn->prepare("SELECT COUNT(*) FROM attendance WHERE date = :d AND status = 'Late'");
    $s2->execute(['d' => $d]);
    $chart_late[] = $s2->fetchColumn();
}

?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="page-content">
    <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
        <div>
            <h2 class="page-title">Attendance Overview</h2>
            <p class="page-subtitle">Track daily attendance and employee performance.</p>
        </div>

        <div style="display:flex; gap: 1rem;">
            <!-- Date Filter -->
            <form method="GET"
                style="display:flex; align-items:center; gap:0.5rem; background:white; padding:0.5rem; border-radius:8px; border:1px solid #e2e8f0;">
                <i data-lucide="calendar" style="width:16px; color:#64748b;"></i>
                <input type="date" name="date" value="<?= $filter_date ?>" onchange="this.form.submit()"
                    style="border:none; outline:none; color:#475569; font-family:inherit;">
            </form>

            <button class="btn-primary" onclick="exportCSV()">
                <i data-lucide="download" style="width:18px; margin-right:8px;"></i> Download Report
            </button>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid"
        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">

        <!-- Total Employees -->
        <div class="card" style="padding: 1.5rem; display:flex; align-items:center; gap:1rem;">
            <div
                style="width:50px; height:50px; border-radius:12px; background:hsl(220, 90%, 96%); color:hsl(220, 90%, 56%); display:flex; align-items:center; justify-content:center;">
                <i data-lucide="users" style="width:24px; height:24px;"></i>
            </div>
            <div>
                <div style="font-size: 0.9rem; color: #64748b; font-weight:500;">Total Employees</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
                    <?= $total_employees ?>
                </div>
            </div>
        </div>

        <!-- Present Today -->
        <div class="card" style="padding: 1.5rem; display:flex; align-items:center; gap:1rem;">
            <div
                style="width:50px; height:50px; border-radius:12px; background:hsl(142, 76%, 96%); color:hsl(142, 76%, 36%); display:flex; align-items:center; justify-content:center;">
                <i data-lucide="check-circle-2" style="width:24px; height:24px;"></i>
            </div>
            <div>
                <div style="font-size: 0.9rem; color: #64748b; font-weight:500;">Present Today</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
                    <?= $present_count ?> <span style="font-size:0.9rem; color:#64748b; font-weight:400;">/
                        <?= $total_employees ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Late Today -->
        <div class="card" style="padding: 1.5rem; display:flex; align-items:center; gap:1rem;">
            <div
                style="width:50px; height:50px; border-radius:12px; background:hsl(0, 84%, 96%); color:hsl(0, 84%, 60%); display:flex; align-items:center; justify-content:center;">
                <i data-lucide="clock" style="width:24px; height:24px;"></i>
            </div>
            <div>
                <div style="font-size: 0.9rem; color: #64748b; font-weight:500;">Late Today</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
                    <?= $late_count ?>
                </div>
            </div>
        </div>

        <!-- Avg Hours -->
        <div class="card" style="padding: 1.5rem; display:flex; align-items:center; gap:1rem;">
            <div
                style="width:50px; height:50px; border-radius:12px; background:hsl(250, 90%, 96%); color:hsl(250, 90%, 60%); display:flex; align-items:center; justify-content:center;">
                <i data-lucide="timer" style="width:24px; height:24px;"></i>
            </div>
            <div>
                <div style="font-size: 0.9rem; color: #64748b; font-weight:500;">Avg. Work Hours</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #1e293b;">
                    <?= $avg_hours ?>h
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="card" style="padding: 1.5rem; margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1.5rem; font-size: 1.1rem; color: #334155;">Attendance Flow (Last 7 Days)</h3>
        <div style="height: 300px;">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>

    <!-- Attendance List -->
    <div class="card">
        <div class="card-header">
            <h3>Daily Report -
                <?= date('d M Y', strtotime($filter_date)) ?>
            </h3>
        </div>
        <table class="table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>Clock In</th>
                    <th>Clock Out</th>
                    <th>Hours</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($attendance_list as $row):
                    $status_color = 'gray';
                    if ($row['status'] == 'Present')
                        $status_color = 'green';
                    if ($row['status'] == 'Absent')
                        $status_color = 'red';
                    if ($row['status'] == 'Late')
                        $status_color = 'orange';
                    if ($row['status'] == 'Half Day')
                        $status_color = 'yellow';

                    // Fallback Status if no record
                    $display_status = $row['status'] ?? 'Absent';
                    if ($row['status'] == null)
                        $status_color = 'red';
                    ?>
                    <tr>
                        <td>
                            <div style="display:flex; align-items:center; gap:0.75rem;">
                                <!-- Avatar or Initials -->
                                <div
                                    style="width:36px; height:36px; border-radius:50%; background: #f1f5f9; display:flex; align-items:center; justify-content:center; overflow:hidden; font-weight:bold; color:#64748b; flex-shrink:0;">
                                    <?php if (!empty($row['avatar'])): ?>
                                        <img src="<?= $row['avatar'] ?>" style="width:100%; height:100%; object-fit:cover;">
                                    <?php else: ?>
                                        <?= strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div style="font-weight:500; color:#1e293b;">
                                        <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                    </div>
                                    <div style="font-size:0.8rem; color:#64748b;">
                                        <?= htmlspecialchars($row['employee_code']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge" style="background:#f8fafc; color:#475569; border:1px solid #e2e8f0;">
                                <?= htmlspecialchars($row['dept_name'] ?? '-') ?>
                            </span></td>
                        <td>
                            <span class="badge" style="
                            <?php
                            if ($display_status == 'Present')
                                echo 'background:#dcfce7; color:#166534;';
                            elseif ($display_status == 'Absent')
                                echo 'background:#fee2e2; color:#991b1b;';
                            elseif ($display_status == 'Late')
                                echo 'background:#ffedd5; color:#9a3412;';
                            else
                                echo 'background:#f1f5f9; color:#475569;';
                            ?>
                        ">
                                <?= $display_status ?>
                            </span>
                        </td>
                        <td style="font-family:monospace;">
                            <?= $row['clock_in'] ? date('h:i A', strtotime($row['clock_in'])) : '--:--' ?>
                        </td>
                        <td style="font-family:monospace;">
                            <?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : '--:--' ?>
                        </td>
                        <td>
                            <?= $row['total_hours'] ? $row['total_hours'] . 'h' : '-' ?>
                        </td>
                        <td>
                            <button class="btn-icon" title="Edit Attendance (Not Implemented)">
                                <i data-lucide="more-horizontal" style="width:16px;"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Initialize Chart
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($chart_labels) ?>,
            datasets: [
                {
                    label: 'Present',
                    data: <?= json_encode($chart_present) ?>,
                    backgroundColor: '#10b981',
                    borderRadius: 4,
                },
                {
                    label: 'Late',
                    data: <?= json_encode($chart_late) ?>,
                    backgroundColor: '#f59e0b',
                    borderRadius: 4,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                x: { grid: { display: false } }
            }
        }
    });

    // CSV Export Function
    function exportCSV() {
        const rows = [
            ['Employee Code', 'Name', 'Department', 'Date', 'Status', 'Clock In', 'Clock Out', 'Hours'],
            <?php foreach ($attendance_list as $row): ?>
                [
                    "<?= $row['employee_code'] ?>",
                    "<?= $row['first_name'] . ' ' . $row['last_name'] ?>",
                    "<?= $row['dept_name'] ?>",
                    "<?= $filter_date ?>",
                    "<?= $row['status'] ?? 'Absent' ?>",
                    "<?= $row['clock_in'] ?? '' ?>",
                    "<?= $row['clock_out'] ?? '' ?>",
                    "<?= $row['total_hours'] ?? '0' ?>"
                ],
            <?php endforeach; ?>
        ];

        let csvContent = "data:text/csv;charset=utf-8,"
            + rows.map(e => e.join(",")).join("\n");

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "attendance_report_<?= $filter_date ?>.csv");
        document.body.appendChild(link);
        link.click();
    }
</script>

<?php include 'includes/footer.php'; ?>