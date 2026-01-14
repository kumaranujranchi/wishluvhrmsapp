<?php
// Load database connection
require_once 'config/db.php';

echo "<h2>Migration: Add Gender Column</h2>";

try {
    // Read SQL file
    $sql = file_get_contents('migration_add_gender.sql');

    // Execute SQL
    $conn->exec($sql);
    echo "<p style='color:green;'>Success: Gender column added to employees table.</p>";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), "Duplicate column name") !== false) {
        echo "<p style='color:orange;'>Notice: Column 'gender' already exists.</p>";
    } else {
        echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
    }
}
?>