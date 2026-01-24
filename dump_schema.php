<?php
require_once 'config/db.php';
$output = "";
try {
    $tables = ['attendance', 'holidays', 'monthly_payroll', 'employees'];
    foreach ($tables as $t) {
        $output .= "Table: $t\n";
        $stmt = $conn->query("DESCRIBE $t");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            $output .= "  " . $c['Field'] . " (" . $c['Type'] . ")\n";
        }
        $output .= "\n";
    }
} catch (Exception $e) {
    $output .= "Error: " . $e->getMessage();
}
file_put_contents('db_schema_output.txt', $output);
echo "Done";
