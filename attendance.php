<?php
require_once 'config/db.php';

// Date Filter
$filter_date = $_GET['date'] ?? date('Y-m-d');

// --- ATTENDANCE LIST (Needed for both export and display) ---
$sql = "SELECT a.*, e.first_name, e.last_name, e.employee_code, e.avatar, d.name as dept_name 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id 
        LEFT JOIN departments d ON e.department_id = d.id 
        WHERE a.date = :date 
        ORDER BY a.clock_in DESC";
$stmt = $conn->prepare($sql);
$stmt->execute(['date' => $filter_date]);
$attendance_records = $stmt->fetchAll();

// Helper function to format duration from minutes to hours:minutes
function formatDuration($total_minutes)
{
    if (!$total_minutes || $total_minutes == 0)
        return '-';
    $hours = floor($total_minutes / 60);
    $minutes = $total_minutes % 60;
    return sprintf('%d:%02d', $hours, $minutes);
}


// CSV Export Logic (Must be BEFORE any HTML output)
if (isset($_POST['export_csv'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="attendance_' . $filter_date . '.csv"');
    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility with special characters (Hindi)
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

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
            formatDuration($row['total_hours'])
        ]);
    }
    fclose($output);
    exit;
}

include 'includes/header.php';

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
$avg_minutes = round($avg_hours->fetchColumn());
$avg = formatDuration($avg_minutes);


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
?>

<style>
    @media (max-width: 768px) {
        .page-content {
            background: #f5f7fa !important;
            min-height: 100vh !important;
            padding: 1rem !important;
        }

        .stats-grid {
            grid-template-columns: 1fr !important;
            gap: 1rem !important;
        }

        .card {
            margin-bottom: 1rem;
        }

        .filter-form {
            flex-direction: column !important;
            width: 100%;
        }

        .filter-form input[type="date"] {
            width: 100% !important;
        }

        .header-action-btn {
            width: 100%;
        }

        .page-header-flex {
            flex-direction: column !important;
            gap: 1rem !important;
            align-items: stretch !important;
        }

        .page-header-flex>div {
            width: 100%;
        }
    }
</style>

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
                <h3 class="stats-value"><?= $avg ?></h3>
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
    <div class="card" style="padding: 0; margin-top: 2rem;">
        <div class="card-header page-header-flex" style="border-bottom: 1px solid #f1f5f9; padding: 1.25rem 1.5rem;">
            <div class="page-header-info">
                <h3 style="margin:0; font-size: 1.1rem; color: #1e293b;">Daily Attendance</h3>
            </div>
            <div style="display:flex; gap:12px; align-items:center;">
                <form method="GET" class="filter-form" style="display:flex; gap:8px; align-items:center;">
                    <div style="position:relative; display:flex; align-items:center;">
                        <input type="date" name="date" class="form-control" value="<?= $filter_date ?>"
                            onchange="this.form.submit()"
                            style="padding: 0.6rem 0.8rem; height: 42px; font-size: 0.9rem; border-radius: 10px; width: 180px;">
                    </div>
                    <button type="submit" class="btn-primary header-action-btn"
                        style="height: 42px; padding: 0 1.5rem; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); border-radius: 10px; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);">
                        Filter
                    </button>
                </form>
                <form method="POST">
                    <button type="submit" name="export_csv" class="btn-primary header-action-btn"
                        style="height: 42px; padding: 0 1.25rem; background: #0f172a; border-radius: 10px;">
                        <i data-lucide="download" style="width:18px;"></i>
                        <span class="desktop-only" style="margin-left: 6px;">Export CSV</span>
                    </button>
                </form>
            </div>
        </div>

        <!-- Desktop View -->
        <div class="desktop-only">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Status</th>
                            <th>Clock In</th>
                            <th>In Location</th>
                            <th>Clock Out</th>
                            <th>Out Location</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendance_records as $row): ?>
                            <tr>
                                <td>
                                    <div style="display:flex; align-items:center; gap:10px;">
                                        <div
                                            style="width:32px; height:32px; border-radius:8px; background:#e2e8f0; overflow:hidden; display:flex; align-items:center; justify-content:center; font-weight:bold; color:#64748b; font-size:0.8rem;">
                                            <?php if ($row['avatar']): ?>
                                                <img src="<?= $row['avatar'] ?>"
                                                    style="width:100%; height:100%; object-fit:cover;">
                                            <?php else: ?>
                                                <?= strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:600; font-size:0.9rem; color:#1e293b;">
                                                <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                            </div>
                                            <div style="font-size:0.75rem; color:#64748b;"><?= $row['employee_code'] ?>
                                                &bull;
                                                <?= $row['dept_name'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                    $sColor = match ($row['status']) {
                                        'On Time' => 'background:#dcfce7; color:#166534;',
                                        'Late' => 'background:#fef9c3; color:#854d0e;',
                                        'Present' => 'background:#dbeafe; color:#1e40af;',
                                        'Half Day' => 'background:#ffedd5; color:#9a3412;',
                                        'Absent' => 'background:#fee2e2; color:#991b1b;',
                                        default => 'background:#f1f5f9; color:#475569;'
                                    };
                                    ?>
                                    <span class="badge" style="<?= $sColor ?>"><?= $row['status'] ?></span>
                                </td>
                                <td style="font-weight:500;"><?= date('h:i A', strtotime($row['clock_in'])) ?></td>
                                <td>
                                    <div style="display:flex; align-items:flex-start; gap:6px;">
                                        <div style="font-size:0.8rem; color: #475569; line-height: 1.4;"
                                            title="<?= htmlspecialchars($row['clock_in_address']) ?>">
                                            <i data-lucide="map-pin"
                                                style="width:12px; vertical-align:middle; color:#94a3b8; margin-right: 4px;"></i>
                                            <?= htmlspecialchars($row['clock_in_address'] ?? '-') ?>
                                        </div>
                                        <?php if ($row['clock_in_lat']): ?>
                                            <a href="https://www.google.com/maps/search/?api=1&query=<?= $row['clock_in_lat'] ?>,<?= $row['clock_in_lng'] ?>"
                                                target="_blank" title="View Map" style="color:#6366f1; flex-shrink:0;">
                                                <i data-lucide="map" style="width:14px;"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($row['out_of_range']): ?>
                                        <div
                                            style="font-size:0.7rem; color:#dc2626; font-weight:600; margin-top:4px; max-width:200px;">
                                            ⚠️ Out of Office: <?= htmlspecialchars($row['out_of_range_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="font-weight:500;">
                                    <?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : '<span style="color:#cbd5e1;">--:--</span>' ?>
                                </td>
                                <td>
                                    <div style="display:flex; align-items:flex-start; gap:6px;">
                                        <div style="font-size:0.8rem; color: #475569; line-height: 1.4;"
                                            title="<?= htmlspecialchars($row['clock_out_address']) ?>">
                                            <?php if ($row['clock_out_address']): ?>
                                                <i data-lucide="map-pin"
                                                    style="width:12px; vertical-align:middle; color:#94a3b8; margin-right: 4px;"></i>
                                                <?= htmlspecialchars($row['clock_out_address']) ?>
                                            <?php else: ?>
                                                <span style="color:#cbd5e1;">-</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($row['clock_out_lat']): ?>
                                            <a href="https://www.google.com/maps/search/?api=1&query=<?= $row['clock_out_lat'] ?>,<?= $row['clock_out_lng'] ?>"
                                                target="_blank" title="View Map" style="color:#6366f1; flex-shrink:0;">
                                                <i data-lucide="map" style="width:14px;"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="font-weight:600;"><?= formatDuration($row['total_hours']) ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (empty($attendance_records)): ?>
                            <tr>
                                <td colspan="7" style="text-align:center; padding:2rem; color:#64748b;">No attendance
                                    records
                                    for this date.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile View -->
        <div class="mobile-only">
            <div class="mobile-card-list">
                <?php if (empty($attendance_records)): ?>
                    <div
                        style="text-align:center; padding:2.5rem; color:#64748b; background:#f8fafc; border-radius:1rem; margin:1rem;">
                        <i data-lucide="calendar-x" style="width:32px; margin-bottom:10px; opacity:0.5;"></i>
                        <p>No records for this date.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($attendance_records as $row): ?>
                        <div class="mobile-card" style="margin-bottom:0.75rem;">
                            <div class="mobile-card-header" onclick="this.parentElement.classList.toggle('expanded')"
                                style="padding:1rem;">
                                <div style="display:flex; align-items:center; gap:12px; flex:1;">
                                    <div
                                        style="width:40px; height:40px; background:#e0e7ff; border-radius:12px; display:flex; align-items:center; justify-content:center; overflow:hidden; font-weight:700; color:#4f46e5; font-size:0.9rem;">
                                        <?php if ($row['avatar']): ?>
                                            <img src="<?= $row['avatar'] ?>" style="width:100%; height:100%; object-fit:cover;">
                                        <?php else: ?>
                                            <?= strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div style="overflow:hidden;">
                                        <div
                                            style="font-weight:700; font-size:0.95rem; color:#1e293b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                            <?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?>
                                        </div>
                                        <div
                                            style="font-size:0.75rem; color:#64748b; display:flex; align-items:center; gap:4px;">
                                            <i data-lucide="clock" style="width:12px;"></i>
                                            <?= date('h:i A', strtotime($row['clock_in'])) ?> -
                                            <?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : 'Active' ?>
                                        </div>
                                    </div>
                                </div>
                                <div style="display:flex; align-items:center; gap:8px;">
                                    <?php
                                    $sColorMobile = match ($row['status']) {
                                        'On Time' => 'background:#dcfce7; color:#166534;',
                                        'Late' => 'background:#fef9c3; color:#854d0e;',
                                        'Present' => 'background:#dbeafe; color:#1e40af;',
                                        'Half Day' => 'background:#ffedd5; color:#9a3412;',
                                        'Absent' => 'background:#fee2e2; color:#991b1b;',
                                        default => 'background:#f1f5f9; color:#475569;'
                                    };
                                    ?>
                                    <span class="badge"
                                        style="<?= $sColorMobile ?> font-size:0.65rem; padding:2px 8px; border-radius:50px;"><?= $row['status'] ?></span>
                                    <i data-lucide="chevron-down" class="toggle-icon" style="width:18px;"></i>
                                </div>
                            </div>
                            <div class="mobile-card-body"
                                style="padding:1.25rem; background:#f8fafc; border-top:1px solid #f1f5f9;">
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:1.5rem;">
                                    <div class="mobile-field">
                                        <span class="mobile-label">In Time</span>
                                        <span class="mobile-value"><i data-lucide="log-in"
                                                style="width:14px; vertical-align:middle; color:#10b981; margin-right:4px;"></i>
                                            <?= date('h:i A', strtotime($row['clock_in'])) ?></span>
                                        <?php if ($row['clock_in_address']): ?>
                                            <div style="display:flex; align-items:flex-start; gap:8px; margin-top:4px;">
                                                <small style="font-size:0.7rem; color:#94a3b8; line-height:1.3; flex:1;">
                                                    <?= htmlspecialchars($row['clock_in_address']) ?>
                                                </small>
                                                <?php if ($row['clock_in_lat']): ?>
                                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= $row['clock_in_lat'] ?>,<?= $row['clock_in_lng'] ?>"
                                                        target="_blank" style="color:#6366f1;">
                                                        <i data-lucide="map" style="width:14px;"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($row['out_of_range']): ?>
                                                <div
                                                    style="font-size:0.65rem; color:#dc2626; font-weight:600; margin-top:5px; background:#fef2f2; padding:4px 8px; border-radius:4px;">
                                                    ⚠️ Out of Office: <?= htmlspecialchars($row['out_of_range_reason']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mobile-field">
                                        <span class="mobile-label">Out Time</span>
                                        <span class="mobile-value"><i data-lucide="log-out"
                                                style="width:14px; vertical-align:middle; color:#ef4444; margin-right:4px;"></i>
                                            <?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : 'Working' ?></span>
                                        <?php if ($row['clock_out_address']): ?>
                                            <div style="display:flex; align-items:flex-start; gap:8px; margin-top:4px;">
                                                <small style="font-size:0.7rem; color:#94a3b8; line-height:1.3; flex:1;">
                                                    <?= htmlspecialchars($row['clock_out_address']) ?>
                                                </small>
                                                <?php if ($row['clock_out_lat']): ?>
                                                    <a href="https://www.google.com/maps/search/?api=1&query=<?= $row['clock_out_lat'] ?>,<?= $row['clock_out_lng'] ?>"
                                                        target="_blank" style="color:#6366f1;">
                                                        <i data-lucide="map" style="width:14px;"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div
                                    style="display:flex; justify-content:space-between; align-items:center; margin-top:1.5rem; padding-top:1rem; border-top:1px solid #e2e8f0;">
                                    <div class="mobile-field" style="margin:0;">
                                        <span class="mobile-label">Working Hours</span>
                                        <span class="mobile-value"
                                            style="color:#6366f1; font-weight:700; font-size:1.1rem;"><?= formatDuration($row['total_hours']) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div> <!-- Close .card -->
</div> <!-- Close .page-content -->


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