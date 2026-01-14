<?php
require_once 'config/db.php';

echo "<h2>Migration: Fix Policies Table Schema</h2>";

try {
    // 1. Add/Modify updated_at to have CURRENT_TIMESTAMP and ON UPDATE CURRENT_TIMESTAMP
    // First, let's check if we can simply alter the column behavior
    $sql = "ALTER TABLE policies 
            MODIFY COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            MODIFY COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";

    $conn->exec($sql);
    echo "<p style='color:green;'>Success: policies table schema updated with auto-timestamps.</p>";

    // 2. Initialize any NULL timestamps to now
    $conn->exec("UPDATE policies SET updated_at = NOW() WHERE updated_at IS NULL OR updated_at = '0000-00-00 00:00:00'");
    echo "<p style='color:green;'>Success: Initialized NULL timestamps.</p>";

} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>