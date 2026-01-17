<?php
/**
 * One-time script to convert existing decimal hours to minutes in the database
 * Run this once to fix all existing attendance records
 */

require_once 'config/db.php';

echo "Starting duration data conversion...\n\n";

try {
    // Get all attendance records with total_hours
    $stmt = $conn->prepare("SELECT id, total_hours FROM attendance WHERE total_hours > 0 AND total_hours < 100");
    $stmt->execute();
    $records = $stmt->fetchAll();

    echo "Found " . count($records) . " records to convert\n\n";

    $updated = 0;

    foreach ($records as $record) {
        $old_value = $record['total_hours'];

        // If value is less than 24, it's likely in decimal hours format
        // Convert decimal hours to minutes
        if ($old_value < 24) {
            $total_minutes = round($old_value * 60);

            // Update the record
            $update = $conn->prepare("UPDATE attendance SET total_hours = :minutes WHERE id = :id");
            $update->execute([
                'minutes' => $total_minutes,
                'id' => $record['id']
            ]);

            $hours = floor($total_minutes / 60);
            $mins = $total_minutes % 60;

            echo "Record ID {$record['id']}: {$old_value} hr → {$total_minutes} min ({$hours} hr {$mins} min)\n";
            $updated++;
        }
    }

    echo "\n✅ Successfully converted {$updated} records!\n";
    echo "Duration data is now in minutes format.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>