<?php
require_once 'config/db.php';

try {
    $sql = "ALTER TABLE notices ADD COLUMN is_popup TINYINT(1) DEFAULT 0";
    $conn->exec($sql);
    echo "Column 'is_popup' added successfully to 'notices' table.";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "Column 'is_popup' already exists.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>