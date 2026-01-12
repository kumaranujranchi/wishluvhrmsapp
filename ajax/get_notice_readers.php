<?php
require_once '../config/db.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    exit('Unauthorized');
}

$id = $_GET['id'] ?? 0;

try {
    $stmt = $conn->prepare("
        SELECT e.first_name, e.last_name, nr.read_at 
        FROM notice_reads nr 
        JOIN employees e ON nr.employee_id = e.id 
        WHERE nr.notice_id = :id 
        ORDER BY nr.read_at DESC
    ");
    $stmt->execute(['id' => $id]);
    $readers = $stmt->fetchAll();

    if (empty($readers)) {
        echo '<div style="text-align:center; padding:1.5rem; color:#64748b;">No one has read this notice yet.</div>';
    } else {
        echo '<div style="max-height: 400px; overflow-y: auto;">';
        foreach ($readers as $r) {
            echo '<div style="padding: 10px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">';
            echo '  <div style="font-weight: 500; color: #1e293b;">' . htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) . '</div>';
            echo '  <div style="font-size: 0.75rem; color: #64748b;">' . date('d M, h:i A', strtotime($r['read_at'])) . '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
} catch (PDOException $e) {
    echo 'Error loading data: ' . $e->getMessage();
}
?>