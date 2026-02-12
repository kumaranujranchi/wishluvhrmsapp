<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/aws_config.php'; // For S3 upload/Rekognition if needed later, or just simple file save

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
    exit;
}

// 0. Enforce Mobile App Usage via User-Agent Check
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Check if User-Agent contains our custom string "WishluvMobileApp"
if (strpos($user_agent, 'WishluvMobileApp') === false) {
    echo json_encode(['success' => false, 'message' => 'Attendance can only be marked from the official Mobile App.']);
    exit;
}

// Get JSON Input
$input = json_decode(file_get_contents('php://input'), true);

$user_id = $input['user_id'] ?? null;
$lat = $input['latitude'] ?? null;
$lng = $input['longitude'] ?? null;
$file_data = $input['image_data'] ?? null; // Base64
$mock_detected = $input['mock_detected'] ?? false;
$usb_debug = $input['usb_debug'] ?? false;

if (!$user_id || !$lat || !$lng || !$file_data) {
    echo json_encode(['success' => false, 'message' => 'Missing Data']);
    exit;
}

// 1. Strict Security Check
if ($mock_detected || $usb_debug) {
    echo json_encode(['success' => false, 'message' => 'Security Violation: Mock Location or USB Debugging Detected. Attendance Rejected.']);
    exit;
}

try {
    // 2. Fetch Assigned Locations for Geofencing
    $loc_stmt = $conn->prepare("
        SELECT al.* 
        FROM attendance_locations al
        JOIN employee_locations el ON al.id = el.location_id
        WHERE el.employee_id = :uid AND al.is_active = 1
    ");
    $loc_stmt->execute(['uid' => $user_id]);
    $assigned_locations = $loc_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Permission Check (Outside Punch)
    $perm_stmt = $conn->prepare("SELECT allow_outside_punch FROM employees WHERE id = :uid");
    $perm_stmt->execute(['uid' => $user_id]);
    $allow_outside_punch = $perm_stmt->fetchColumn();

    // 4. Calculate Distance
    $matched_location_id = null;
    $is_out_of_range = true;
    $min_distance = 999999;

    foreach ($assigned_locations as $loc) {
        $distance = getDistance($lat, $lng, $loc['latitude'], $loc['longitude']);
        if ($distance <= $loc['radius']) {
            $is_out_of_range = false;
            $matched_location_id = $loc['id'];
            break;
        }
    }

    if ($is_out_of_range && !$allow_outside_punch) {
        echo json_encode(['success' => false, 'message' => 'You are outside the office location.']);
        exit;
    }

    // 5. Handle Image Upload (Save Base64 to file)
    $upload_dir = '../uploads/attendance/';
    if (!file_exists($upload_dir))
        mkdir($upload_dir, 0777, true);

    // Extract base64
    if (preg_match('/^data:image\/(\w+);base64,/', $file_data, $type)) {
        $file_data = substr($file_data, strpos($file_data, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif
        $file_data = base64_decode($file_data);
        if ($file_data === false) {
            echo json_encode(['success' => false, 'message' => 'Base64 decode failed']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid image data']);
        exit;
    }

    $file_name = 'att_' . $user_id . '_' . time() . '.' . $type;
    file_put_contents($upload_dir . $file_name, $file_data);

    // Relative path for DB
    $image_path = 'uploads/attendance/' . $file_name;

    // 6. Record Attendance (Logic from attendance_view.php simplified)
    $date = date('Y-m-d');
    $time = date('H:i:s');

    // Check existing
    $check = $conn->prepare("SELECT * FROM attendance WHERE employee_id = :uid AND date = :date");
    $check->execute(['uid' => $user_id, 'date' => $date]);
    $existing = $check->fetch();

    if (!$existing) {
        // PUNCH IN
        // Status Check (Late)
        $emp_q = $conn->prepare("SELECT shift_start_time FROM employees WHERE id = :uid");
        $emp_q->execute(['uid' => $user_id]);
        $shift_start = $emp_q->fetchColumn() ?: '10:00:00';
        $grace_time = date('H:i:s', strtotime($shift_start . ' + 6 minutes'));
        $status = ($time < $grace_time) ? 'On Time' : 'Late';

        $sql = "INSERT INTO attendance (employee_id, date, clock_in, status, clock_in_lat, clock_in_lng, location_id, out_of_range, verification_method, image_path) 
                VALUES (:uid, :date, :time, :status, :lat, :lng, :loc_id, :oor, 'mobile_app', :img)";

        $insert = $conn->prepare($sql);
        $insert->execute([
            'uid' => $user_id,
            'date' => $date,
            'time' => $time,
            'status' => $status,
            'lat' => $lat,
            'lng' => $lng,
            'loc_id' => $matched_location_id,
            'oor' => $is_out_of_range ? 1 : 0,
            'img' => $image_path
        ]);

        echo json_encode(['success' => true, 'message' => 'Punch In Successful', 'type' => 'in', 'time' => date('h:i A')]);

    } elseif (empty($existing['clock_out'])) {
        // PUNCH OUT
        // Check 5 min buffer
        $last_punch = strtotime($existing['clock_in']);
        if (time() - $last_punch < 300) {
            echo json_encode(['success' => false, 'message' => 'Already punched in just now. Wait 5 minutes.']);
            exit;
        }

        $calc_in = strtotime($existing['clock_in']);
        $calc_out = strtotime($time);
        $mins = round(abs($calc_out - $calc_in) / 60);

        $sql = "UPDATE attendance SET clock_out = :time, total_hours = :mins, clock_out_lat = :lat, clock_out_lng = :lng WHERE id = :id";
        $upd = $conn->prepare($sql);
        $upd->execute([
            'time' => $time,
            'mins' => $mins,
            'lat' => $lat,
            'lng' => $lng,
            'id' => $existing['id']
        ]);

        echo json_encode(['success' => true, 'message' => 'Punch Out Successful', 'type' => 'out', 'time' => date('h:i A')]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Attendance already completed for today']);
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}

// Distance Function (Haversine)
function getDistance($lat1, $lon1, $lat2, $lon2)
{
    if (!$lat2 || !$lon2)
        return 999999;
    $R = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}
?>