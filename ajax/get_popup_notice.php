<?php
require_once '../config/db.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch the latest unread pop-up notice
    $stmt = $conn->prepare("
        SELECT n.id, n.title, n.content, n.urgency 
        FROM notices n 
        WHERE n.is_popup = 1 
        AND NOT EXISTS (
            SELECT 1 FROM notice_reads nr 
            WHERE nr.notice_id = n.id AND nr.employee_id = :user_id
        )
        ORDER BY n.created_at DESC 
        LIMIT 1
    ");

    $stmt->execute(['user_id' => $user_id]);
    $notice = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($notice) {
        // Convert newlines to breaks for safety in display (though frontend might handle it)
        echo json_encode(['status' => 'found', 'notice' => $notice]);
    } else {
        echo json_encode(['status' => 'none']);
    }

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>