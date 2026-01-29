<?php
/**
 * Ajax Endpoint: Kiosk Verify
 * Handles face verification for Kiosk mode (One-to-Many Search)
 */

session_start();
require_once '../config/db.php';
require_once '../config/aws_config.php';

header('Content-Type: application/json');

// Security Check: Must be logged in as Kiosk Admin
// Note: We check if ANY user is logged in, but ideally should verify it's the Kiosk user.
// Allowing any admin to test this too.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}

$imageData = $_POST['image_data'] ?? null;
$lat = $_POST['latitude'] ?? null;
$lng = $_POST['longitude'] ?? null;

if (!$imageData) {
    echo json_encode(['success' => false, 'message' => 'Missing Data']);
    exit;
}

try {
    // 1. Search Face in AWS
    $searchResult = searchFaceByImage($imageData, 85.0); // High confidence for kiosk

    if (!$searchResult['success']) {
        echo json_encode($searchResult); // Returns 'no_match' => true if face not found
        exit;
    }

    $employeeId = $searchResult['employee_id'];
    $confidence = $searchResult['confidence'];

    // 2. Fetch Employee Details
    $stmt = $conn->prepare("SELECT id, first_name, last_name, avatar, shift_start_time FROM employees WHERE id = :id AND status = 'Active'");
    $stmt->execute(['id' => $employeeId]);
    $employee = $stmt->fetch();

    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee Record Not Found']);
        exit;
    }

    // 3. Determine Clock In or Clock Out
    $date = date('Y-m-d');
    $time = date('H:i:s');

    // Check existing attendance for TODAY
    $attStmt = $conn->prepare("SELECT id, clock_in, clock_out FROM attendance WHERE employee_id = :uid AND date = :date");
    $attStmt->execute(['uid' => $employeeId, 'date' => $date]);
    $attendance = $attStmt->fetch();

    $message = "";
    $type = "";

    if (!$attendance) {
        // CLOCK IN
        $status = ($time > ($employee['shift_start_time'] ?? '10:00:00')) ? 'Late' : 'On Time';

        $ins = $conn->prepare("INSERT INTO attendance (employee_id, date, clock_in, status, face_verified, face_confidence, clock_in_lat, clock_in_lng, verification_method) VALUES (:uid, :date, :time, :status, TRUE, :conf, :lat, :lng, 'kiosk')");
        $ins->execute([
            'uid' => $employeeId,
            'date' => $date,
            'time' => $time,
            'status' => $status,
            'conf' => $confidence,
            'lat' => $lat,
            'lng' => $lng
        ]);

        $message = "Clocked IN at " . date('h:i A');
        $type = "in";

    } elseif ($attendance['clock_in'] && empty($attendance['clock_out'])) {
        // CLOCK OUT (Prevent double punch within 5 mins)
        $lastPunch = strtotime($attendance['clock_in']);
        if (time() - $lastPunch < 300) { // 5 minutes buffer
            echo json_encode(['success' => true, 'employee_name' => $employee['first_name'], 'avatar' => $employee['avatar'], 'message' => 'Already Punched In Just Now']);
            exit;
        }

        $totalMinutes = round((strtotime($time) - strtotime($attendance['clock_in'])) / 60);

        $upd = $conn->prepare("UPDATE attendance SET clock_out = :time, total_hours = :mins, clock_out_lat = :lat, clock_out_lng = :lng WHERE id = :id");
        $upd->execute([
            'time' => $time,
            'mins' => $totalMinutes,
            'lat' => $lat,
            'lng' => $lng,
            'id' => $attendance['id']
        ]);

        $message = "Clocked OUT at " . date('h:i A');
        $type = "out";
    } else {
        // Already done for the day
        echo json_encode([
            'success' => true,
            'employee_name' => $employee['first_name'],
            'avatar' => $employee['avatar'],
            'message' => 'Your Punches are Complete for today.',
            'type' => 'complete'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'employee_name' => $employee['first_name'] . ' ' . $employee['last_name'],
        'avatar' => $employee['avatar'],
        'message' => $message,
        'type' => $type
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'System Error: ' . $e->getMessage()]);
}
?>