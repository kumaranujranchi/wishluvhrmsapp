<?php
/**
 * Script to check and fix avatar paths in the database
 * This will help identify broken avatar links
 */

require_once 'config/db.php';

echo "Checking avatar paths...\n\n";

try {
    // Get all employees with avatars
    $stmt = $conn->prepare("SELECT id, first_name, last_name, employee_code, avatar FROM employees WHERE avatar IS NOT NULL AND avatar != ''");
    $stmt->execute();
    $employees = $stmt->fetchAll();

    echo "Found " . count($employees) . " employees with avatars\n\n";

    foreach ($employees as $emp) {
        echo "Employee: {$emp['first_name']} {$emp['last_name']} ({$emp['employee_code']})\n";
        echo "Avatar Path: {$emp['avatar']}\n";

        // Check if file exists
        if (file_exists($emp['avatar'])) {
            echo "✅ File exists\n";
        } else {
            echo "❌ File NOT found\n";

            // Try to find the file in uploads directory
            $filename = basename($emp['avatar']);
            $possible_paths = [
                "uploads/avatars/{$filename}",
                "uploads/{$filename}",
                "assets/avatars/{$filename}"
            ];

            foreach ($possible_paths as $path) {
                if (file_exists($path)) {
                    echo "   Found at: {$path}\n";
                    // Update database
                    $update = $conn->prepare("UPDATE employees SET avatar = :path WHERE id = :id");
                    $update->execute(['path' => $path, 'id' => $emp['id']]);
                    echo "   ✅ Updated database\n";
                    break;
                }
            }
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>