<?php
/**
 * Run Face Recognition Database Migration
 * This script creates the necessary tables for AWS Rekognition integration
 */

require_once 'config/db.php';

echo "Starting Face Recognition Database Migration...\n\n";

try {
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/database/face_recognition_schema.sql');

    // Remove the USE statement as we're already connected
    $sql = preg_replace('/USE\s+[^;]+;/', '', $sql);

    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function ($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );

    $successCount = 0;
    $errorCount = 0;

    foreach ($statements as $statement) {
        if (empty(trim($statement)))
            continue;

        try {
            $conn->exec($statement);
            $successCount++;

            // Extract table/operation name for logging
            if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Created table: {$matches[1]}\n";
            } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Altered table: {$matches[1]}\n";
            } else {
                echo "✓ Executed statement\n";
            }
        } catch (PDOException $e) {
            $errorCount++;
            $errorMsg = $e->getMessage();

            // Check if it's a "duplicate column" or "table exists" error (which is OK)
            if (
                strpos($errorMsg, 'Duplicate column') !== false ||
                strpos($errorMsg, 'already exists') !== false ||
                strpos($errorMsg, 'Duplicate key') !== false
            ) {
                echo "⚠ Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
            } else {
                echo "✗ Error: {$errorMsg}\n";
                echo "  Statement: " . substr($statement, 0, 100) . "...\n";
            }
        }
    }

    echo "\n" . str_repeat("=", 50) . "\n";
    echo "Migration completed!\n";
    echo "Successful operations: $successCount\n";
    echo "Errors/Skipped: $errorCount\n";
    echo str_repeat("=", 50) . "\n\n";

    // Verify tables were created
    echo "Verifying tables...\n";
    $tables = ['employee_faces', 'face_verification_logs'];

    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";

            // Show column count
            $cols = $conn->query("SHOW COLUMNS FROM $table");
            echo "  → " . $cols->rowCount() . " columns\n";
        } else {
            echo "✗ Table '$table' NOT found\n";
        }
    }

    // Check attendance table modifications
    echo "\nChecking attendance table modifications...\n";
    $cols = $conn->query("SHOW COLUMNS FROM attendance LIKE 'face_verified'");
    if ($cols->rowCount() > 0) {
        echo "✓ Column 'face_verified' added to attendance table\n";
    } else {
        echo "✗ Column 'face_verified' NOT found in attendance table\n";
    }

    echo "\n✅ Migration verification complete!\n";

} catch (Exception $e) {
    echo "\n❌ Fatal Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>