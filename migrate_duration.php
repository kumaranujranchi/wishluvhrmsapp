<?php
/**
 * Database Migration Script
 * Purpose: Convert existing decimal hours to minutes format
 * 
 * INSTRUCTIONS:
 * 1. Upload this file to your server
 * 2. Access it via browser: https://your-domain.com/migrate_duration.php
 * 3. Delete this file after successful migration
 */

require_once 'config/db.php';

// Security: Only allow execution once
$migration_flag_file = 'migration_duration_completed.flag';
if (file_exists($migration_flag_file)) {
    die("Migration already completed. Delete the flag file to run again.");
}

?>
<!DOCTYPE html>
<html>

<head>
    <title>Duration Data Migration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }

        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .info {
            color: blue;
        }

        pre {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <h1>Attendance Duration Migration</h1>
    <p class="info">Converting decimal hours to minutes format...</p>

    <?php
    try {
        // Start transaction
        $conn->beginTransaction();

        // Get all attendance records with total_hours in decimal format
        $stmt = $conn->prepare("
        SELECT id, employee_id, date, clock_in, clock_out, total_hours 
        FROM attendance 
        WHERE total_hours > 0 AND total_hours < 100
        ORDER BY date DESC
    ");
        $stmt->execute();
        $records = $stmt->fetchAll();

        echo "<p class='info'>Found " . count($records) . " records to convert</p>";
        echo "<pre>";

        $updated = 0;
        foreach ($records as $record) {
            $old_value = $record['total_hours'];

            // Convert decimal hours to minutes
            $total_minutes = round($old_value * 60);

            // Update the record
            $update = $conn->prepare("UPDATE attendance SET total_hours = :minutes WHERE id = :id");
            $update->execute([
                'minutes' => $total_minutes,
                'id' => $record['id']
            ]);

            $hours = floor($total_minutes / 60);
            $mins = $total_minutes % 60;

            echo sprintf(
                "ID: %d | Date: %s | Old: %.2f hr → New: %d min (%d hr %d min)\n",
                $record['id'],
                $record['date'],
                $old_value,
                $total_minutes,
                $hours,
                $mins
            );

            $updated++;
        }

        echo "</pre>";

        // Commit transaction
        $conn->commit();

        // Create flag file to prevent re-running
        file_put_contents($migration_flag_file, date('Y-m-d H:i:s'));

        echo "<p class='success'>✅ Successfully converted {$updated} records!</p>";
        echo "<p class='success'>Duration data is now in minutes format.</p>";
        echo "<p class='info'>You can now delete this migration file.</p>";

    } catch (Exception $e) {
        $conn->rollBack();
        echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>

</body>

</html>