<?php
require_once 'config/db.php';

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$filter_date = $_GET['date'] ?? date('Y-m-d');

// 1. Fetch Company Info (Hardcoded as per sample)
$company_name = "WISH LUV BUILDCON PVT LTD";
$location = "PATNA";

// 2. Fetch All Employees
$sql_employees = "SELECT e.id, e.employee_code, e.first_name, e.last_name, d.name as dept_name 
                  FROM employees e 
                  LEFT JOIN departments d ON e.department_id = d.id 
                  ORDER BY e.first_name ASC";
$employees = $conn->query($sql_employees)->fetchAll();

// 3. Fetch Attendance for the date
$sql_attendance = "SELECT * FROM attendance WHERE date = :date";
$stmt = $conn->prepare($sql_attendance);
$stmt->execute(['date' => $filter_date]);
$attendance_data = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

// 4. Fetch Approved Leaves for the date
$sql_leaves = "SELECT employee_id, leave_type FROM leave_requests 
               WHERE :date BETWEEN start_date AND end_date 
               AND admin_status = 'Approved'";
$stmt = $conn->prepare($sql_leaves);
$stmt->execute(['date' => $filter_date]);
$leave_data = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);

// Helper function to format duration
function formatDuration($total_minutes)
{
    if (!$total_minutes || $total_minutes == 0)
        return '0:00';
    $hours = floor($total_minutes / 60);
    $minutes = $total_minutes % 60;
    return $hours . ":" . str_pad($minutes, 2, '0', STR_PAD_LEFT);
}

// Calculate Stats
$stats = [
    'present' => 0,
    'absent' => 0,
    'late' => 0,
    'on_leave' => 0,
    'total' => count($employees)
];

$report_rows = [];
foreach ($employees as $emp) {
    $emp_id = $emp['id'];
    $status = 'Absent';
    $in_time = '---';
    $out_time = '---';
    $work_hrs = '0:00';
    $ot_hrs = '0:00';
    $remarks = '';
    $status_label = 'ABSENT';
    $status_class = 'status-absent';

    if (isset($attendance_data[$emp_id])) {
        $att = $attendance_data[$emp_id];
        $status = $att['status'];
        $in_time = $att['clock_in'] ? date('H:i', strtotime($att['clock_in'])) : '---';
        $out_time = $att['clock_out'] ? date('H:i', strtotime($att['clock_out'])) : '---';
        $work_hrs = formatDuration($att['total_hours']);

        $stats['present']++;
        if ($status === 'Late') {
            $stats['late']++;
            // Calculate late minutes for the label
            $late_min = round((strtotime($att['clock_in']) - strtotime('10:00:00')) / 60);
            $status_label = "LATE " . ($late_min > 0 ? $late_min . " MIN" : "");
            $status_class = 'status-late';
        } else {
            $status_label = 'ON TIME';
            $status_class = 'status-ontime';
        }
    } elseif (isset($leave_data[$emp_id])) {
        $status = 'Leave';
        $stats['on_leave']++;
        $status_label = 'ON LEAVE';
        $status_class = 'status-leave';
        $remarks = $leave_data[$emp_id]['leave_type'];
    } else {
        $stats['absent']++;
    }

    $report_rows[] = [
        'code' => $emp['employee_code'],
        'name' => strtoupper($emp['first_name'] . ' ' . $emp['last_name']),
        'in_time' => $in_time,
        'out_time' => $out_time,
        'work_hrs' => $work_hrs,
        'ot_hrs' => $ot_hrs,
        'shift' => 'General',
        'shift_hrs' => '8:30',
        'status_label' => $status_label,
        'status_class' => $status_class,
        'remarks' => $remarks
    ];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Report -
        <?= $filter_date ?>
    </title>
    <style>
        :root {
            --primary-color: #0891b2;
            --border-color: #e2e8f0;
            --text-main: #1e293b;
            --text-muted: #64748b;
        }

        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            color: var(--text-main);
            font-size: 11px;
            background: #fff;
        }

        .report-wrapper {
            max-width: 1000px;
            margin: 0 auto;
            border: 1px solid #cbd5e1;
            padding: 15px;
        }

        .header {
            text-align: center;
            background: #0891b2;
            color: white;
            padding: 12px;
            margin: -15px -15px 15px -15px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            text-transform: uppercase;
        }

        .header p {
            margin: 5px 0 0 0;
            font-size: 12px;
            opacity: 0.9;
        }

        .company-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #0891b2;
        }

        .company-info .name {
            font-weight: bold;
            font-size: 14px;
            color: #0891b2;
        }

        .stats-row {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-card {
            flex: 1;
            text-align: center;
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
        }

        .stat-card.present {
            border-top: 4px solid #0891b2;
        }

        .stat-card.absent {
            border-top: 4px solid #ef4444;
        }

        .stat-card.late {
            border-top: 4px solid #f59e0b;
        }

        .stat-card.leave {
            border-top: 4px solid #a855f7;
        }

        .stat-card.total {
            border-top: 4px solid #0891b2;
        }

        .stat-label {
            display: block;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--text-muted);
            margin-bottom: 4px;
        }

        .stat-value {
            font-size: 18px;
            font-weight: 800;
            color: #1e293b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th {
            background: #0891b2;
            color: white;
            text-align: left;
            padding: 8px 6px;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 9px;
        }

        td {
            padding: 8px 6px;
            border-bottom: 1px solid var(--border-color);
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 8px;
            color: white;
            text-transform: uppercase;
        }

        .status-ontime {
            background: #10b981;
        }

        .status-late {
            background: #f59e0b;
        }

        .status-absent {
            background: #ef4444;
        }

        .status-leave {
            background: #a855f7;
        }

        .footer-note {
            text-align: center;
            font-size: 9px;
            color: var(--text-muted);
            margin-top: 30px;
            border-top: 1px solid var(--border-color);
            padding-top: 10px;
        }

        @media print {
            .no-print {
                display: none;
            }

            .report-wrapper {
                border: none;
            }
        }
    </style>
</head>

<body>

    <div class="no-print"
        style="background: #f1f5f9; padding: 10px; text-align: center; border-bottom: 1px solid #cbd5e1;">
        <button onclick="window.print()"
            style="padding: 8px 20px; background: #0891b2; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: 600;">Print
            / Save as PDF</button>
    </div>

    <div class="report-wrapper">
        <div class="header">
            <h1>Date wise Daily Attendance Report (Summary)</h1>
            <p>On Dated:
                <?= date('d/m/Y', strtotime($filter_date)) ?>
            </p>
        </div>

        <div class="company-info">
            <div>
                <div class="name">
                    <?= $company_name ?>
                </div>
                <div>Location:
                    <?= $location ?>
                </div>
            </div>
            <div style="font-weight: 700;">Date:
                <?= date('d/m/Y', strtotime($filter_date)) ?>
            </div>
        </div>

        <div class="stats-row">
            <div class="stat-card present">
                <span class="stat-label">Present</span>
                <div class="stat-value">
                    <?= $stats['present'] ?>
                </div>
            </div>
            <div class="stat-card absent">
                <span class="stat-label">Absent</span>
                <div class="stat-value">
                    <?= $stats['absent'] ?>
                </div>
            </div>
            <div class="stat-card late">
                <span class="stat-label">Late</span>
                <div class="stat-value">
                    <?= $stats['late'] ?>
                </div>
            </div>
            <div class="stat-card leave">
                <span class="stat-label">On Leave</span>
                <div class="stat-value">
                    <?= $stats['on_leave'] ?>
                </div>
            </div>
            <div class="stat-card total">
                <span class="stat-label">Total</span>
                <div class="stat-value">
                    <?= $stats['total'] ?>
                </div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">S.No</th>
                    <th style="width: 60px;">Emp Code</th>
                    <th>Emp Name</th>
                    <th style="width: 60px;">In Time</th>
                    <th style="width: 60px;">Out Time</th>
                    <th style="width: 60px;">Work Hrs</th>
                    <th style="width: 40px;">OT Hrs</th>
                    <th style="width: 60px;">Shift</th>
                    <th style="width: 60px;">Shift Hrs</th>
                    <th style="width: 100px;">Work Status</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($report_rows as $index => $row): ?>
                    <tr>
                        <td>
                            <?= $index + 1 ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['code']) ?>
                        </td>
                        <td style="font-weight: 700;">
                            <?= htmlspecialchars($row['name']) ?>
                        </td>
                        <td>
                            <?= $row['in_time'] ?>
                        </td>
                        <td>
                            <?= $row['out_time'] ?>
                        </td>
                        <td>
                            <?= $row['work_hrs'] ?>
                        </td>
                        <td>
                            <?= $row['ot_hrs'] ?>
                        </td>
                        <td>
                            <?= $row['shift'] ?>
                        </td>
                        <td>
                            <?= $row['shift_hrs'] ?>
                        </td>
                        <td>
                            <span class="status-badge <?= $row['status_class'] ?>">
                                <?= $row['status_label'] ?>
                            </span>
                        </td>
                        <td style="font-size: 9px; color: #64748b;">
                            <?= htmlspecialchars($row['remarks']) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="footer-note">
            * Work Hrs is including Over Time | Generated By: OnTime (Secureye) | Printed On:
            <?= date('d/m/Y H:i') ?>
        </div>
    </div>

    <script>
        // Automatically trigger print on load
        window.onload = function () {
            // Uncomment the line below to auto-open print dialog
            // window.print();
        };
    </script>

</body>

</html>