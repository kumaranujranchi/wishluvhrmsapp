<?php
require_once 'config/db.php';
try {
    $stmt = $conn->query("DESCRIBE monthly_payroll");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($cols);
    echo "</pre>";

    $stmt = $conn->query("DESCRIBE attendance");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Attendance</h3><pre>";
    print_r($cols);
    echo "</pre>";

    $stmt = $conn->query("DESCRIBE holidays");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<h3>Holidays</h3><pre>";
    print_r($cols);
    echo "</pre>";
} catch (Exception $e) {
    echo $e->getMessage();
}
