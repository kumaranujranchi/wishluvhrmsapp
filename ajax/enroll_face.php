<?php
/**
 * AJAX Endpoint: Enroll Employee Face
 * Receives face image and enrolls it in AWS Rekognition
 */

session_start();
require_once '../config/db.php';
require_once '../config/aws_config.php';

header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$employeeId = $_POST['employee_id'] ?? null;
$imageData = $_POST['image_data'] ?? null;

if (!$employeeId || !$imageData) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Verify employee exists
    $stmt = $conn->prepare("SELECT id, first_name, last_name FROM employees WHERE id = :id");
    $stmt->execute(['id' => $employeeId]);
    $employee = $stmt->fetch();

    if (!$employee) {
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }

    // Create AWS collection if it doesn't exist
    $collectionResult = createFaceCollection();
    if (!$collectionResult['success'] && !str_contains($collectionResult['message'], 'already exists')) {
        echo json_encode($collectionResult);
        exit;
    }

    // Deactivate any existing active faces for this employee
    $stmt = $conn->prepare("UPDATE employee_faces SET is_active = FALSE WHERE employee_id = :id AND is_active = TRUE");
    $stmt->execute(['id' => $employeeId]);

    // If there were active faces, delete them from AWS
    $stmt = $conn->prepare("SELECT aws_face_id FROM employee_faces WHERE employee_id = :id AND is_active = FALSE");
    $stmt->execute(['id' => $employeeId]);
    $oldFaces = $stmt->fetchAll();

    foreach ($oldFaces as $face) {
        deleteFace($face['aws_face_id']);
    }

    // Index the new face in AWS Rekognition
    $result = indexFace($imageData, $employeeId);

    if (!$result['success']) {
        echo json_encode($result);
        exit;
    }

    // Store face data in database
    $stmt = $conn->prepare("
        INSERT INTO employee_faces (employee_id, aws_face_id, aws_image_id, confidence_score, enrolled_by, is_active)
        VALUES (:emp_id, :face_id, :image_id, :confidence, :enrolled_by, TRUE)
    ");

    $stmt->execute([
        'emp_id' => $employeeId,
        'face_id' => $result['face_id'],
        'image_id' => $result['image_id'],
        'confidence' => $result['confidence'],
        'enrolled_by' => $_SESSION['user_id']
    ]);

    // Log the enrollment
    $stmt = $conn->prepare("
        INSERT INTO face_verification_logs (employee_id, verification_type, aws_face_id, confidence_score, success, ip_address, user_agent)
        VALUES (:emp_id, 'enrollment', :face_id, :confidence, TRUE, :ip, :ua)
    ");

    $stmt->execute([
        'emp_id' => $employeeId,
        'face_id' => $result['face_id'],
        'confidence' => $result['confidence'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'ua' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ]);

    echo json_encode([
        'success' => true,
        'message' => "Face enrolled successfully for {$employee['first_name']} {$employee['last_name']}",
        'confidence' => $result['confidence'],
        'face_id' => $result['face_id']
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