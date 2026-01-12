<?php
require_once 'config/db.php';

$sql = file_get_contents('migration_leave_approval.sql');

try {
    $conn->exec($sql);
    echo "Migration executed successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>