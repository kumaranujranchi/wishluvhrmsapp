<?php
/**
 * AJAX Endpoint: Verify Face and Process Attendance Punch
 * Verifies employee face and creates attendance record
 */

session_start();
require_once '../config/db.php';
require_once '../config/aws_config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$imageData = $_POST['image_data'] ?? null;
$action = $_POST['action'] ?? null; // 'clock_in' or 'clock_out'
$latitude = $_POST['latitude'] ?? null;
$longitude = $_POST['longitude'] ?? null;
$address = $_POST['address'] ?? 'Location not available';
$locationId = $_POST['location_id'] ?? null;
$outOfRange = $_POST['out_of_range'] ?? 0;
$reason = $_POST['reason'] ?? null;

if (!$imageData || !$action || !in_array($action, ['clock_in', 'clock_out'])) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid required parameters (Action: ' . json_encode($action) . ')']);
    exit;
}

$userId = $_SESSION['user_id'];
$date = date('Y-m-d');
$currentTime = date('H:i:s');

try {
    // Verify face using AWS Rekognition
    $verificationResult = searchFaceByImage($imageData, 80.0); // 80% confidence threshold

    if (!$verificationResult['success']) {
        // Log failed verification
        $stmt = $conn->prepare("
            INSERT INTO face_verification_logs (employee_id, verification_type, success, failure_reason, ip_address, user_agent)
            VALUES (:emp_id, :type, FALSE, :reason, :ip, :ua)
        ");

        $stmt->execute([
            'emp_id' => $userId,
            'type' => $action,
            'reason' => $verificationResult['message'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        echo json_encode($verificationResult);
        exit;
    }

    // Permission Check: Check if user is allowed to punch from outside if marked as out_of_range
    if ($outOfRange) {
        $permStmt = $conn->prepare("SELECT allow_outside_punch FROM employees WHERE id = :uid");
        $permStmt->execute(['uid' => $verificationResult['employee_id']]); // Use matched ID or Session ID (should match)
        $allowOutside = $permStmt->fetchColumn();

        if (!$allowOutside) {
            echo json_encode([
                'success' => false,
                'message' => 'Permission Denied: You are not allowed to mark attendance from outside the permitted location.'
            ]);
            exit;
        }
    }

    // Check if matched employee is the logged-in user
    if ($verificationResult['employee_id'] != $userId) {
        // Log failed verification
        $stmt = $conn->prepare("
            INSERT INTO face_verification_logs (employee_id, verification_type, aws_face_id, confidence_score, success, failure_reason, ip_address, user_agent)
            VALUES (:emp_id, :type, :face_id, :confidence, FALSE, :reason, :ip, :ua)
        ");

        $stmt->execute([
            'emp_id' => $userId,
            'type' => $action,
            'face_id' => $verificationResult['face_id'],
            'confidence' => $verificationResult['confidence'],
            'reason' => 'Face matched different employee (ID: ' . $verificationResult['employee_id'] . ')',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);

        echo json_encode([
            'success' => false,
            'message' => 'Face verification failed. The detected face does not match your enrolled face.'
        ]);
        exit;
    }

    // Face verified successfully, process attendance
    $attendanceId = null;

    if ($action === 'clock_in') {
        // Check if already clocked in
        $check = $conn->prepare("SELECT id FROM attendance WHERE employee_id = :uid AND date = :date");
        $check->execute(['uid' => $userId, 'date' => $date]);

        if ($check->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Already clocked in today']);
            exit;
        }

        // Determine status (Late if after 10:00 AM)
        $empStmt = $conn->prepare("SELECT shift_start_time FROM employees WHERE id = :uid");
        $empStmt->execute(['uid' => $userId]);
        $shiftStart = $empStmt->fetchColumn() ?: '10:00:00';
        // Compare only hours and minutes to allow the 10:00 AM minute to be "On Time"
        $status = (date('H:i', strtotime($currentTime)) > date('H:i', strtotime($shiftStart))) ? 'Late' : 'On Time';

        // Insert attendance record
        $stmt = $conn->prepare("
            INSERT INTO attendance (
                employee_id, date, clock_in, status, face_verified, face_confidence, verification_method,
                clock_in_lat, clock_in_lng, clock_in_address, location_id, out_of_range, out_of_range_reason
            ) VALUES (
                :uid, :date, :time, :status, TRUE, :confidence, 'face',
                :lat, :lng, :addr, :loc_id, :oor, :reason
            )
        ");

        $stmt->execute([
            'uid' => $userId,
            'date' => $date,
            'time' => $currentTime,
            'status' => $status,
            'confidence' => $verificationResult['confidence'],
            'lat' => $latitude,
            'lng' => $longitude,
            'addr' => $address,
            'loc_id' => $locationId,
            'oor' => $outOfRange,
            'reason' => $reason
        ]);

        $attendanceId = $conn->lastInsertId();
        $message = "Face verified! Checked in successfully at " . date('h:i A', strtotime($currentTime));

    } elseif ($action === 'clock_out') {
        // Get today's attendance record
        $stmt = $conn->prepare("SELECT id, clock_in FROM attendance WHERE employee_id = :uid AND date = :date");
        $stmt->execute(['uid' => $userId, 'date' => $date]);
        $record = $stmt->fetch();

        if (!$record) {
            echo json_encode(['success' => false, 'message' => 'No clock-in record found for today']);
            exit;
        }

        if (!empty($record['clock_out'])) {
            echo json_encode(['success' => false, 'message' => 'Already clocked out today']);
            exit;
        }

        // Calculate total hours
        $clockInTime = strtotime($record['clock_in']);
        $clockOutTime = strtotime($currentTime);
        $totalMinutes = round(abs($clockOutTime - $clockInTime) / 60);

        // Update attendance record
        $stmt = $conn->prepare("
            UPDATE attendance SET 
                clock_out = :time,
                total_hours = :total_minutes,
                face_confidence = :confidence,
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
            WHERE id = :id
        ");

        $stmt->execute([
            'time' => $currentTime,
            'total_minutes' => $totalMinutes,
            'confidence' => $verificationResult['confidence'],
            'lat' => $latitude,
            'lng' => $longitude,
            'addr' => $address,
            'loc_id' => $locationId ?: null,
            'oor' => $outOfRange,
            'reason' => $reason,
            'id' => $record['id']
        ]);

        $attendanceId = $record['id'];
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;
        $message = "Face verified! Checked out successfully at " . date('h:i A', strtotime($currentTime)) .
            ". Total: {$hours}h {$minutes}m";
    }

    // Log successful verification
    $stmt = $conn->prepare("
        INSERT INTO face_verification_logs (
            employee_id, attendance_id, verification_type, aws_face_id, confidence_score, success, ip_address, user_agent
        ) VALUES (
            :emp_id, :att_id, :type, :face_id, :confidence, TRUE, :ip, :ua
        )
    ");

    $stmt->execute([
        'emp_id' => $userId,
        'att_id' => $attendanceId,
        'type' => $action,
        'face_id' => $verificationResult['face_id'],
        'confidence' => $verificationResult['confidence'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);

    echo json_encode([
        'success' => true,
        'message' => $message,
        'confidence' => $verificationResult['confidence'],
        'attendance_id' => $attendanceId
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>