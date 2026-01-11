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
            $status = (strtotime($current_time) > strtotime('10:00:00')) ? 'Late' : 'Present';

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
            $message = "<div class='alert success'>Checked In Successfully at $current_time</div>";
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
            $message = "<div class='alert success'>Checked Out Successfully at $current_time. Total Hours: $hours</div>";
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

<?php
// ... (Previous Code for Check In/Out Logic is fine, just updating History Fetching part) ...

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
    if ($h['status'] == 'Present' || $h['status'] == 'Late') $present_days++;
    if ($h['status'] == 'Late') $late_days++;
    $total_work_hours += $h['total_hours'];
}
?>

    <!-- ... (Hero Section Remains) ... -->
    <!-- Insert Filter Form before Table -->
    
    <div class="card" style="margin-top: 2rem;">
        <div class="card-header" style="justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <h3>My Attendance History</h3>
            <form method="GET" style="display:flex; gap:10px; align-items:center;">
                <select name="month" class="form-control" style="width: auto;">
                    <?php for($m=1; $m<=12; $m++): ?>
                        <option value="<?= $m ?>" <?= $m == $filter_month ? 'selected' : '' ?>>
                            <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <select name="year" class="form-control" style="width: auto;">
                    <?php for($y=date('Y'); $y>=2024; $y--): ?>
                        <option value="<?= $y ?>" <?= $y == $filter_year ? 'selected' : '' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-primary" style="padding: 0.5rem 1rem;">View</button>
            </form>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; padding: 1.5rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
            <div style="text-align: center;">
                <div style="font-size: 0.9rem; color: #64748b;">Days Present</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #10b981;"><?= $present_days ?></div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 0.9rem; color: #64748b;">Late Days</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #f59e0b;"><?= $late_days ?></div>
            </div>
            <div style="text-align: center;">
                <div style="font-size: 0.9rem; color: #64748b;">Total Hours</div>
                <div style="font-size: 1.5rem; font-weight: 700; color: #3b82f6;"><?= $total_work_hours ?></div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Clock In</th>
                        <th>Clock Out</th>
                        <th>Duration</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr>
                            <td><?= date('d M, D', strtotime($row['date'])) ?></td>
                            <td>
                                <?php 
                                    $statusColor = match($row['status']) {
                                        'Present' => '#dcfce7',
                                        'Late' => '#fef9c3',
                                        default => '#f1f5f9'
                                    };
                                    $statusText = match($row['status']) {
                                        'Present' => '#166534',
                                        'Late' => '#854d0e',
                                        default => '#475569'
                                    };
                                ?>
                                <span class="badge" style="background:<?= $statusColor ?>; color:<?= $statusText ?>;"><?= $row['status'] ?></span>
                            </td>
                            <td><?= $row['clock_in'] ? date('h:i A', strtotime($row['clock_in'])) : '-' ?></td>
                            <td><?= $row['clock_out'] ? date('h:i A', strtotime($row['clock_out'])) : '-' ?></td>
                            <td style="font-weight: 600;"><?= $row['total_hours'] ? $row['total_hours'] . ' hr' : '-' ?></td>
                            <td style="font-size:0.8rem; max-width:200px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="<?= htmlspecialchars($row['clock_in_address']) ?>">
                                <?= htmlspecialchars($row['clock_in_address'] ?? '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if(empty($history)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:1.5rem; color: #94a3b8;">No records found for this month.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // Live Clock
    function updateClock() {
        const now = new Date();
        document.getElementById('liveClock').innerText = now.toLocaleTimeString();
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Geolocation Logic
    function getLocationAndSubmit(type) {
        const statusDiv = document.getElementById('locationStatus');
        const form = document.getElementById('attendanceForm');
        
        // Update UI state
        statusDiv.innerHTML = '<span class="spin" style="display:inline-block;">⌛</span> Fetching location...';
        
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(success, error, {
                 enableHighAccuracy: true
            });
        } else {
            statusDiv.innerHTML = "Geolocation is not supported by this browser.";
            alert("Geolocation is not support. Attendance cannot be marked.");
        }

        async function success(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            document.getElementById('actionInput').value = type;
            document.getElementById('latInput').value = lat;
            document.getElementById('lngInput').value = lng;
            
            statusDiv.innerHTML = 'Location found. Fetching address...';
            
            // Reverse Geocoding (Nominatim OpenStreetMap)
            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                const data = await response.json();
                
                if(data && data.display_name) {
                    document.getElementById('addrInput').value = data.display_name;
                    statusDiv.innerHTML = 'Address found: ' + data.display_name;
                } else {
                    document.getElementById('addrInput').value = lat + ', ' + lng;
                }
            } catch (e) {
                console.log("Geocoding failed", e);
                document.getElementById('addrInput').value = lat + ', ' + lng;
            }

            // Submit Form
            statusDiv.innerHTML = 'Submitting...';
            form.submit();
        }

        function error() {
            statusDiv.innerHTML = "Unable to retrieve your location.";
            alert("Location access is required to mark attendance. Please allow location access.");
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
