<?php
require_once 'config/db.php';

echo "=== Attendance Status Correction Utility ===\n\n";

try {
    // 1. Identify records that have clock_in but are marked as 'Absent'
    $check_sql = "SELECT COUNT(*) FROM attendance WHERE status = 'Absent' AND clock_in IS NOT NULL";
    $count = $conn->query($check_sql)->fetchColumn();

    echo "Found $count records with status 'Absent' but containing a Clock-In time.\n";

    if ($count > 0) {
        // 2. Update these records to 'On Time' (safest bet)
        // We use 'On Time' because it's the newer status preferred for app punches
        $update_sql = "UPDATE attendance SET status = 'On Time' WHERE status = 'Absent' AND clock_in IS NOT NULL";
        $affected = $conn->exec($update_sql);

        echo "Successfully updated $affected records to 'On Time'.\n";
    } else {
        echo "No records need correction.\n";
    }

    echo "\n✅ Done!\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>