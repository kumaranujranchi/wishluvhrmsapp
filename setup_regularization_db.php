<?php
require_once 'config/db.php';

echo "=== Attendance Regularization - Database Setup ===\n\n";

try {
    // Read SQL file
    $sql = file_get_contents('database/regularization_schema.sql');

    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));

    $success_count = 0;
    $error_count = 0;

    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }

        try {
            $conn->exec($statement);
            $success_count++;
            echo "✓ Executed successfully\n";
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (
                strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Duplicate') !== false
            ) {
                echo "⚠ Already exists (skipped)\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
                $error_count++;
            }
        }
    }

    echo "\n=== Summary ===\n";
    echo "Successful: $success_count\n";
    echo "Errors: $error_count\n";
    echo "\n✅ Database setup complete!\n";

} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
}
?>