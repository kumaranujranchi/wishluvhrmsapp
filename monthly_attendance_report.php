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

// 4. Fetch Holidays
$holiday_map = [];
$hol_sql = "SELECT title, start_date, end_date FROM holidays 
            WHERE is_active = 1 
            AND (
                (start_date BETWEEN :start AND :end) 
                OR (end_date BETWEEN :start AND :end)
                OR (start_date <= :start AND end_date >= :end)
            )";
$hol_stmt = $conn->prepare($hol_sql);
$hol_stmt->execute(['start' => $start_date, 'end' => $end_date]);
$holidays = $hol_stmt->fetchAll();

foreach ($holidays as $h) {
    $curr = strtotime($h['start_date']);
    $last = strtotime($h['end_date']);

    while ($curr <= $last) {
        $d_str = date('Y-m-d', $curr);
        if ($d_str >= $start_date && $d_str <= $end_date) {
            $holiday_map[$d_str] = 'H';
        }
        $curr = strtotime('+1 day', $curr);
    }
}

// 5. Fetch Approved Leaves
$leave_map = [];
$leave_sql = "SELECT employee_id, start_date, end_date FROM leave_requests 
              WHERE admin_status = 'Approved' 
              AND (
                  (start_date BETWEEN :start AND :end) 
                  OR (end_date BETWEEN :start AND :end)
                  OR (start_date <= :start AND end_date >= :end)
              )";
$leave_stmt = $conn->prepare($leave_sql);
$leave_stmt->execute(['start' => $start_date, 'end' => $end_date]);
$leaves = $leave_stmt->fetchAll();

foreach ($leaves as $l) {
    $curr = strtotime($l['start_date']);
    $last = strtotime($l['end_date']);

    while ($curr <= $last) {
        $d_str = date('Y-m-d', $curr);
        if ($d_str >= $start_date && $d_str <= $end_date) {
            $leave_map[$l['employee_id']][$d_str] = 'L';
        }
        $curr = strtotime('+1 day', $curr);
    }
}

// 6. Pivot Data: [emp_id][day] = log
$matrix = [];
foreach ($logs as $log) {
    $day = (int) date('j', strtotime($log['date']));
    $matrix[$log['emp_id']][$day] = $log;
}

// 7. Handle CSV Export
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
            $current_date = "$year-$month-" . sprintf('%02d', $d);
            $is_tuesday = (date('l', strtotime($current_date)) === 'Tuesday');

            if (isset($matrix[$emp['id']][$d])) {
                // Present
                $log = $matrix[$emp['id']][$d];
                $in = $log['clock_in'] ? date('H:i', strtotime($log['clock_in'])) : '-';
                $out = $log['clock_out'] ? date('H:i', strtotime($log['clock_out'])) : '-';
                $row[] = "$in\n$out";
                $present++;
                if ($log['status'] === 'Late')
                    $late++;
            } elseif (isset($holiday_map[$current_date])) {
                // Holiday
                $row[] = 'H';
            } elseif (isset($leave_map[$emp['id']][$current_date])) {
                // Leave
                $row[] = 'L';
            } elseif ($is_tuesday) {
                // Weekly Off
                $row[] = 'W/O';
            } else {
                // Absent
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
    .matrix-container {
        padding: 2rem;
        background: #f8fafc;
        min-height: 100vh;
    }

    .report-heading-section {
        text-align: center;
        margin-bottom: 2rem;
    }

    .report-title {
        font-size: 1.4rem;
        font-weight: 800;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 4px;
    }

    .report-subtitle {
        color: #64748b;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .matrix-table-wrapper {
        overflow-x: auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px -5px rgba(0, 0, 0, 0.1);
        border: 1px solid #e2e8f0;
    }

    .matrix-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.7rem;
    }

    .matrix-table th,
    .matrix-table td {
        border: 1px solid #e2e8f0;
        padding: 6px 2px;
        text-align: center;
        min-width: 55px;
    }

    .matrix-table th {
        background: #f1f5f9;
        font-weight: 700;
        color: #334155;
        position: sticky;
        top: 0;
        z-index: 10;
        padding: 10px 4px;
    }

    .matrix-table .emp-col {
        position: sticky;
        left: 0;
        background: #f8fafc;
        z-index: 20;
        text-align: left;
        min-width: 160px;
        padding-left: 12px;
        border-right: 2px solid #e2e8f0;
    }

    .matrix-table td.emp-col {
        background: white;
        font-weight: 600;
        color: #1e293b;
    }

    .punch-cell {
        display: flex;
        flex-direction: column;
        gap: 2px;
        line-height: 1;
        justify-content: center;
        height: 100%;
    }

    .time-val {
        display: block;
        font-weight: 500;
    }

    .time-in {
        color: #059669;
    }

    .time-out {
        color: #2563eb;
    }

    .status-a {
        color: #ef4444;
        font-weight: 800;
        font-size: 0.8rem;
    }

    .status-wo {
        color: #64748b;
        font-weight: 800;
        font-size: 0.75rem;
        background: #f1f5f9;
        display: block;
        padding: 4px 0;
    }

    .status-h {
        color: #7c3aed;
        /* Violet */
        font-weight: 800;
        font-size: 0.8rem;
        background: #f5f3ff;
        display: block;
        padding: 4px 0;
    }

    .status-l {
        color: #ea580c;
        /* Orange */
        font-weight: 800;
        font-size: 0.8rem;
        background: #fff7ed;
        display: block;
        padding: 4px 0;
    }

    .summary-col {
        font-weight: 700;
        background: #f8fafc;
    }

    .no-print {
        background: white;
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        border: 1px solid #e2e8f0;
    }
</style>

<div class="matrix-container">
    <div class="no-print" style="display: flex; justify-content: space-between; align-items: center;">
        <div style="display: flex; gap: 1rem; align-items: end;">
            <form method="GET" style="display: flex; gap: 0.75rem;">
                <div class="form-group">
                    <label
                        style="display: block; font-size: 0.7rem; font-weight: 700; color: #64748b; margin-bottom: 4px;">MONTH</label>
                    <select name="month"
                        style="padding: 0.5rem; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.85rem;">
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= sprintf('%02d', $m) ?>" <?= $month == sprintf('%02d', $m) ? 'selected' : '' ?>>
                                <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label
                        style="display: block; font-size: 0.7rem; font-weight: 700; color: #64748b; margin-bottom: 4px;">YEAR</label>
                    <select name="year"
                        style="padding: 0.5rem; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 0.85rem;">
                        <?php for ($y = date('Y'); $y >= date('Y') - 2; $y--): ?>
                            <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display: block; font-size: 0.7rem; margin-bottom: 4px; opacity: 0;">ACTION</label>
                    <button type="submit"
                        style="background: #6366f1; color: white; border: none; padding: 0.5rem 1.25rem; border-radius: 6px; font-weight: 600; cursor: pointer; height: 35px; display: flex; align-items: center;">
                        Apply
                    </button>
                </div>
            </form>
        </div>
        <div style="display: flex; gap: 0.5rem;">
            <a href="admin_reports.php"
                style="text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; background: #f1f5f9; color: #475569; font-weight: 600; font-size: 0.85rem; border: 1px solid #e2e8f0;">
                Back
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>"
                style="text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; background: #0f172a; color: white; font-weight: 600; font-size: 0.85rem; display: flex; align-items: center; gap: 6px;">
                <i data-lucide="download" style="width: 14px;"></i> CSV Export
            </a>
            <button onclick="window.print()"
                style="padding: 0.5rem 1rem; border-radius: 6px; background: #059669; color: white; font-weight: 600; font-size: 0.85rem; border: none; cursor: pointer;">
                Print PDF
            </button>
        </div>
    </div>

    <div class="report-heading-section">
        <h1 class="report-title">Monthly Attendance Report with (In\Out) Time</h1>
        <div class="report-subtitle">For Period: 01/<?= $month ?>/<?= $year ?> To
            <?= $num_days ?>/<?= $month ?>/<?= $year ?>
        </div>
        <div style="font-weight: 700; color: #1e293b; margin-top: 8px;">Company Name : WISHLUV BUILDCON PVT LTD</div>
        <div style="font-size: 0.85rem; color: #475569;">Location : PATNA</div>
    </div>

    <div class="matrix-table-wrapper">
        <table class="matrix-table">
            <thead>
                <tr>
                    <th class="emp-col" style="min-width: 50px;">Code</th>
                    <th class="emp-col" style="left: 62px; min-width: 150px;">Emp Name</th>
                    <?php for ($d = 1; $d <= $num_days; $d++): ?>
                        <th><?= $d ?></th>
                    <?php endfor; ?>
                    <th class="summary-col">Pres.</th>
                    <th class="summary-col">Late</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($employees as $emp): ?>
                    <?php
                    $present_count = 0;
                    $late_count = 0;
                    ?>
                    <tr>
                        <td class="emp-col" style="min-width: 50px;">
                            <?= $emp['employee_code'] ?>
                        </td>
                        <td class="emp-col" style="left: 62px; min-width: 150px;">
                            <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                        </td>
                        <?php for ($d = 1; $d <= $num_days; $d++): ?>
                            <?php
                            $current_date = "$year-$month-" . sprintf('%02d', $d);
                            $is_tuesday = (date('l', strtotime($current_date)) === 'Tuesday');
                            ?>
                            <td>
                                <?php if (isset($matrix[$emp['id']][$d])): ?>
                                    <?php
                                    $log = $matrix[$emp['id']][$d];
                                    $present_count++;
                                    if ($log['status'] === 'Late')
                                        $late_count++;
                                    ?>
                                    <div class="punch-cell">
                                        <span
                                            class="time-val time-in"><?= $log['clock_in'] ? date('H:i', strtotime($log['clock_in'])) : '---' ?></span>
                                        <span
                                            class="time-val time-out"><?= $log['clock_out'] ? date('H:i', strtotime($log['clock_out'])) : '---' ?></span>
                                    </div>
                                <?php elseif (isset($holiday_map[$current_date])): ?>
                                    <span class="status-h">H</span>
                                <?php elseif (isset($leave_map[$emp['id']][$current_date])): ?>
                                    <span class="status-l">L</span>
                                <?php elseif ($is_tuesday): ?>
                                    <span class="status-wo">W/O</span>
                                <?php else: ?>
                                    <span class="status-a">A</span>
                                <?php endif; ?>
                            </td>
                        <?php endfor; ?>
                        <td class="summary-col" style="color: #059669;"><?= $present_count ?></td>
                        <td class="summary-col" style="color: #d97706;"><?= $late_count ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<?php include 'includes/footer.php'; ?>