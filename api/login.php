<?php
header('Content-Type: application/json');
require_once '../config/db.php';

// Allow CORS if needed (for development)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
    exit;
}

// Get JSON Input
$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Email and Password are required']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT e.id, e.first_name, e.last_name, e.email, e.password, e.avatar, e.role, d.name as designation 
        FROM employees e 
        LEFT JOIN designations d ON e.designation_id = d.id 
        WHERE (e.email = :email OR e.employee_code = :email) AND e.status = 'Active'
    ");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Remove password from response
        unset($user['password']);

        // Add full avatar URL if relative
        if (!empty($user['avatar']) && !filter_var($user['avatar'], FILTER_VALIDATE_URL)) {
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            // Assuming the API is in /api/ and avatar is relative to root
            $user['avatar'] = "$protocol://$host/hrms/" . ltrim($user['avatar'], '/');
        }

        echo json_encode([
            'success' => true,
            'message' => 'Login Successful',
            'user' => $user
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server Error: ' . $e->getMessage()]);
}
?>