<?php
require_once 'config/db.php';
require_once 'config/ai_config.php'; // Load environment variables from .env
include 'includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Ensure only employees can punch
if ($_SESSION['user_role'] === 'Admin') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$date = date('Y-m-d');
$message = "";

// Fetch Assigned Locations for this employee
$loc_stmt = $conn->prepare("
    SELECT al.* 
    FROM attendance_locations al
    JOIN employee_locations el ON al.id = el.location_id
    WHERE el.employee_id = :uid AND al.is_active = 1
");
$loc_stmt->execute(['uid' => $user_id]);
$assigned_locations = $loc_stmt->fetchAll(PDO::FETCH_ASSOC);

// 1. Handle POST Requests (Check In / Check Out)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $lat = $_POST['latitude'] ?? null;
    $lng = $_POST['longitude'] ?? null;
    $address = $_POST['address'] ?? 'Location not allocated';
    $location_id = $_POST['location_id'] ?? null;
    $out_of_range = $_POST['out_of_range'] ?? 0;
    $reason = $_POST['reason'] ?? null;

    if ($action === 'clock_in') {
        // Check if already checked in
        $check = $conn->prepare("SELECT * FROM attendance WHERE employee_id = :uid AND date = :date");
        $check->execute(['uid' => $user_id, 'date' => $date]);

        if ($check->rowCount() == 0) {
            $current_time = date('H:i:s');
            // Determine Status (Late if after 10:00 AM)
            // Fetch employee specific shift if available, else default
            $emp_q = $conn->prepare("SELECT shift_start_time FROM employees WHERE id = :uid");
            $emp_q->execute(['uid' => $user_id]);
            $shift_start = $emp_q->fetchColumn() ?: '10:00:00';

            $status = (strtotime($current_time) > strtotime($shift_start)) ? 'Late' : 'On Time';

            $sql = "INSERT INTO attendance (employee_id, date, clock_in, status, clock_in_lat, clock_in_lng, clock_in_address, location_id, out_of_range, out_of_range_reason) 
                    VALUES (:uid, :date, :time, :status, :lat, :lng, :addr, :loc_id, :oor, :reason)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'uid' => $user_id,
                'date' => $date,
                'time' => $current_time,
                'status' => $status,
                'lat' => $lat,
                'lng' => $lng,
                'addr' => $address,
                'loc_id' => $location_id,
                'oor' => $out_of_range,
                'reason' => $reason
            ]);
            $message = "<div class='alert success-glass'>Checked In Successfully at " . date('h:i A', strtotime($current_time)) . "</div>";

            // --- SEND EMAIL NOTIFICATION ---
            require_once 'config/email.php';
            // Fetch Employee Email
            $empStmt = $conn->prepare("SELECT first_name, last_name, email FROM employees WHERE id = :uid");
            $empStmt->execute(['uid' => $user_id]);
            $emp = $empStmt->fetch();

            if ($emp && !empty($emp['email'])) {
                $subject = "Attendance Confirmed: " . date('d M Y');
                $content = "
                    <p>Hello <strong>{$emp['first_name']}</strong>,</p>
                    <p>Your attendance for today has been successfully marked.</p>
                    <ul>
                        <li><strong>Date:</strong> " . date('d M Y') . "</li>
                        <li><strong>Time:</strong> " . date('h:i A', strtotime($current_time)) . "</li>
                        <li><strong>Status:</strong> <span style='color: " . ($status == 'Late' ? '#eab308' : '#16a34a') . "; font-weight: bold;'>{$status}</span></li>
                        <li><strong>Location:</strong> " . htmlspecialchars($address) . "</li>
                    </ul>
                    <p>Have a productive day!</p>
                ";
                $body = getHtmlEmailTemplate("Punch In Confirmation", $content);
                sendEmail($emp['email'], $subject, $body);
            }
        }
    } elseif ($action === 'clock_out') {
        // Find today's record
        $current_time = date('H:i:s');

        // Calculate total hours
        // First get clock_in time
        $q = $conn->prepare("SELECT clock_in FROM attendance WHERE employee_id = :uid AND date = :date");
        $q->execute(['uid' => $user_id, 'date' => $date]);
        $row = $q->fetch();

        if ($row) {
            $clock_in_time = strtotime($row['clock_in']);
            $clock_out_time = strtotime($current_time);
            // Calculate total minutes instead of decimal hours
            $total_minutes = round(abs($clock_out_time - $clock_in_time) / 60);
            $hours = floor($total_minutes / 60);
            $minutes = $total_minutes % 60;
            $hours_display = sprintf('%d:%02d', $hours, $minutes);

            $sql = "UPDATE attendance SET 
                    clock_out = :time, 
                    total_hours = :total_minutes,
                    clock_out_lat = :lat,
                    clock_out_lng = :lng,
                    clock_out_address = :addr,
                    location_id = COALESCE(:loc_id, location_id),
                    out_of_range = CASE WHEN out_of_range = 1 THEN 1 ELSE :oor END,
                    out_of_range_reason = CASE 
                        WHEN out_of_range = 1 AND :oor = 1 THEN CONCAT(out_of_range_reason, ' | Out on Exit: ', :reason)
                        WHEN out_of_range = 1 THEN out_of_range_reason
                        ELSE :reason 
                    END
                    WHERE employee_id = :uid AND date = :date";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'time' => $current_time,
                'total_minutes' => $total_minutes,
                'lat' => $lat,
                'lng' => $lng,
                'addr' => $address,
                'loc_id' => $location_id ?: null,
                'oor' => $out_of_range,
                'reason' => $reason,
                'uid' => $user_id,
                'date' => $date
            ]);
            $message = "<div class='alert success-glass'>Checked Out Successfully at " . date('h:i A', strtotime($current_time)) . ". Total Hours: $hours_display</div>";
        }
    }
}

// 2. Fetch Current Status
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = :uid AND date = :date");
$stmt->execute(['uid' => $user_id, 'date' => $date]);
$today_record = $stmt->fetch();

$has_checked_in = false;
$has_checked_out = false;

if ($today_record) {
    $has_checked_in = true;
    if (!empty($today_record['clock_out'])) {
        $has_checked_out = true;
    }
}

// 3. Filter History
$filter_month = $_GET['month'] ?? date('m');
$filter_year = $_GET['year'] ?? date('Y');

$hist_sql = "SELECT * FROM attendance 
             WHERE employee_id = :uid 
             AND MONTH(date) = :m AND YEAR(date) = :y 
             ORDER BY date DESC";
$hist_stmt = $conn->prepare($hist_sql);
$hist_stmt->execute(['uid' => $user_id, 'm' => $filter_month, 'y' => $filter_year]);
$history = $hist_stmt->fetchAll();

// Calculate Monthly Stats
$present_days = 0;
$late_days = 0;
$total_work_hours = 0;

foreach ($history as $h) {
    if ($h['status'] == 'Present' || $h['status'] == 'Late')
        $present_days++;
    if ($h['status'] == 'Late')
        $late_days++;
    $total_work_hours += $h['total_hours'];
}

// Helper function to format duration from minutes to hours:minutes
function formatDuration($total_minutes)
{
    if (!$total_minutes || $total_minutes == 0)
        return '-';
    $hours = floor($total_minutes / 60);
    $minutes = $total_minutes % 60;
    return sprintf('%d:%02d', $hours, $minutes);
}

?>

<style>
    /* Layout & Hero Card */
    .dashboard-container {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 2rem;
        align-items: start;
    }

    .punch-card {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        border-radius: 1.5rem;
        padding: 3rem;
        color: white;
        text-align: center;
        position: relative;
        overflow: hidden;
        box-shadow: 0 20px 40px -10px rgba(79, 70, 229, 0.5);
    }

    .punch-card::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 60%);
        pointer-events: none;
    }

    .digital-clock {
        font-size: 4rem;
        font-weight: 800;
        font-family: 'Outfit', monospace;
        letter-spacing: -2px;
        background: -webkit-linear-gradient(#fff, #e0e7ff);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
    }

    .custom-punch-btn {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 2px solid rgba(255, 255, 255, 0.4);
        border-radius: 50%;
        width: 180px;
        height: 180px;
        color: white;
        font-size: 1.25rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin: 2rem auto;
        box-shadow: 0 0 30px rgba(255, 255, 255, 0.1);
    }

    .custom-punch-btn:hover {
        transform: scale(1.05);
        background: rgba(255, 255, 255, 0.3);
        box-shadow: 0 0 50px rgba(255, 255, 255, 0.3);
    }

    .custom-punch-btn:active {
        transform: scale(0.95);
    }

    .btn-disabled {
        background: rgba(226, 232, 240, 0.1);
        color: rgba(255, 255, 255, 0.5);
        border-color: rgba(255, 255, 255, 0.1);
        cursor: not-allowed;
    }

    .status-capsule {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.15);
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.9rem;
        backdrop-filter: blur(5px);
    }

    /* Stats Cards */
    .mini-stat-card {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        text-align: center;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #f1f5f9;
        transition: transform 0.2s;
    }

    .mini-stat-card:hover {
        transform: translateY(-3px);
    }

    .mini-stat-card i {
        width: 32px;
        height: 32px;
    }

    /* History Timeline */
    .timeline-item {
        display: flex;
        gap: 1rem;
        padding: 1.25rem 0;
        border-bottom: 1px dashed #e2e8f0;
        cursor: pointer;
        position: relative;
    }

    .timeline-date {
        min-width: 60px;
        text-align: center;
        font-weight: 700;
        color: #3b82f6;
        line-height: 1.2;
    }

    .timeline-date span {
        display: block;
        font-size: 0.8rem;
        font-weight: 400;
        color: #64748b;
    }

    .timeline-content {
        flex: 1;
    }

    .details-collapse {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s cubic-bezier(0, 1, 0, 1);
    }

    .timeline-item.expanded .details-collapse {
        max-height: 500px;
        transition: max-height 0.3s cubic-bezier(1, 0, 1, 0);
    }

    .expand-icon {
        position: absolute;
        right: 0;
        top: 25px;
        color: #cbd5e1;
        transition: transform 0.3s;
    }

    .timeline-item.expanded .expand-icon {
        transform: rotate(180deg);
    }

    /* Mobile Polishing */
    @media (max-width: 1024px) {
        .page-content {
            background: #f5f7fa !important;
            min-height: 100vh !important;
            padding: 1rem !important;
        }

        .dashboard-container {
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }

        .mobile-stats-grid {
            display: grid !important;
            grid-template-columns: repeat(3, 1fr) !important;
            gap: 10px !important;
            order: 2;
        }

        .punch-card {
            order: 1;
            padding: 2rem 1rem;
        }

        .digital-clock {
            font-size: 2.2rem;
        }

        .custom-punch-btn {
            width: 140px;
            height: 140px;
            font-size: 1rem;
        }

        .mobile-stats-grid .mini-stat-card {
            padding: 1rem 0.5rem;
            border-radius: 12px;
        }

        .mobile-stats-grid .mini-stat-card i {
            width: 20px;
            height: 20px;
        }

        .mobile-stats-grid h2 {
            font-size: 1.2rem;
            margin: 4px 0;
        }

        .mobile-stats-grid span {
            font-size: 0.7rem;
            white-space: nowrap;
        }

        .timeline-item {
            padding-right: 30px;
        }
    }

    @media (max-width: 600px) {
        .timeline-date {
            min-width: 45px;
            font-size: 1.1rem;
        }

        .timeline-date span {
            font-size: 0.7rem;
        }
    }

    /* Custom Warning Modal */
    .warning-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
        z-index: 10000;
        align-items: center;
        justify-content: center;
    }

    .warning-modal-overlay.active {
        display: flex;
    }

    .warning-modal {
        background: white;
        border-radius: 20px;
        max-width: 500px;
        width: 90%;
        padding: 0;
        box-shadow: 0 20px 60px rgba(239, 68, 68, 0.4);
        animation: modalSlideIn 0.3s ease-out;
        overflow: hidden;
    }

    @keyframes modalSlideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .warning-modal-header {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        padding: 2rem;
        text-align: center;
        color: white;
    }

    .warning-modal-header i {
        width: 60px;
        height: 60px;
        margin-bottom: 1rem;
        animation: warningPulse 2s infinite;
    }

    @keyframes warningPulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.1);
        }
    }

    .warning-modal-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .warning-modal-body {
        padding: 2rem;
    }

    .warning-modal-body p {
        color: #64748b;
        margin-bottom: 1.5rem;
        line-height: 1.6;
        text-align: center;
    }

    .warning-modal-body textarea {
        width: 100%;
        min-height: 100px;
        padding: 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        font-family: inherit;
        font-size: 0.95rem;
        resize: vertical;
        transition: border-color 0.3s;
    }

    .warning-modal-body textarea:focus {
        outline: none;
        border-color: #ef4444;
    }

    .warning-modal-footer {
        padding: 0 2rem 2rem;
        display: flex;
        gap: 1rem;
    }

    .warning-modal-footer button {
        flex: 1;
        padding: 1rem;
        border: none;
        border-radius: 12px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
    }

    .warning-modal-cancel {
        background: #f1f5f9;
        color: #64748b;
    }

    .warning-modal-cancel:hover {
        background: #e2e8f0;
    }

    .warning-modal-submit {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .warning-modal-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3);
    }

    .warning-modal-submit:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
</style>

<!-- Custom Warning Modal -->
<div class="warning-modal-overlay" id="warningModal">
    <div class="warning-modal">
        <div class="warning-modal-header">
            <i data-lucide="alert-triangle"></i>
            <h2>⚠️ Out of Range Warning</h2>
        </div>
        <div class="warning-modal-body">
            <p id="warningModalMessage">You are punching from outside the permitted office location. Please provide a
                reason for this action.</p>
            <textarea id="warningReasonInput"
                placeholder="Enter reason here... (e.g., Client meeting, Field work, etc.)"></textarea>
        </div>
        <div class="warning-modal-footer">
            <button class="warning-modal-cancel" onclick="closeWarningModal()">
                <i data-lucide="x" style="width:18px; height:18px; vertical-align:middle;"></i> Cancel
            </button>
            <button class="warning-modal-submit" id="warningSubmitBtn" onclick="submitWarningReason()">
                <i data-lucide="check" style="width:18px; height:18px; vertical-align:middle;"></i> Submit & Continue
            </button>
        </div>
    </div>
</div>

<div class="page-content">
    <?= $message ?>

    <div class="dashboard-container">
        <!-- LEFT: Active Punch Card -->
        <div class="punch-card">
            <div style="font-size:1rem; opacity:0.9; margin-bottom: 2px;">Current Time</div>
            <div class="digital-clock" id="liveClock">00:00:00</div>
            <div style="font-size:0.9rem; opacity:0.8; margin-bottom:1.5rem;"><?= date('l, d F Y') ?></div>

            <form method="POST" id="attendanceForm">
                <input type="hidden" name="action" id="actionInput">
                <input type="hidden" name="latitude" id="latInput">
                <input type="hidden" name="longitude" id="lngInput">
                <input type="hidden" name="address" id="addrInput">
                <input type="hidden" name="location_id" id="locationIdInput">
                <input type="hidden" name="out_of_range" id="outOfRangeInput" value="0">
                <input type="hidden" name="reason" id="reasonInput">

                <?php if (!$has_checked_in): ?>
                    <button type="button" class="custom-punch-btn" onclick="getLocationAndSubmit('clock_in')">
                        <i data-lucide="power" style="width:40px; height:40px;"></i>
                        <span>Start Day</span>
                    </button>
                    <div class="status-capsule">
                        <span style="width:8px; height:8px; background:#ef4444; border-radius:50%;"></span>
                        Not Yet Checked In
                    </div>
                <?php elseif (!$has_checked_out): ?>
                    <button type="button" class="custom-punch-btn" onclick="getLocationAndSubmit('clock_out')"
                        style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4);">
                        <i data-lucide="log-out" style="width:40px; height:40px;"></i>
                        <span>End Day</span>
                    </button>
                    <div class="status-capsule">
                        <span
                            style="width:8px; height:8px; background:#10b981; border-radius:50%; box-shadow: 0 0 8px #10b981;"></span>
                        Working Since <?= date('h:i A', strtotime($today_record['clock_in'])) ?>
                    </div>
                <?php else: ?>
                    <button type="button" class="custom-punch-btn btn-disabled" disabled>
                        <i data-lucide="check-circle" style="width:40px; height:40px;"></i>
                        <span>Completed</span>
                    </button>
                    <div class="status-capsule">
                        <i data-lucide="check" style="width:14px;"></i> Total:
                        <?= formatDuration($today_record['total_hours']) ?>
                    </div>
                <?php endif; ?>
            </form>

            <div id="locationStatus" style="margin-top:1.25rem; font-size:0.8rem; opacity:0.7;">
                <i data-lucide="map-pin" style="width:14px; vertical-align:middle;"></i> Ready to capture location
            </div>
        </div>

        <!-- RIGHT/BOTTOM: Mini Stats (Compact Grid on Mobile) -->
        <div class="mobile-stats-grid" style="display: flex; flex-direction: column; gap: 1rem;">
            <div class="mini-stat-card">
                <div style="color:#10b981;"><i data-lucide="calendar-check"></i></div>
                <h2 style="font-size:2.2rem; margin:0; color:#1e293b;"><?= $present_days ?></h2>
                <span style="color:#64748b; font-size:0.85rem;">Days Present</span>
            </div>
            <div class="mini-stat-card">
                <div style="color:#f59e0b;"><i data-lucide="clock"></i></div>
                <h2 style="font-size:2.2rem; margin:0; color:#1e293b;"><?= $late_days ?></h2>
                <span style="color:#64748b; font-size:0.85rem;">Late Arrivals</span>
            </div>
            <div class="mini-stat-card">
                <div style="color:#3b82f6;"><i data-lucide="timer"></i></div>
                <h2 style="font-size:2.2rem; margin:0; color:#1e293b;"><?= formatDuration($total_work_hours) ?></h2>
                <span style="color:#64748b; font-size:0.85rem;">Total Hours</span>
            </div>
        </div>
    </div>

    <!-- History Timeline Section -->
    <div class="card" style="margin-top: 2rem; border-radius: 1.5rem; overflow: hidden;">
        <div class="card-header"
            style="justify-content: space-between; padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;">
            <h3 style="display:flex; align-items:center; gap:10px; margin: 0; font-size: 1.1rem;">
                <i data-lucide="history" style="color:#6366f1; width: 20px;"></i> Attendance History
            </h3>
            <form method="GET" style="display:flex; gap:8px;">
                <select name="month" class="form-control"
                    style="width:auto; padding: 0.4rem 2rem 0.4rem 0.75rem; font-size: 0.85rem; height: 36px; border-radius: 8px;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $filter_month ? 'selected' : '' ?>>
                            <?= date('M', mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-primary"
                    style="padding:0 1rem; height: 36px; border-radius: 8px; font-size: 0.85rem;">Go</button>
            </form>
        </div>
        <div style="padding: 0 1.5rem 1rem;">
            <?php foreach ($history as $row): ?>
                <div class="timeline-item history-item-mobile" onclick="this.classList.toggle('expanded')">
                    <i data-lucide="chevron-down" class="expand-icon mobile-only" style="width: 16px;"></i>
                    <div class="timeline-date">
                        <?= date('d', strtotime($row['date'])) ?>
                        <span><?= date('M', strtotime($row['date'])) ?></span>
                    </div>
                    <div class="timeline-content">
                        <div
                            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.25rem;">
                            <span
                                style="font-weight:700; color:#1e293b; font-size: 0.95rem;"><?= date('l', strtotime($row['date'])) ?></span>
                            <?php
                            $badgeStyle = match ($row['status']) {
                                'On Time' => 'background:#dcfce7; color:#166534;',
                                'Late' => 'background:#fef9c3; color:#854d0e;',
                                'Present' => 'background:#dbeafe; color:#1e40af;',
                                default => 'background:#f1f5f9; color:#64748b;'
                            };
                            ?>
                            <span class="badge"
                                style="<?= $badgeStyle ?> font-size: 0.65rem; padding: 2px 8px; font-weight: 700;"><?= $row['status'] ?></span>
                        </div>

                        <!-- Always visible summary -->
                        <div style="display:flex; gap:1.5rem; font-size:0.85rem; color:#64748b;">
                            <div style="display:flex; align-items:center; gap:4px;">
                                <i data-lucide="log-in" style="width:14px; color:#10b981;"></i>
                                <?= date('h:i A', strtotime($row['clock_in'])) ?>
                            </div>
                            <div style="display:flex; align-items:center; gap:4px;">
                                <i data-lucide="log-out" style="width:14px; color:#f43f5e;"></i>
                                <?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : 'Working...' ?>
                            </div>
                        </div>

                        <!-- Collapsible Details (Mobile addresses) -->
                        <div class="details-collapse" style="margin-top: 10px;">
                            <div
                                style="display: grid; gap: 10px; background: #f8fafc; padding: 10px; border-radius: 8px; border: 1px solid #f1f5f9;">
                                <div>
                                    <div
                                        style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">
                                        In Location</div>
                                    <div style="font-size: 0.8rem; color: #475569; line-height: 1.4;">
                                        <?= htmlspecialchars($row['clock_in_address'] ?: 'Not recorded') ?>
                                    </div>
                                    <?php if ($row['out_of_range'] && strpos($row['out_of_range_reason'], 'Out on Exit') === false): ?>
                                        <div
                                            style="font-size: 0.7rem; color: #dc2626; font-weight: 700; margin-top: 5px; background: #fff1f2; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                                            ⚠️ Out of Range: <?= htmlspecialchars($row['out_of_range_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($row['clock_out']): ?>
                                    <div>
                                        <div
                                            style="font-size: 0.7rem; color: #94a3b8; text-transform: uppercase; font-weight: 700; margin-bottom: 2px;">
                                            Out Location</div>
                                        <div style="font-size: 0.8rem; color: #475569; line-height: 1.4;">
                                            <?= htmlspecialchars($row['clock_out_address'] ?: 'Not recorded') ?>
                                        </div>
                                        <?php if ($row['out_of_range'] && strpos($row['out_of_range_reason'], 'Out on Exit') !== false): ?>
                                            <div
                                                style="font-size: 0.7rem; color: #dc2626; font-weight: 700; margin-top: 5px; background: #fff1f2; padding: 4px 8px; border-radius: 4px; display: inline-block;">
                                                ⚠️ Out of Range (Exit):
                                                <?= htmlspecialchars(str_replace('Out on Exit: ', '', $row['out_of_range_reason'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div style="text-align:right; min-width:60px;">
                        <span
                            style="display:block; font-size:1.1rem; font-weight:800; color:#3b82f6;"><?= formatDuration($row['total_hours']) ?></span>
                        <span
                            style="font-size:0.7rem; color:#94a3b8; font-weight: 600; text-transform: uppercase;">Hours</span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($history)): ?>
                <div style="text-align:center; padding:3rem; color:#94a3b8;">
                    No attendance records found for this period.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    // Live Clock
    function updateClock() {
        const now = new Date();
        document.getElementById('liveClock').innerText = now.toLocaleTimeString('en-US', { hour12: false });
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Geolocation Logic
    const assignedLocations = <?= json_encode($assigned_locations) ?> || [];
    console.log("Assigned Locations:", assignedLocations);

    // Update UI status if geofencing is active
    if (assignedLocations.length > 0) {
        const statusDiv = document.getElementById('locationStatus');
        if (statusDiv) {
            statusDiv.innerHTML += ' <span style="color:#10b981; font-weight:600;">(Geofencing Active)</span>';
        }
    } else {
        const statusDiv = document.getElementById('locationStatus');
        if (statusDiv) {
            statusDiv.innerHTML += ' <span style="color:#94a3b8;">(Geofencing Inactive - Open Punch)</span>';
        }
    }

    function getDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Radius of the earth in km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
            Math.sin(dLon / 2) * Math.sin(dLon / 2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c * 1000; // Distance in meters
    }

    function getLocationAndSubmit(type) {
        const statusDiv = document.getElementById('locationStatus');
        const form = document.getElementById('attendanceForm');

        statusDiv.innerHTML = '<span class="spin" style="display:inline-block;">⌛</span> Detecting Location...';

        if (navigator.geolocation) {
            const options = {
                enableHighAccuracy: true,
                timeout: 30000,
                maximumAge: 30000
            };

            const successCallback = async (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                document.getElementById('actionInput').value = type;
                document.getElementById('latInput').value = lat;
                document.getElementById('lngInput').value = lng;

                // Geofencing Check
                let matchedLocation = null;
                let isOutOfRange = false;
                let reason = "";

                if (assignedLocations.length > 0) {
                    for (const loc of assignedLocations) {
                        const dist = getDistance(lat, lng, parseFloat(loc.latitude), parseFloat(loc.longitude));
                        if (dist <= parseFloat(loc.radius)) {
                            matchedLocation = loc;
                            break;
                        }
                    }

                    if (!matchedLocation) {
                        isOutOfRange = true;
                        const actionText = (type === 'clock_in') ? 'Punch In' : 'Punch Out';

                        // Show custom warning modal instead of browser prompt
                        document.getElementById('warningModalMessage').textContent =
                            `Warning: You are attempting to ${actionText} from outside the permitted office location. Please provide a reason for this action.`;

                        // Show modal and wait for user response
                        const modalResult = await showWarningModal();

                        if (!modalResult || modalResult.trim() === "") {
                            statusDiv.innerHTML = '<span style="color:#ef4444;">Reason is required to punch from outside!</span>';
                            return;
                        }

                        reason = modalResult;
                    }
                } else {
                    console.warn("No assigned locations found for this employee. Geofencing check skipped.");
                }

                document.getElementById('locationIdInput').value = matchedLocation ? matchedLocation.id : '';
                document.getElementById('outOfRangeInput').value = isOutOfRange ? 1 : 0;
                document.getElementById('reasonInput').value = reason;

                statusDiv.innerHTML = 'Location Locked. Getting Address...';


                try {
                    const controller = new AbortController();
                    const timeoutId = setTimeout(() => controller.abort(), 5000); // 5 second timeout

                    // Use Google Maps Geocoding API for precise location
                    const apiKey = '<?= getenv("GOOGLE_MAPS_API_KEY") ?>';
                    console.log('API Key loaded:', apiKey ? 'Yes (length: ' + apiKey.length + ')' : 'No - EMPTY!');

                    const response = await fetch(
                        `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${apiKey}`,
                        { signal: controller.signal }
                    );
                    clearTimeout(timeoutId);

                    const data = await response.json();
                    console.log('Geocoding API Response:', data);

                    if (data.status === 'OK' && data.results[0]) {
                        const addressComponents = data.results[0].address_components;
                        let locationParts = [];

                        // Helper function to extract address component by type
                        const getComponent = (type) => {
                            const comp = addressComponents.find(c => c.types.includes(type));
                            return comp ? comp.long_name : null;
                        };

                        // Priority: route (road name) > sublocality > locality
                        const road = getComponent('route');
                        const sublocality = getComponent('sublocality_level_1') || getComponent('sublocality');
                        const locality = getComponent('locality');

                        // Add road/area name
                        if (road) {
                            locationParts.push(road);
                        } else if (sublocality) {
                            locationParts.push(sublocality);
                        }

                        // Always add city for context
                        if (locality) {
                            locationParts.push(locality);
                        }

                        // Use extracted location or fallback to formatted address
                        const shortAddress = locationParts.length > 0
                            ? locationParts.join(', ')
                            : data.results[0].formatted_address;

                        console.log('Final address:', shortAddress);
                        document.getElementById('addrInput').value = shortAddress;
                    } else {
                        console.error('Geocoding API Error - Status:', data.status, 'Error:', data.error_message);
                        throw new Error('Geocoding failed: ' + data.status);
                    }
                } catch (e) {
                    console.error('Geocoding failed:', e);
                    // Fallback: Use a more readable format
                    document.getElementById('addrInput').value = `Location: ${lat.toFixed(4)}, ${lng.toFixed(4)}`;
                }


                form.submit();
            };

            const errorCallback = (error) => {
                // FALLBACK: Try again with low accuracy if first attempt failed
                if (options.enableHighAccuracy) {
                    statusDiv.innerHTML = '<span class="spin" style="display:inline-block;">⌛</span> GPS weak, trying standard network location...';
                    options.enableHighAccuracy = false;
                    options.timeout = 20000; // Give 20s for network location
                    navigator.geolocation.getCurrentPosition(successCallback, finalErrorCallback, options);
                    return;
                }
                finalErrorCallback(error);
            };

            const finalErrorCallback = (error) => {
                let errorMsg = "Location Access Denied.";
                if (error.code === 1) errorMsg = "Location Access Denied. Please allow location in your browser settings.";
                else if (error.code === 2) errorMsg = "Location unavailable. Please check your GPS/Internet.";
                else if (error.code === 3) errorMsg = "Location request timed out. Please try refreshing.";

                statusDiv.innerHTML = `<span style="color:#ef4444;">${errorMsg}</span>`;
                alert(errorMsg);
            };

            navigator.geolocation.getCurrentPosition(successCallback, errorCallback, options);
        } else {
            alert("Geolocation not supported by this browser.");
        }
    }

    // Custom Warning Modal Functions
    let warningModalResolve = null;

    function showWarningModal() {
        return new Promise((resolve) => {
            warningModalResolve = resolve;
            const modal = document.getElementById('warningModal');
            const textarea = document.getElementById('warningReasonInput');

            // Clear previous input
            textarea.value = '';

            // Show modal
            modal.classList.add('active');

            // Focus on textarea
            setTimeout(() => textarea.focus(), 300);

            // Enable/disable submit button based on input
            textarea.addEventListener('input', function () {
                const submitBtn = document.getElementById('warningSubmitBtn');
                submitBtn.disabled = this.value.trim() === '';
            });

            // Initialize submit button as disabled
            document.getElementById('warningSubmitBtn').disabled = true;
        });
    }

    function closeWarningModal() {
        const modal = document.getElementById('warningModal');
        modal.classList.remove('active');

        if (warningModalResolve) {
            warningModalResolve(null); // Return null when cancelled
            warningModalResolve = null;
        }
    }

    function submitWarningReason() {
        const reason = document.getElementById('warningReasonInput').value.trim();

        if (reason === '') {
            alert('Please enter a reason before submitting.');
            return;
        }

        const modal = document.getElementById('warningModal');
        modal.classList.remove('active');

        if (warningModalResolve) {
            warningModalResolve(reason); // Return the reason
            warningModalResolve = null;
        }
    }

    // Close modal on Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('warningModal');
            if (modal.classList.contains('active')) {
                closeWarningModal();
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>