<?php
require_once 'config/db.php';

// Ensure session is started and user is authorized before any output
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] === 'Employee') {
    header("Location: employee_dashboard.php");
    exit;
}

// 1. Get Filters
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');
$start_date = "$year-$month-01";
$end_date = date('Y-m-t', strtotime($start_date));
$num_days = date('t', strtotime($start_date));

// 2. Fetch Employees
$emp_stmt = $conn->query("SELECT id, first_name, last_name, employee_code, department_id FROM employees ORDER BY first_name ASC");
$employees = $emp_stmt->fetchAll();

// 3. Fetch Attendance Logs for the month
$sql = "SELECT a.*, e.id as emp_id 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id 
        WHERE a.date BETWEEN :start AND :end";
$stmt = $conn->prepare($sql);
$stmt->execute(['start' => $start_date, 'end' => $end_date]);
$logs = $stmt->fetchAll();

// 4. Pivot Data: [emp_id][day] = log
$matrix = [];
foreach ($logs as $log) {
    $day = (int) date('j', strtotime($log['date']));
    $matrix[$log['emp_id']][$day] = $log;
}

// 5. Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Monthly_Matrix_' . $month . '_' . $year . '.csv"');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM

    // Headers: Emp Code, Emp Name, Days 1..N, Summary
    $headers = ['Code', 'Name'];
    for ($d = 1; $d <= $num_days; $d++) {
        $headers[] = $d;
    }
    $headers[] = 'Present';
    $headers[] = 'Late';
    fputcsv($output, $headers);

    foreach ($employees as $emp) {
        $row = [$emp['employee_code'], $emp['first_name'] . ' ' . $emp['last_name']];
        $present = 0;
        $late = 0;

        for ($d = 1; $d <= $num_days; $d++) {
            if (isset($matrix[$emp['id']][$d])) {
                $log = $matrix[$emp['id']][$d];
                $in = $log['clock_in'] ? date('H:i', strtotime($log['clock_in'])) : '-';
                $out = $log['clock_out'] ? date('H:i', strtotime($log['clock_out'])) : '-';
                $row[] = "$in / $out";
                $present++;
                if ($log['status'] === 'Late')
                    $late++;
            } else {
                $row[] = 'A';
            }
        }
        $row[] = $present;
        $row[] = $late;
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

include 'includes/header.php';
?>

<style>
    .matrix-table-wrapper {
        overflow-x: auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 1px solid #f1f5f9;
        margin-top: 1.5rem;
    }

    .matrix-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.75rem;
    }

    .matrix-table th,
    .matrix-table td {
        border: 1px solid #f1f5f9;
        padding: 8px 4px;
        text-align: center;
        min-width: 60px;
    }

    .matrix-table th {
        background: #f8fafc;
        font-weight: 700;
        color: #475569;
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .matrix-table .emp-col {
        position: sticky;
        left: 0;
        background: #f8fafc;
        z-index: 20;
        text-align: left;
        min-width: 180px;
        padding-left: 12px;
    }

    .matrix-table td.emp-col {
        background: white;
        font-weight: 600;
    }

    .time-val {
        display: block;
        line-height: 1.2;
    }

    .status-a {
        color: #ef4444;
        font-weight: 700;
    }

    .status-present {
        color: #10b981;
    }

    .status-late {
        color: #f59e0b;
    }
</style>

<div class="page-content">
    <div class="page-header-flex"
        style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <div>
            <h2 style="margin: 0; font-size: 1.5rem; color: #1e293b; font-weight: 700;">Monthly Matrix Report</h2>
            <p style="color: #64748b; margin-top: 4px;">Matrix view of attendance for
                <?= date('F Y', strtotime($start_date)) ?>
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="admin_reports.php" class="btn-secondary"
                style="text-decoration: none; padding: 0.6rem 1.2rem; border-radius: 10px; display: flex; align-items: center; gap: 8px; background: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.85rem;">
                <i data-lucide="arrow-left" style="width: 16px;"></i> Back
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn-primary"
                style="text-decoration: none; padding: 0.6rem 1.2rem; border-radius: 10px; display: flex; align-items: center; gap: 8px; background: #0f172a; color: white; font-weight: 600; font-size: 0.85rem;">
                <i data-lucide="download" style="width: 16px;"></i> Export CSV
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="card" style="padding: 1.25rem; margin-bottom: 1.5rem; border: 1px solid #f1f5f9;">
        <form method="GET" style="display: flex; gap: 1.25rem; align-items: end; flex-wrap: wrap;">
            <div class="form-group">
                <label
                    style="display: block; font-size: 0.75rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Month</label>
                <select name="month" class="form-control"
                    style="padding: 0.5rem; border-radius: 8px; border: 1px solid #e2e8f0; min-width: 140px;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= sprintf('%02d', $m) ?>" <?= $month == sprintf('%02d', $m) ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="form-group">
                <label
                    style="display: block; font-size: 0.75rem; font-weight: 600; color: #64748b; margin-bottom: 6px;">Year</label>
                <select name="year" class="form-control"
                    style="padding: 0.5rem; border-radius: 8px; border: 1px solid #e2e8f0; min-width: 100px;">
                    <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                        <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>>
                            <?= $y ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>
            <button type="submit" class="btn-primary"
                style="height: 38px; padding: 0 1.5rem; border: none; border-radius: 8px; background: #6366f1; color: white; font-weight: 600; cursor: pointer;">
                View Report
            </button>
        </form>
    </div>

    <div class="matrix-table-wrapper">
        <table class="matrix-table">
            <thead>
                <tr>
                    <th class="emp-col">Employee Name</th>
                    <?php for ($d = 1; $d <= $num_days; $d++): ?>
                        <th>
                            <?= $d ?>
                        </th>
                    <?php endfor; ?>
                    <th style="background: #f0fdf4; color: #166534;">Pres.</th>
                    <th style="background: #fffbeb; color: #92400e;">Late</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                    <?php
                    $present_count = 0;
                    $late_count = 0;
                    ?>
                    <tr>
                        <td class="emp-col">
                            <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                            <div style="font-size: 0.65rem; color: #94a3b8; font-weight: 400;">
                                <?= $emp['employee_code'] ?>
                            </div>
                        </td>
                        <?php for ($d = 1; $d <= $num_days; $d++): ?>
                            <td>
                                <?php if (isset($matrix[$emp['id']][$d])): ?>
                                    <?php
                                    $log = $matrix[$emp['id']][$d];
                                    $present_count++;
                                    if ($log['status'] === 'Late')
                                        $late_count++;
                                    $statusClass = ($log['status'] === 'Late') ? 'status-late' : 'status-present';
                                    ?>
                                    <div class="<?= $statusClass ?>">
                                        <span class="time-val">
                                            <?= $log['clock_in'] ? date('H:i', strtotime($log['clock_in'])) : '--' ?>
                                        </span>
                                        <span class="time-val">
                                            <?= $log['clock_out'] ? date('H:i', strtotime($log['clock_out'])) : '--' ?>
                                        </span>
                                    </div>
                                <?php else: ?>
                                    <span class="status-a">A</span>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td style="font-weight: 700; background: #f0fdf4;">
                            <?= $present_count ?>
                        </td>
                        <td style="font-weight: 700; background: #fffbeb;">
                            <?= $late_count ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>