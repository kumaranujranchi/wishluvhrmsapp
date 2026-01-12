<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$message = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $leave_type = $_POST['leave_type'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $reason = trim($_POST['reason']);

    if (empty($start_date) || empty($end_date) || empty($leave_type)) {
        $message = "<div class='alert error'>Please fill all required fields.</div>";
    } else {
        // Basic date validation
        if (strtotime($end_date) < strtotime($start_date)) {
            $message = "<div class='alert error'>End Date cannot be before Start Date.</div>";
        } else {
            $sql = "INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, reason) 
                     VALUES (:uid, :type, :start, :end, :reason)";
            try {
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    'uid' => $user_id,
                    'type' => $leave_type,
                    'start' => $start_date,
                    'end' => $end_date,
                    'reason' => $reason
                ]);
                $message = "<div class='alert success'>Leave Application Submitted Successfully!</div>";

                // --- NOTIFICATIONS ---
                require_once 'config/email.php';
                // 1. Get Employee & Manager Info
                $empStmt = $conn->prepare("SELECT e.first_name, e.last_name, e.email, e.employee_code, m.email as manager_email, m.first_name as manager_name 
                                           FROM employees e 
                                           LEFT JOIN employees m ON e.reporting_manager_id = m.id 
                                           WHERE e.id = :uid");
                $empStmt->execute(['uid' => $user_id]);
                $empData = $empStmt->fetch();

                if ($empData) {
                    $empName = $empData['first_name'] . ' ' . $empData['last_name'];
                    $leaveInfo = "
                        <ul>
                            <li><strong>Type:</strong> $leave_type</li>
                            <li><strong>Date:</strong> " . date('d M', strtotime($start_date)) . " to " . date('d M Y', strtotime($end_date)) . "</li>
                            <li><strong>Reason:</strong> " . htmlspecialchars($reason) . "</li>
                        </ul>
                     ";

                    // A. Notify Employee
                    if (!empty($empData['email'])) {
                        $body = getHtmlEmailTemplate(
                            "Leave Request Received",
                            "<p>Dear $empName,</p><p>Your leave request has been submitted successfully and is pending approval.</p>$leaveInfo"
                        );
                        sendEmail($empData['email'], "Leave Request Submitted", $body);
                    }

                    // B. Notify Manager
                    if (!empty($empData['manager_email'])) {
                        $body = getHtmlEmailTemplate(
                            "Team Leave Request",
                            "<p>Hello {$empData['manager_name']},</p><p><strong>$empName</strong> ({$empData['employee_code']}) has applied for leave.</p>$leaveInfo",
                            "https://wishluvbuildcon.com/hrms/leave_manager_approval.php",
                            "Review Request"
                        );
                        sendEmail($empData['manager_email'], "Leave Request: $empName", $body);
                    }

                    // C. Notify Admin (e.g. fixed email or fetch)
                    // Assuming admin@wishluv.com or finding active Admins
                    $admins = $conn->query("SELECT email FROM employees WHERE role = 'Admin'")->fetchAll();
                    foreach ($admins as $admin) {
                        if (!empty($admin['email'])) {
                            $body = getHtmlEmailTemplate(
                                "New Leave Application",
                                "<p><strong>$empName</strong> has applied for leave.</p>$leaveInfo",
                                "https://wishluvbuildcon.com/hrms/leave_admin.php",
                                "Manage Leaves"
                            );
                            sendEmail($admin['email'], "Leave App: $empName", $body);
                        }
                    }
                }
            } catch (PDOException $e) {
                $message = "<div class='alert error'>Error: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// 1. Calculate Leave Stats (Annual)
$current_year = date('Y');
$total_leaves_annual = 24;

$stats_sql = "SELECT start_date, end_date FROM leave_requests 
              WHERE employee_id = :uid AND status = 'Approved' AND YEAR(start_date) = :year";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->execute(['uid' => $user_id, 'year' => $current_year]);
$approved_leaves = $stats_stmt->fetchAll();

$leaves_taken = 0;
foreach ($approved_leaves as $l) {
    $d1 = new DateTime($l['start_date']);
    $d2 = new DateTime($l['end_date']);
    $diff = $d2->diff($d1)->format("%a") + 1;
    $leaves_taken += $diff;
}
$leave_balance = $total_leaves_annual - $leaves_taken;


// 2. Fetch My Leaves (History with Filter)
$filter_month = $_GET['month'] ?? '';
$filter_year = $_GET['year'] ?? '';

$sql = "SELECT * FROM leave_requests WHERE employee_id = :uid";
$params = ['uid' => $user_id];

if ($filter_month) {
    $sql .= " AND MONTH(start_date) = :m";
    $params['m'] = $filter_month;
}
if ($filter_year) {
    $sql .= " AND YEAR(start_date) = :y";
    $params['y'] = $filter_year;
}

$sql .= " ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$leaves = $stmt->fetchAll();
?>

<style>
    .leave-dashboard {
        display: grid;
        grid-template-columns: 350px 1fr;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .leave-dashboard {
            grid-template-columns: 1fr;
        }
    }

    .leave-form-card {
        background: white;
        border-radius: 1.5rem;
        padding: 2rem;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
        border: 1px solid #f1f5f9;
        position: sticky;
        top: 2rem;
    }

    .form-group label {
        font-weight: 500;
        color: #475569;
        margin-bottom: 0.5rem;
        display: block;
    }

    .form-control {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        width: 100%;
        transition: all 0.2s;
    }

    .form-control:focus {
        background: white;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        outline: none;
    }

    .duration-capsule {
        background: linear-gradient(135deg, #6366f1, #8b5cf6);
        color: white;
        padding: 1rem;
        border-radius: 1rem;
        margin-bottom: 1.5rem;
        display: none;
        /* Hidden by default */
        text-align: center;
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .history-card {
        background: white;
        border-radius: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        overflow: hidden;
    }

    .history-header {
        padding: 1.5rem;
        border-bottom: 1px solid #f1f5f9;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-modern {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-modern th {
        background: #f8fafc;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 1rem 1.5rem;
        text-align: left;
        border-bottom: 1px solid #e2e8f0;
    }

    .table-modern td {
        padding: 1.25rem 1.5rem;
        vertical-align: middle;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }

    .table-modern tr:last-child td {
        border-bottom: none;
    }

    .status-pill {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        margin-right: 0.5rem;
        margin-bottom: 0.25rem;
    }

    .approval-block {
        display: flex;
        flex-direction: column;
        gap: 4px;
        font-size: 0.8rem;
    }

    .approval-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 140px;
    }

    .approval-label {
        color: #94a3b8;
    }

    .approval-val {
        font-weight: 600;
    }
</style>

<div class="page-content">
    <?= $message ?>

    <!-- Leave Statistics Cards -->
    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem;">
        <div class="card stats-card">
            <div class="stats-icon-wrapper" style="background: #e0e7ff; color: #4f46e5;">
                <i data-lucide="calendar" style="width:24px; height:24px;"></i>
            </div>
            <div class="stats-info">
                <span class="stats-title">Total Annual Leaves</span>
                <span class="stats-value"><?= $total_leaves_annual ?></span>
            </div>
        </div>
        <div class="card stats-card">
            <div class="stats-icon-wrapper" style="background: #dcfce7; color: #166534;">
                <i data-lucide="check-circle" style="width:24px; height:24px;"></i>
            </div>
            <div class="stats-info">
                <span class="stats-title">Leaves Consumed</span>
                <span class="stats-value"><?= $leaves_taken ?></span>
            </div>
        </div>
        <div class="card stats-card">
            <div class="stats-icon-wrapper" style="background: #ffedd5; color: #9a3412;">
                <i data-lucide="pie-chart" style="width:24px; height:24px;"></i>
            </div>
            <div class="stats-info">
                <span class="stats-title">Leave Balance</span>
                <span class="stats-value"><?= $leave_balance ?></span>
            </div>
        </div>
    </div>

    <div class="leave-dashboard">
        <!-- Application Form -->
        <div class="leave-form-card">
            <!-- ... form content (untouched) ... -->
            <h3 style="margin: 0 0 1.5rem 0; color: #1e293b; display: flex; align-items: center; gap: 10px;">
                <span
                    style="width: 32px; height: 32px; background: #e0e7ff; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #4f46e5;">
                    <i data-lucide="send" style="width: 18px;"></i>
                </span>
                Apply for Leave
            </h3>

            <form method="POST">
                <!-- ... inputs ... -->
                <div class="form-group">
                    <label>Leave Type</label>
                    <div style="position: relative;">
                        <i data-lucide="layers"
                            style="position: absolute; left: 12px; top: 12px; width: 18px; color: #94a3b8;"></i>
                        <select name="leave_type" class="form-control" style="padding-left: 2.5rem;" required>
                            <option value="">Select Type</option>
                            <option value="Sick Leave">Sick Leave (SL)</option>
                            <option value="Half Day">Half Day</option>
                            <option value="Full Day">Full Day (CL)</option>
                            <option value="PL">Privilege Leave (PL)</option>
                            <option value="EL">Earned Leave (EL)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="start_date" id="startDate" class="form-control" required
                        onchange="calculateDuration()">
                </div>

                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="end_date" id="endDate" class="form-control" required
                        onchange="calculateDuration()">
                </div>

                <div id="durationDisplay" class="duration-capsule">
                    <span style="font-size: 0.85rem; opacity: 0.9;">Total Duration</span>
                    <div style="font-size: 1.5rem; font-weight: 700;" id="daysCount">0 Days</div>
                </div>

                <div class="form-group">
                    <label>Reason</label>
                    <textarea name="reason" class="form-control" rows="4" placeholder="Mention the reason..."
                        required></textarea>
                </div>

                <button type="submit" class="btn-primary"
                    style="width: 100%; justify-content: center; font-size: 1rem; padding: 0.875rem;">
                    Submit Application
                </button>
            </form>
        </div>

        <!-- Leave History -->
        <div class="history-card">
            <div class="history-header">
                <div>
                    <h3 style="margin: 0;">Leave History</h3>
                    <span style="font-size: 0.85rem; color: #64748b;">Track your requests</span>
                </div>
                <!-- Filter Form -->
                <form method="GET" style="display: flex; gap: 10px; align-items: center;">
                    <select name="month" class="form-control"
                        style="width: auto; padding: 0.5rem 2rem 0.5rem 1rem; font-size: 0.85rem;">
                        <option value="">All Months</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= ($filter_month == $m) ? 'selected' : '' ?>>
                                <?= date('M', mktime(0, 0, 0, $m, 1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <select name="year" class="form-control"
                        style="width: auto; padding: 0.5rem 2rem 0.5rem 1rem; font-size: 0.85rem;">
                        <option value="">All Years</option>
                        <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                            <option value="<?= $y ?>" <?= ($filter_year == $y) ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                    <button type="submit" class="btn-primary"
                        style="padding: 0.5rem 1rem; font-size: 0.85rem;">Filter</button>
                </form>
            </div>
            <!-- Desktop View -->
            <div class="desktop-only">
                <div class="table-responsive">
                    <table class="table table-modern">
                        <thead>
                            <tr>
                                <th width="15%">Applied On</th>
                                <th width="15%">Type</th>
                                <th width="25%">Dates</th>
                                <th width="25%">Approvals</th>
                                <th width="20%" style="text-align:right;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaves as $leave): ?>
                                <tr>
                                    <td style="color: #64748b; font-size: 0.9rem;">
                                        <?= date('d M Y', strtotime($leave['created_at'])) ?>
                                    </td>
                                    <td>
                                        <span style="font-weight: 600; color: #1e293b;"><?= $leave['leave_type'] ?></span>
                                    </td>
                                    <td>
                                        <div style="font-weight: 500; font-size: 0.95rem; color:#0f172a;">
                                            <?= date('d M', strtotime($leave['start_date'])) ?> -
                                            <?= date('d M', strtotime($leave['end_date'])) ?>
                                        </div>
                                        <?php
                                        $d1 = new DateTime($leave['start_date']);
                                        $d2 = new DateTime($leave['end_date']);
                                        $diff = $d2->diff($d1)->format("%a") + 1;
                                        ?>
                                        <div style="font-size: 0.75rem; color: #64748b; margin-top:2px;"><?= $diff ?> Days
                                        </div>
                                    </td>
                                    <td>
                                        <div class="approval-block">
                                            <div class="approval-row">
                                                <span class="approval-label">Manager</span>
                                                <?php
                                                $mColor = match ($leave['manager_status']) {
                                                    'Approved' => '#10b981', 'Rejected' => '#ef4444', default => '#f59e0b'
                                                };
                                                ?>
                                                <span class="approval-val"
                                                    style="color: <?= $mColor ?>;"><?= $leave['manager_status'] ?></span>
                                            </div>
                                            <div class="approval-row">
                                                <span class="approval-label">Admin</span>
                                                <?php
                                                $aColor = match ($leave['admin_status']) {
                                                    'Approved' => '#10b981', 'Rejected' => '#ef4444', default => '#f59e0b'
                                                };
                                                ?>
                                                <span class="approval-val"
                                                    style="color: <?= $aColor ?>;"><?= $leave['admin_status'] ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align:right;">
                                        <?php
                                        $badgeBg = match ($leave['status']) {
                                            'Approved' => '#dcfce7', 'Rejected' => '#fee2e2', 'Pending' => '#fef9c3', default => '#f1f5f9'
                                        };
                                        $badgeColor = match ($leave['status']) {
                                            'Approved' => '#166534', 'Rejected' => '#991b1b', 'Pending' => '#854d0e', default => '#64748b'
                                        };
                                        ?>
                                        <span class="badge"
                                            style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; border-radius: 6px; padding: 0.35rem 0.75rem; font-size:0.8rem;">
                                            <?= $leave['status'] ?: 'Pending' ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Mobile View -->
            <div class="mobile-only">
                <div class="mobile-card-list">
                    <?php foreach ($leaves as $leave): ?>
                        <div class="mobile-card">
                            <div class="mobile-card-header" onclick="this.parentElement.classList.toggle('expanded')">
                                <div style="display: flex; flex-direction: column;">
                                    <div style="font-weight: 600; color: #1e293b;"><?= $leave['leave_type'] ?></div>
                                    <div style="font-size: 0.75rem; color: #64748b;">
                                        <?= date('d M', strtotime($leave['start_date'])) ?> -
                                        <?= date('d M', strtotime($leave['end_date'])) ?>
                                    </div>
                                </div>
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <?php
                                    $badgeBg = match ($leave['status']) {
                                        'Approved' => '#dcfce7', 'Rejected' => '#fee2e2', 'Pending' => '#fef9c3', default => '#f1f5f9'
                                    };
                                    $badgeColor = match ($leave['status']) {
                                        'Approved' => '#166534', 'Rejected' => '#991b1b', 'Pending' => '#854d0e', default => '#64748b'
                                    };
                                    ?>
                                    <span class="badge"
                                        style="background: <?= $badgeBg ?>; color: <?= $badgeColor ?>; border-radius: 4px; padding: 0.2rem 0.5rem; font-size:0.7rem;">
                                        <?= $leave['status'] ?: 'Pending' ?>
                                    </span>
                                    <i data-lucide="chevron-down" class="toggle-icon" style="width: 16px;"></i>
                                </div>
                            </div>
                            <div class="mobile-card-body">
                                <div class="mobile-field">
                                    <span class="mobile-label">Applied On</span>
                                    <span class="mobile-value"><?= date('d M Y', strtotime($leave['created_at'])) ?></span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Reason</span>
                                    <span
                                        class="mobile-value"><?= htmlspecialchars($leave['reason'] ?: 'No reason provided') ?></span>
                                </div>
                                <div class="mobile-field">
                                    <span class="mobile-label">Approvals</span>
                                    <div style="display: flex; flex-direction: column; gap: 5px; margin-top: 5px;">
                                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                                            <span style="color: #64748b;">Manager:</span>
                                            <span
                                                style="font-weight: 600; color: <?= match ($leave['manager_status']) { 'Approved' => '#10b981', 'Rejected' => '#ef4444', default => '#f59e0b'} ?>;"><?= $leave['manager_status'] ?></span>
                                        </div>
                                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem;">
                                            <span style="color: #64748b;">Admin:</span>
                                            <span
                                                style="font-weight: 600; color: <?= match ($leave['admin_status']) { 'Approved' => '#10b981', 'Rejected' => '#ef4444', default => '#f59e0b'} ?>;"><?= $leave['admin_status'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    function calculateDuration() {
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        const display = document.getElementById('durationDisplay');
        const countSpan = document.getElementById('daysCount');

        if (start && end) {
            const d1 = new Date(start);
            const d2 = new Date(end);

            // Calculate difference in milliseconds
            const diffTime = Math.abs(d2 - d1);
            // Convert to days (add 1 to include start date)
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24)) + 1;

            if (d2 >= d1) {
                countSpan.innerText = diffDays + (diffDays === 1 ? ' Day' : ' Days');
                display.style.display = 'block';
            } else {
                display.style.display = 'none';
            }
        } else {
            display.style.display = 'none';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>