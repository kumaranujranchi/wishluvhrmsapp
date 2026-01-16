<?php
require_once 'config/db.php';
header('Content-Type: text/plain');

echo "Debugging Shalu Singh's Manager...\n\n";

// 1. Find Shalu
$stmt = $conn->prepare("SELECT * FROM employees WHERE first_name LIKE 'Shalu%' LIMIT 1");
$stmt->execute();
$shalu = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shalu) {
    echo "Shalu Not Found in DB!\n";
    exit;
}

echo "Employee: " . $shalu['first_name'] . " " . $shalu['last_name'] . " (ID: " . $shalu['id'] . ")\n";
echo "Reporting Manager ID: " . var_export($shalu['reporting_manager_id'], true) . "\n";

// 2. Find Manager
if (!empty($shalu['reporting_manager_id'])) {
    $m_stmt = $conn->prepare("SELECT * FROM employees WHERE id = :mid");
    $m_stmt->execute(['mid' => $shalu['reporting_manager_id']]);
    $manager = $m_stmt->fetch(PDO::FETCH_ASSOC);

    if ($manager) {
        echo "Manager Found in DB: " . $manager['first_name'] . " " . $manager['last_name'] . " (ID: " . $manager['id'] . ")\n";
    } else {
        echo "Manager ID " . $shalu['reporting_manager_id'] . " exists in column, BUT no employee found with this ID (Deleted?).\n";
    }
} else {
    echo "Reporting Manager ID is NULL/Empty.\n";
}
?>