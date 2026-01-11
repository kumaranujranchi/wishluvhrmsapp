<?php
require_once 'config/db.php';
include 'includes/header.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$date = date('Y-m-d');
$message = "";

// 1. Handle POST Requests (Check In / Check Out)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $lat = $_POST['latitude'] ?? null;
    $lng = $_POST['longitude'] ?? null;
    $address = $_POST['address'] ?? 'Location not allocated';

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

            $status = (strtotime($current_time) > strtotime($shift_start)) ? 'Late' : 'Present';

            $sql = "INSERT INTO attendance (employee_id, date, clock_in, status, clock_in_lat, clock_in_lng, clock_in_address) 
                    VALUES (:uid, :date, :time, :status, :lat, :lng, :addr)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'uid' => $user_id,
                'date' => $date,
                'time' => $current_time,
                'status' => $status,
                'lat' => $lat,
                'lng' => $lng,
                'addr' => $address
            ]);
            $message = "<div class='alert success-glass'>Checked In Successfully at " . date('h:i A', strtotime($current_time)) . "</div>";
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
            $hours = round(abs($clock_out_time - $clock_in_time) / 3600, 2);

            $sql = "UPDATE attendance SET 
                    clock_out = :time, 
                    total_hours = :hours,
                    clock_out_lat = :lat,
                    clock_out_lng = :lng,
                    clock_out_address = :addr 
                    WHERE employee_id = :uid AND date = :date";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                'time' => $current_time,
                'hours' => $hours,
                'lat' => $lat,
                'lng' => $lng,
                'addr' => $address,
                'uid' => $user_id,
                'date' => $date
            ]);
            $message = "<div class='alert success-glass'>Checked Out Successfully at " . date('h:i A', strtotime($current_time)) . ". Total Hours: $hours</div>";
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
?>

<style>
    /* Gradient Hero & Glassmorphism */
    .dashboard-container {
        display: grid;
        grid-template-columns: 1fr 350px;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1024px) {
        .dashboard-container {
            grid-template-columns: 1fr;
        }
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

    .btn-disabled:hover {
        transform: none;
        box-shadow: none;
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

    /* History Timeline */
    .history-card {
        background: white;
        border-radius: 1.5rem;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        height: 100%;
    }

    .timeline-item {
        display: flex;
        gap: 1rem;
        padding: 1.25rem 0;
        border-bottom: 1px dashed #e2e8f0;
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

    .stat-badge-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        margin-top: 2rem;
    }

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

    .alert-glass {
        background: rgba(16, 185, 129, 0.1);
        border: 1px solid rgba(16, 185, 129, 0.2);
        color: #065f46;
        padding: 1rem;
        border-radius: 1rem;
        margin-bottom: 2rem;
        backdrop-filter: blur(5px);
        display: flex;
        align-items: center;
        justify-content: center;
    }
</style>

<div class="page-content">
    <?= $message ?>

    <div class="dashboard-container">
        <!-- LEFT: Active Punch Card -->
        <div class="punch-card">
            <div style="font-size:1.1rem; opacity:0.9;">Current Time</div>
            <div class="digital-clock" id="liveClock">00:00:00</div>
            <div style="font-size:1rem; opacity:0.8; margin-bottom:2rem;"><?= date('l, d F Y') ?></div>

            <form method="POST" id="attendanceForm">
                <input type="hidden" name="action" id="actionInput">
                <input type="hidden" name="latitude" id="latInput">
                <input type="hidden" name="longitude" id="lngInput">
                <input type="hidden" name="address" id="addrInput">

                <?php if (!$has_checked_in): ?>
                    <button type="button" class="custom-punch-btn" onclick="getLocationAndSubmit('clock_in')">
                        <i data-lucide="power" style="width:48px; height:48px;"></i>
                        <span>Start Day</span>
                    </button>
                    <div class="status-capsule">
                        <span style="width:10px; height:10px; background:#ef4444; border-radius:50%;"></span>
                        Not Yet Checked In
                    </div>
                <?php elseif (!$has_checked_out): ?>
                    <button type="button" class="custom-punch-btn" onclick="getLocationAndSubmit('clock_out')"
                        style="background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.4);">
                        <i data-lucide="log-out" style="width:48px; height:48px;"></i>
                        <span>End Day</span>
                    </button>
                    <div class="status-capsule">
                        <span
                            style="width:10px; height:10px; background:#10b981; border-radius:50%; box-shadow: 0 0 10px #10b981;"></span>
                        Working Since <?= date('h:i A', strtotime($today_record['clock_in'])) ?>
                    </div>
                <?php else: ?>
                    <button type="button" class="custom-punch-btn btn-disabled" disabled>
                        <i data-lucide="check-circle" style="width:48px; height:48px;"></i>
                        <span>Completed</span>
                    </button>
                    <div class="status-capsule">
                        <i data-lucide="check" style="width:16px;"></i> Total: <?= $today_record['total_hours'] ?> Hours
                    </div>
                <?php endif; ?>
            </form>

            <div id="locationStatus" style="margin-top:1.5rem; font-size:0.85rem; opacity:0.7;">
                <i data-lucide="map-pin" style="width:14px; vertical-align:middle;"></i> Ready to capture location
            </div>
        </div>

        <!-- RIGHT: Mini Stats -->
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            <div class="mini-stat-card">
                <div style="color:#10b981; margin-bottom:0.5rem;"><i data-lucide="calendar-check"
                        style="width:32px; height:32px;"></i></div>
                <h2 style="font-size:2.5rem; margin:0; color:#1e293b;"><?= $present_days ?></h2>
                <span style="color:#64748b; font-size:0.9rem;">Days Present</span>
            </div>
            <div class="mini-stat-card">
                <div style="color:#f59e0b; margin-bottom:0.5rem;"><i data-lucide="clock"
                        style="width:32px; height:32px;"></i></div>
                <h2 style="font-size:2.5rem; margin:0; color:#1e293b;"><?= $late_days ?></h2>
                <span style="color:#64748b; font-size:0.9rem;">Late Arrivals</span>
            </div>
            <div class="mini-stat-card">
                <div style="color:#3b82f6; margin-bottom:0.5rem;"><i data-lucide="timer"
                        style="width:32px; height:32px;"></i></div>
                <h2 style="font-size:2.5rem; margin:0; color:#1e293b;"><?= $total_work_hours ?></h2>
                <span style="color:#64748b; font-size:0.9rem;">Total Hours</span>
            </div>
        </div>
    </div>

    <!-- History Timeline Section -->
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header" style="justify-content: space-between;">
            <h3 style="display:flex; align-items:center; gap:10px;">
                <i data-lucide="history" style="color:#6366f1;"></i> Attendance History
            </h3>
            <form method="GET" style="display:flex; gap:10px;">
                <select name="month" class="form-control" style="width:auto; padding: 0.5rem 1rem;">
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $filter_month ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-primary" style="padding:0.5rem 1rem;">Go</button>
            </form>
        </div>
        <div style="padding: 0 1.5rem;">
            <?php foreach ($history as $row): ?>
                <div class="timeline-item">
                    <div class="timeline-date">
                        <?= date('d', strtotime($row['date'])) ?>
                        <span><?= date('M', strtotime($row['date'])) ?></span>
                    </div>
                    <div class="timeline-content">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.5rem;">
                            <span style="font-weight:600; color:#1e293b;"><?= date('l', strtotime($row['date'])) ?></span>
                            <?php
                            $badgeStyle = match ($row['status']) {
                                'Present' => 'background:#dcfce7; color:#166534;',
                                'Late' => 'background:#fef9c3; color:#854d0e;',
                                default => 'background:#f1f5f9; color:#64748b;'
                            };
                            ?>
                            <span class="badge" style="<?= $badgeStyle ?>"><?= $row['status'] ?></span>
                        </div>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:1rem; font-size:0.9rem;">
                            <div style="color:#64748b;">
                                <i data-lucide="log-in" style="width:14px; vertical-align:middle; color:#10b981;"></i>
                                <?= date('h:i A', strtotime($row['clock_in'])) ?>
                                <div style="font-size:0.75rem; color:#94a3b8; margin-top:2px;">
                                    <?= htmlspecialchars($row['clock_in_address'] ?? '') ?></div>
                            </div>
                            <div style="color:#64748b;">
                                <i data-lucide="log-out" style="width:14px; vertical-align:middle; color:#f43f5e;"></i>
                                <?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : 'Working...' ?>
                                <div style="font-size:0.75rem; color:#94a3b8; margin-top:2px;">
                                    <?= htmlspecialchars($row['clock_out_address'] ?? '') ?></div>
                            </div>
                        </div>
                    </div>
                    <div style="text-align:right; min-width:80px;">
                        <span
                            style="display:block; font-size:1.2rem; font-weight:700; color:#3b82f6;"><?= $row['total_hours'] ?: '0' ?></span>
                        <span style="font-size:0.75rem; color:#64748b;">Hours</span>
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
    function getLocationAndSubmit(type) {
        const statusDiv = document.getElementById('locationStatus');
        const form = document.getElementById('attendanceForm');

        statusDiv.innerHTML = '<span class="spin" style="display:inline-block;">⌛</span> Detecting Location...';

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(async (position) => {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;

                document.getElementById('actionInput').value = type;
                document.getElementById('latInput').value = lat;
                document.getElementById('lngInput').value = lng;

                statusDiv.innerHTML = 'Location Locked. Getting Address...';

                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                    const data = await response.json();
                    document.getElementById('addrInput').value = data.display_name || (lat + ', ' + lng);
                } catch (e) {
                    document.getElementById('addrInput').value = lat + ', ' + lng;
                }

                form.submit();
            }, (error) => {
                statusDiv.innerHTML = "Location Access Denied.";
                alert("Please allow location access to mark attendance.");
            }, { enableHighAccuracy: true });
        } else {
            alert("Geolocation not supported.");
        }
    }
</script>

<?php include 'includes/footer.php'; ?>