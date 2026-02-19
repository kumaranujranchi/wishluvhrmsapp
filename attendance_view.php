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
$loc_stmt->execute(['uid' => $user_id]);
$assigned_locations = $loc_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Permissions
$perm_stmt = $conn->prepare("SELECT allow_outside_punch FROM employees WHERE id = :uid");
$perm_stmt->execute(['uid' => $user_id]);
$allow_outside_punch = $perm_stmt->fetchColumn();

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
        // Permission Check for Outside Punch
        if ($out_of_range && !$allow_outside_punch) {
            $message = "<div class='alert error'>Permission Denied: You are not allowed to mark attendance from outside the office location.</div>";
        } else {
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
                $body = getHtmlEmailTemplate("Punch In Confirmation", $content);
                sendEmail($emp['email'], $subject, $body);
            }
        }
    } elseif ($action === 'clock_out') {
        // Permission Check for Outside Punch
        if ($out_of_range && !$allow_outside_punch) {
            $message = "<div class='alert error'>Permission Denied: You are not allowed to mark attendance from outside the office location.</div>";
        } else {
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
    if ($h['status'] == 'Half Day') {
        $present_days += 0.5;
    } elseif (!empty($h['clock_in']) || in_array($h['status'], ['Present', 'On Time', 'Late', 'Leave'])) {
        $present_days++;
    }

    if ($h['status'] == 'Late') {
        $late_days++;
    }
    $total_work_hours += $h['total_hours'];
}

// Helper function to format duration from minutes to hours:minutes
function formatDuration($total_minutes)
{
    if (!$total_minutes || $total_minutes == 0)
        return '-';
    $hours = floor($total_minutes / 60);
    $minutes = $total_minutes % 60;

    if ($hours > 0 && $minutes > 0) {
        return $hours . ' hr ' . $minutes . ' min';
    } elseif ($hours > 0) {
        return $hours . ' hr';
    } else {
        return $minutes . ' min';
    }
}


?>

<style>
    /* Layout & Hero Card */
    .dashboard-container {
        display: grid;
        grid-template-columns: 1fr;
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

    /* Stats Cards - Colorful Design */
    .mini-stat-card {
        border-radius: 20px;
        padding: 16px;
        color: white;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 10px -2px rgba(0, 0, 0, 0.15);
        min-height: 140px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .mini-stat-card .stat-icon {
        position: absolute;
        bottom: -12px;
        right: -8px;
        opacity: 0.15;
        width: 70px;
        height: 70px;
        transform: rotate(-10deg);
    }

    .mini-stat-card .stat-label {
        font-size: 9px;
        text-transform: uppercase;
        font-weight: 700;
        letter-spacing: 0.05em;
        opacity: 0.9;
        margin-bottom: 4px;
    }

    .mini-stat-card .stat-value {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
        margin: 8px 0;
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

    /* --- RESPONSIVE ATTENDANCE HISTORY --- */
    /* Desktop Defaults (Big Fonts, Horizontal Layout) */
    .att-day {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1e293b;
    }

    .att-badge {
        font-size: 0.8rem;
        padding: 4px 10px;
        font-weight: 700;
        border-radius: 50px;
    }

    .att-time {
        font-size: 0.95rem;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .att-loc-label {
        font-size: 0.75rem;
        color: #94a3b8;
        font-weight: 700;
        text-transform: uppercase;
        margin-bottom: 2px;
    }

    .att-loc-text {
        font-size: 0.85rem;
        color: #475569;
        line-height: 1.4;
    }

    .att-hours {
        font-size: 1.3rem;
        font-weight: 800;
        color: #3b82f6;
        line-height: 1;
        display: block;
    }

    .att-hours-label {
        font-size: 0.8rem;
        color: #94a3b8;
        font-weight: 600;
        text-transform: uppercase;
    }

    /* Desktop Layout */
    .timeline-content {
        display: flex;
        align-items: center;
        gap: 2rem;
        width: 100%;
    }

    .att-header-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        width: 120px;
        flex-shrink: 0;
    }

    .att-time-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        width: 140px;
        flex-shrink: 0;
    }

    .details-collapse {
        display: block !important;
        max-height: none !important;
        margin: 0 !important;
        flex: 1;
    }

    .att-loc-container {
        display: flex;
        gap: 2rem;
        background: none !important;
        border: none !important;
        padding: 0 !important;
    }

    /* Fixed width columns for alignment */
    .att-loc-item {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    /* Force exact 50% width for desktop location items to ensure alignment */
    @media (min-width: 1025px) {
        .att-loc-item {
            flex: 0 0 45% !important;
            max-width: 45%;
        }
    }

    .expand-icon {
        display: none;
    }

    .timeline-item {
        cursor: default;
    }


    /* Mobile Polishing */
    @media (max-width: 1024px) {

        /* Mobile Overrides for Attendance History */
        .timeline-content {
            display: block;
        }

        .att-header-group {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.25rem;
            width: 100%;
        }

        .att-time-group {
            flex-direction: row;
            gap: 1rem;
            margin-bottom: 0.25rem;
            width: 100%;
        }

        .details-collapse {
            max-height: 0 !important;
            margin-top: 10px !important;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }

        .timeline-item.expanded .details-collapse {
            max-height: 500px !important;
        }

        .att-loc-container {
            gap: 8px;
            background: #f8fafc !important;
            padding: 10px !important;
            border: 1px solid #f1f5f9 !important;
            border-radius: 8px !important;
            flex-wrap: nowrap;
        }

        .expand-icon {
            display: block;
        }

        .timeline-item {
            cursor: pointer;
        }

        /* Small Fonts for Mobile (Preserving Sorted View) */
        .att-day {
            font-size: 0.8rem;
        }

        .att-badge {
            font-size: 0.6rem;
            padding: 2px 6px;
        }

        .att-time {
            font-size: 0.7rem;
            gap: 3px;
        }

        .att-time i {
            width: 12px;
            height: 12px;
        }

        .att-loc-label {
            font-size: 0.6rem;
        }

        .att-loc-text {
            font-size: 0.7rem;
            line-height: 1.2;
        }

        .att-hours {
            font-size: 0.75rem;
            white-space: nowrap;
        }

        .att-hours-label {
            font-size: 0.6rem;
        }

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
            padding: 14px;
            border-radius: 16px;
            min-height: 120px;
        }

        .mobile-stats-grid .mini-stat-card .stat-icon {
            width: 50px;
            height: 50px;
        }

        .mobile-stats-grid .stat-value {
            font-size: 1.5rem !important;
        }

        .mobile-stats-grid .stat-label {
            font-size: 8px !important;
        }

        .mobile-stats-grid span {
            font-size: 0.7rem;
            white-space: nowrap;
        }

        .timeline-item {
            padding: 1rem 1rem 1rem 0.75rem;
        }

        .card {
            padding: 0 !important;
        }
    }

    @media (max-width: 600px) {
        .timeline-date {
            min-width: 45px;
            font-size: 0.9rem;
        }

        .timeline-date span {
            font-size: 0.65rem;
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
        z-index: 10010;
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

    /* Face Verification Modal */
    .face-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(8px);
        z-index: 10001;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .face-modal.active {
        display: flex;
    }

    .face-modal-content {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        max-width: 600px;
        width: 100%;
        text-align: center;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .face-video-wrapper {
        position: relative;
        width: 100%;
        border-radius: 16px;
        overflow: hidden;
        background: #000;
        aspect-ratio: 4/3;
        margin: 1.5rem auto;
        border: 4px solid #f8fafc;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    #faceVideo {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
        /* Mirror effect */
        display: block;
    }

    /* Face Guide Overlay */
    .face-guide {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 180px;
        height: 240px;
        border: 2px dashed rgba(255, 255, 255, 0.6);
        border-radius: 50% / 45%;
        box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.4);
        pointer-events: none;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .face-guide::before {
        content: "Align Face";
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        background: rgba(0, 0, 0, 0.5);
        padding: 2px 8px;
        border-radius: 4px;
        margin-top: -20px;
    }

    .face-controls {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
    }

    .face-controls button {
        flex: 1;
        padding: 0.85rem 0.5rem;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        white-space: nowrap;
        min-width: 0;
    }

    @media (max-width: 480px) {
        .face-controls {
            flex-wrap: wrap;
        }

        .face-controls button {
            flex: 1 1 100%;
        }

        .face-capture-btn {
            order: -1;
            padding: 1rem !important;
            font-size: 1rem !important;
        }
    }

    .face-capture-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
    }

    .face-capture-btn:hover {
        transform: translateY(-2px);
    }

    .face-cancel-btn {
        background: #f1f5f9;
        color: #64748b;
    }

    .scan-line {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: #10b981;
        box-shadow: 0 0 10px #10b981;
        z-index: 11;
        animation: scan 2.5s linear infinite;
        display: none;
    }

    @keyframes scan {
        0% {
            top: 20%;
            opacity: 0;
        }

        10% {
            opacity: 1;
        }

        90% {
            opacity: 1;
        }

        100% {
            top: 80%;
            opacity: 0;
        }
    }

    .enrollment-tips {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #f1f5f9;
    }

    .tip-item {
        text-align: center;
    }

    .tip-icon {
        width: 28px;
        height: 28px;
        background: #f8fafc;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.4rem;
        color: #6366f1;
    }

    .tip-text {
        font-size: 0.65rem;
        color: #64748b;
        font-weight: 600;
    }

    .manual-punch-link {
        margin-top: 1rem;
        color: rgba(255, 255, 255, 0.9);
        text-decoration: underline;
        cursor: pointer;
        font-size: 0.9rem;
        font-weight: 500;
    }

    .manual-punch-link:hover {
        color: #ffffff;
    }

    /* Processing Modal */
    .processing-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(10px);
        z-index: 10001;
        align-items: center;
        justify-content: center;
    }

    .processing-modal.active {
        display: flex;
    }

    .processing-modal-content {
        background: white;
        border-radius: 24px;
        padding: 3rem 2.5rem;
        max-width: 450px;
        width: 90%;
        text-align: center;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        animation: modalFadeIn 0.3s ease-out;
    }

    @keyframes modalFadeIn {
        from {
            transform: scale(0.9);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .processing-spinner {
        width: 60px;
        height: 60px;
        border: 4px solid #f1f5f9;
        border-top: 4px solid #6366f1;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 1.5rem;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .processing-modal-content h3 {
        color: #1e293b;
        font-size: 1.5rem;
        font-weight: 700;
        margin: 0 0 0.75rem 0;
    }

    .processing-modal-content p {
        color: #64748b;
        font-size: 1rem;
        line-height: 1.6;
        margin: 0;
    }

    .processing-modal-content.success .processing-spinner {
        display: none;
    }

    .processing-modal-content.success::before {
        content: '✓';
        display: block;
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        font-size: 3rem;
        font-weight: 700;
        border-radius: 50%;
        margin: 0 auto 1.5rem;
        line-height: 80px;
        animation: successPop 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    @keyframes successPop {
        0% {
            transform: scale(0);
        }

        100% {
            transform: scale(1);
        }
    }

    .processing-modal-content.error .processing-spinner {
        display: none;
    }

    .processing-modal-content.error::before {
        content: '✕';
        display: block;
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
        font-size: 3rem;
        font-weight: 700;
        border-radius: 50%;
        margin: 0 auto 1.5rem;
        line-height: 80px;
        animation: successPop 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }
</style>

<!-- Face Verification Modal -->
<div class="face-modal" id="faceModal">
    <div class="face-modal-content">
        <h3 id="faceModalTitle" style="color: #1e293b; font-weight: 800;">📸 Face Verification</h3>

        <div class="face-video-wrapper">
            <video id="faceVideo" autoplay playsinline></video>
            <div class="face-guide"></div>
            <div class="scan-line" id="faceScanLine"></div>
        </div>

        <canvas id="faceCanvas" style="display: none;"></canvas>

        <div class="face-controls">
            <button class="face-cancel-btn" onclick="closeFaceModal()">
                <i data-lucide="x" style="width: 18px;"></i> Cancel
            </button>
            <button class="face-capture-btn" onclick="captureFaceAndVerify()">
                <i data-lucide="camera" style="width: 18px;"></i> Verify Face
            </button>
        </div>

        <div class="enrollment-tips">
            <div class="tip-item">
                <div class="tip-icon"><i data-lucide="sun" style="width: 14px;"></i></div>
                <div class="tip-text">Good<br>Lighting</div>
            </div>
            <div class="tip-item">
                <div class="tip-icon"><i data-lucide="smile" style="width: 14px;"></i></div>
                <div class="tip-text">Center<br>Face</div>
            </div>
            <div class="tip-item">
                <div class="tip-icon"><i data-lucide="eye" style="width: 14px;"></i></div>
                <div class="tip-text">Remove<br>Glasses</div>
            </div>
        </div>
    </div>
</div>

<!-- Processing Status Modal -->
<div class="processing-modal" id="processingModal">
    <div class="processing-modal-content">
        <div class="processing-spinner"></div>
        <h3 id="processingTitle">Processing...</h3>
        <p id="processingMessage">Please wait while we verify your attendance.</p>
    </div>
</div>

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
                    <button type="button" class="custom-punch-btn" onclick="startFaceVerification('clock_in')">
                        <i data-lucide="scan-face" style="width:40px; height:40px;"></i>
                        <span>Verify & Start</span>
                    </button>
                    <div class="status-capsule">
                        <span style="width:8px; height:8px; background:#ef4444; border-radius:50%;"></span>
                        Not Yet Checked In
                    </div>

                <?php elseif (!$has_checked_out): ?>
                    <button type="button" class="custom-punch-btn" onclick="startFaceVerification('clock_out')"
                        style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4);">
                        <i data-lucide="scan-face" style="width:40px; height:40px;"></i>
                        <span>Verify & End</span>
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
                        <!-- Group 1: Day & Status -->
                        <div class="att-header-group">
                            <span class="att-day"><?= date('l', strtotime($row['date'])) ?></span>
                            <?php
                            $badgeStyle = match ($row['status']) {
                                'On Time' => 'background:#dcfce7; color:#166534;',
                                'Late' => 'background:#fef9c3; color:#854d0e;',
                                'Present' => 'background:#dbeafe; color:#1e40af;',
                                default => 'background:#f1f5f9; color:#64748b;'
                            };
                            ?>
                            <span class="badge att-badge" style="<?= $badgeStyle ?>"><?= $row['status'] ?></span>
                        </div>

                        <!-- Group 2: Times -->
                        <div class="att-time-group">
                            <div class="att-time">
                                <i data-lucide="log-in" style="width:14px; color:#10b981;"></i>
                                <?= date('h:i A', strtotime($row['clock_in'])) ?>
                            </div>
                            <div class="att-time">
                                <i data-lucide="log-out" style="width:14px; color:#f43f5e;"></i>
                                <?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : 'Working...' ?>
                            </div>
                        </div>

                        <!-- Group 3: Locations (Collapsible on Mobile, Inline on Desktop) -->
                        <div class="details-collapse">
                            <div class="att-loc-container">
                                <div class="att-loc-item" style="flex: 1; min-width: 0;">
                                    <div class="att-loc-label">In Location</div>
                                    <div class="att-loc-text">
                                        <?= htmlspecialchars($row['clock_in_address'] ?: 'Not recorded') ?>
                                    </div>
                                    <?php if ($row['out_of_range'] && strpos($row['out_of_range_reason'], 'Out on Exit') === false): ?>
                                        <div
                                            style="font-size: 0.65rem; color: #dc2626; font-weight: 700; margin-top: 4px; background: #fff1f2; padding: 3px 6px; border-radius: 4px; display: inline-block;">
                                            ⚠️ Out of Range: <?= htmlspecialchars($row['out_of_range_reason']) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php if ($row['clock_out']): ?>
                                    <div class="att-loc-item" style="flex: 1; min-width: 0;">
                                        <div class="att-loc-label">Out Location</div>
                                        <div class="att-loc-text">
                                            <?= htmlspecialchars($row['clock_out_address'] ?: 'Not recorded') ?>
                                        </div>
                                        <?php if ($row['out_of_range'] && strpos($row['out_of_range_reason'], 'Out on Exit') !== false): ?>
                                            <div
                                                style="font-size: 0.65rem; color: #dc2626; font-weight: 700; margin-top: 4px; background: #fff1f2; padding: 3px 6px; border-radius: 4px; display: inline-block;">
                                                ⚠️ Exit Range:
                                                <?= htmlspecialchars(str_replace('Out on Exit: ', '', $row['out_of_range_reason'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Right: Hours -->
                    <div style="text-align:right; min-width:50px;">
                        <span class="att-hours"><?= formatDuration($row['total_hours']) ?></span>
                        <span class="att-hours-label">Hours</span>
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

    // --- APP ENFORCEMENT & SECURITY CHECK ---
    document.addEventListener("DOMContentLoaded", function () {
        // Check for User Agent OR Native Bridge (Fallback)
        const isMobileApp = navigator.userAgent.includes("WishluvMobileApp") ||
            (window.Capacitor && window.Capacitor.isNativePlatform());

        if (!isMobileApp) {
            // Not running inside the Native App
            const form = document.getElementById('attendanceForm');
            if (form) {
                form.innerHTML = `
                    <div style="text-align: center; padding: 2rem; background: #fff1f2; border-radius: 1rem; border: 1px solid #fecaca;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 1rem;"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><path d="M12 18h.01"/></svg>
                        <h3 style="color: #991b1b; margin-bottom: 0.5rem; font-weight: 800;">App Required</h3>
                        <p style="color: #7f1d1d; font-size: 0.9rem; margin-bottom: 0;">To ensure security, please mark attendance using the official <br><b>Wishluv Employee App</b>.</p>
                    </div>
                `;
            }
        }
    });


    // Geolocation Logic
    const assignedLocations = <?= json_encode($assigned_locations) ?> || [];
    const allowOutsidePunch = <?= $allow_outside_punch ? 'true' : 'false' ?>;
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

                        // Check Permission
                        if (!allowOutsidePunch) {
                            statusDiv.innerHTML = '<span style="color:#ef4444;">🚫 Permission Denied: Outside Office Location</span>';
                            alert("Permission Denied: You are not allowed to mark attendance from outside the office location.");
                            return;
                        }

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
                CustomDialog.alert(errorMsg, 'error', 'Location Error');
            };

            navigator.geolocation.getCurrentPosition(successCallback, errorCallback, options);
        } else {
            CustomDialog.alert("Geolocation not supported by this browser.", 'warning');
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
            CustomDialog.alert('Please enter a reason before submitting.', 'warning');
            return;
        }

        const modal = document.getElementById('warningModal');
        modal.classList.remove('active');

        if (warningModalResolve) {
            warningModalResolve(reason); // Return the reason
            warningModalResolve = null;
        }
    }

    // ========================================
    // FACE VERIFICATION FUNCTIONS
    // ========================================
    let faceStream = null;
    let currentPunchAction = null;

    async function startFaceVerification(action) {
        currentPunchAction = action;
        const modal = document.getElementById('faceModal');
        const video = document.getElementById('faceVideo');

        const actionText = action === 'clock_in' ? 'Clock In' : 'Clock Out';
        document.getElementById('faceModalTitle').textContent = `📸 Face Verification - ${actionText}`;

        modal.classList.add('active');

        try {
            faceStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: 640, height: 480 }
            });
            video.srcObject = faceStream;
        } catch (error) {
            CustomDialog.alert('Camera access denied. Please allow camera permissions to mark attendance.', 'error', 'Camera Access Required');
            closeFaceModal();
        }
    }

    function closeFaceModal() {
        const modal = document.getElementById('faceModal');
        const video = document.getElementById('faceVideo');

        if (faceStream) {
            faceStream.getTracks().forEach(track => track.stop());
            faceStream = null;
        }

        video.srcObject = null;
        modal.classList.remove('active');
        currentPunchAction = null;
    }

    async function captureFaceAndVerify() {
        // --- NATIVE SECURITY CHECK ---
        if (typeof window.Android !== 'undefined') {
            const isMock = window.Android.isMockLocationActive();
            const isUsb = window.Android.isUsbDebuggingActive();

            if (isMock) {
                alert('❌ Fake GPS Detected! Please disable Mock Locations to mark attendance.');
                return;
            }
            if (isUsb) {
                alert('⚠️ USB Debugging Detected! Please disable Developer Options.');
                return;
            }
        }
        // -----------------------------

        const video = document.getElementById('faceVideo');
        const canvas = document.getElementById('faceCanvas');
        const scanLine = document.getElementById('faceScanLine');

        // Show scan animation
        scanLine.style.display = 'block';

        // Fix: Capture currentPunchAction BEFORE closing modal
        const actionToExecute = currentPunchAction;
        if (!actionToExecute) {
            CustomDialog.alert('No punch action specified.', 'error', 'Action Error');
            return;
        }

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        const imageData = canvas.toDataURL('image/jpeg', 0.9);

        // Wait a bit for the "scan" feel
        await new Promise(r => setTimeout(r, 800));

        // Close camera
        closeFaceModal();
        scanLine.style.display = 'none';

        // Show processing modal
        showProcessingModal('Processing Face Data', 'We are processing your face data, please wait...');

        // Get location
        if (!navigator.geolocation) {
            CustomDialog.alert('Geolocation is not supported by your browser.', 'warning');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            async (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

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
                        const actionText = (actionToExecute === 'clock_in') ? 'Punch In' : 'Punch Out';

                        // Show custom warning modal instead of browser prompt
                        document.getElementById('warningModalMessage').textContent =
                            `Warning: You are attempting to ${actionText} from outside the permitted office location. Please provide a reason for this action.`;

                        // Show modal and wait for user response
                        const modalResult = await showWarningModal();

                        if (!modalResult || modalResult.trim() === "") {
                            hideProcessingModal();
                            CustomDialog.alert('Reason is required to punch from outside office location.', 'warning', 'Reason Required');
                            return;
                        }

                        reason = modalResult;
                    }
                }

                // Get address (Background)
                updateProcessingModal('Getting Location', 'Fetching your location details...');
                let address = 'Location not available';

                try {
                    const apiKey = '<?= getenv("GOOGLE_MAPS_API_KEY") ?>';
                    const response = await fetch(
                        `https://maps.googleapis.com/maps/api/geocode/json?latlng=${lat},${lng}&key=${apiKey}`
                    );
                    const data = await response.json();

                    if (data.status === 'OK' && data.results[0]) {
                        const addressComponents = data.results[0].address_components;
                        const getComponent = (type) => {
                            const comp = addressComponents.find(c => c.types.includes(type));
                            return comp ? comp.long_name : null;
                        };

                        const road = getComponent('route');
                        const sublocality = getComponent('sublocality_level_1') || getComponent('sublocality');
                        const locality = getComponent('locality');

                        let locationParts = [];
                        if (road) locationParts.push(road);
                        else if (sublocality) locationParts.push(sublocality);
                        if (locality) locationParts.push(locality);

                        address = locationParts.length > 0 ? locationParts.join(', ') : data.results[0].formatted_address;
                    }
                } catch (e) {
                    console.error('Geocoding failed:', e);
                }

                // Verify face with backend
                updateProcessingModal('Verifying Face', 'Verifying your face with Azure Face Recognition...');

                const formData = new URLSearchParams();
                formData.append('image_data', imageData);
                formData.append('action', actionToExecute);
                formData.append('latitude', lat);
                formData.append('longitude', lng);
                formData.append('address', address);
                formData.append('location_id', matchedLocation ? matchedLocation.id : '');
                formData.append('out_of_range', isOutOfRange ? '1' : '0');
                formData.append('reason', reason);

                try {
                    const response = await fetch('ajax/verify_face_punch.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: formData.toString()
                    });

                    const result = await response.json();

                    if (result.success) {
                        showSuccessModal('Success!', result.message);
                        setTimeout(() => location.reload(), 2500);
                    } else {
                        showErrorModal('Verification Failed', result.message);
                        setTimeout(() => hideProcessingModal(), 3000);
                    }
                } catch (error) {
                    showErrorModal('System Error', 'An error occurred during verification. Please try again.');
                    setTimeout(() => hideProcessingModal(), 3000);
                }
            },
            (error) => {
                hideProcessingModal();
                CustomDialog.alert('Location access denied. Please enable location services to mark attendance.', 'warning', 'Location Required');
            },
            {
                enableHighAccuracy: true,
                timeout: 30000,
                maximumAge: 30000
            }
        );
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

    // Processing Modal Functions
    function showProcessingModal(title, message) {
        const modal = document.getElementById('processingModal');
        const content = modal.querySelector('.processing-modal-content');
        content.className = 'processing-modal-content';
        document.getElementById('processingTitle').textContent = title;
        document.getElementById('processingMessage').textContent = message;
        modal.classList.add('active');
    }

    function updateProcessingModal(title, message) {
        document.getElementById('processingTitle').textContent = title;
        document.getElementById('processingMessage').textContent = message;
    }

    function showSuccessModal(title, message) {
        const modal = document.getElementById('processingModal');
        const content = modal.querySelector('.processing-modal-content');
        content.className = 'processing-modal-content success';
        document.getElementById('processingTitle').textContent = title;
        document.getElementById('processingMessage').textContent = message;
    }

    function showErrorModal(title, message) {
        const modal = document.getElementById('processingModal');
        const content = modal.querySelector('.processing-modal-content');
        content.className = 'processing-modal-content error';
        document.getElementById('processingTitle').textContent = title;
        document.getElementById('processingMessage').textContent = message;
    }

    function hideProcessingModal() {
        const modal = document.getElementById('processingModal');
        modal.classList.remove('active');
    }
</script>

<?php include 'includes/footer.php'; ?>