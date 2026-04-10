<?php
// Secure log viewer for debugging purposes
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'Admin') {
    die("Unauthorized access.");
}

$logs = [
    'Face Verify Error' => 'logs/face_verify_error.log',
    'Face Enroll Error' => 'logs/face_enroll_error.log'
];

echo "<h1>System Diagnostic Logs</h1>";
echo "<p>Checking for recent errors...</p>";

foreach ($logs as $name => $path) {
    echo "<h2>$name</h2>";
    $fullPath = __DIR__ . '/' . $path;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        echo "<pre style='background: #f4f4f4; padding: 15px; border-radius: 8px; border: 1px solid #ddd; max-height: 300px; overflow: auto;'>" . htmlspecialchars($content ?: 'Log is empty.') . "</pre>";
    } else {
        echo "<p style='color: #666;'>Log file not found at $path. No errors recorded yet.</p>";
    }
}

echo "<hr><button onclick='location.reload()'>Refresh Logs</button>";
?>
