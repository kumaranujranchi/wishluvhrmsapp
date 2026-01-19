<?php
/**
 * Web-Based Face Recognition Database Migration
 * Access this file via browser to run the migration on the remote database
 * URL: https://your-domain.com/migrate_face_db.php
 * 
 * IMPORTANT: Delete this file after running the migration for security
 */

// Only allow admin access
session_start();
require_once 'config/db.php';

// Simple password protection (change this password!)
$MIGRATION_PASSWORD = 'hrms_migrate_2026';

$isAuthenticated = false;
$migrationRun = false;
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['password']) && $_POST['password'] === $MIGRATION_PASSWORD) {
        $isAuthenticated = true;

        if (isset($_POST['run_migration'])) {
            $migrationRun = true;

            try {
                // Read the SQL file
                $sql = file_get_contents(__DIR__ . '/database/face_recognition_schema.sql');

                // Remove the USE statement
                $sql = preg_replace('/USE\s+[^;]+;/', '', $sql);

                // Split into individual statements
                $statements = array_filter(
                    array_map('trim', explode(';', $sql)),
                    function ($stmt) {
                        return !empty($stmt) && !preg_match('/^--/', $stmt);
                    }
                );

                foreach ($statements as $statement) {
                    if (empty(trim($statement)))
                        continue;

                    try {
                        $conn->exec($statement);

                        if (preg_match('/CREATE TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                            $results[] = ['type' => 'success', 'message' => "Created table: {$matches[1]}"];
                        } elseif (preg_match('/ALTER TABLE.*?`?(\w+)`?/i', $statement, $matches)) {
                            $results[] = ['type' => 'success', 'message' => "Altered table: {$matches[1]}"];
                        } else {
                            $results[] = ['type' => 'success', 'message' => 'Executed statement'];
                        }
                    } catch (PDOException $e) {
                        $errorMsg = $e->getMessage();

                        if (
                            strpos($errorMsg, 'Duplicate column') !== false ||
                            strpos($errorMsg, 'already exists') !== false ||
                            strpos($errorMsg, 'Duplicate key') !== false
                        ) {
                            $results[] = ['type' => 'warning', 'message' => 'Skipped (already exists): ' . substr($statement, 0, 50) . '...'];
                        } else {
                            $results[] = ['type' => 'error', 'message' => $errorMsg];
                        }
                    }
                }

                // Verify tables
                $tables = ['employee_faces', 'face_verification_logs'];
                foreach ($tables as $table) {
                    $stmt = $conn->query("SHOW TABLES LIKE '$table'");
                    if ($stmt->rowCount() > 0) {
                        $cols = $conn->query("SHOW COLUMNS FROM $table");
                        $results[] = ['type' => 'info', 'message' => "✓ Table '$table' exists with " . $cols->rowCount() . " columns"];
                    } else {
                        $results[] = ['type' => 'error', 'message' => "✗ Table '$table' NOT found"];
                    }
                }

                // Check attendance modifications
                $cols = $conn->query("SHOW COLUMNS FROM attendance LIKE 'face_verified'");
                if ($cols->rowCount() > 0) {
                    $results[] = ['type' => 'info', 'message' => "✓ Column 'face_verified' added to attendance table"];
                } else {
                    $results[] = ['type' => 'error', 'message' => "✗ Column 'face_verified' NOT found"];
                }

            } catch (Exception $e) {
                $results[] = ['type' => 'error', 'message' => 'Fatal Error: ' . $e->getMessage()];
            }
        }
    } else {
        $results[] = ['type' => 'error', 'message' => 'Invalid password'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Face Recognition Database Migration</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 800px;
            width: 100%;
            padding: 40px;
        }

        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 2rem;
        }

        .subtitle {
            color: #64748b;
            margin-bottom: 30px;
            font-size: 0.95rem;
        }

        .warning {
            background: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            color: #92400e;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #1e293b;
        }

        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .results {
            margin-top: 30px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 10px;
        }

        .result-item {
            padding: 10px;
            margin-bottom: 8px;
            border-radius: 6px;
            font-size: 0.9rem;
        }

        .success {
            background: #dcfce7;
            color: #166534;
            border-left: 4px solid #22c55e;
        }

        .error {
            background: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .warning {
            background: #fef9c3;
            color: #854d0e;
            border-left: 4px solid #f59e0b;
        }

        .info {
            background: #dbeafe;
            color: #1e40af;
            border-left: 4px solid #3b82f6;
        }

        .delete-notice {
            margin-top: 30px;
            padding: 15px;
            background: #fee2e2;
            border-radius: 10px;
            color: #991b1b;
            font-weight: 600;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>🔐 Face Recognition Migration</h1>
        <p class="subtitle">Database schema setup for AWS Rekognition integration</p>

        <div class="warning">
            <strong>⚠️ Security Notice:</strong> This migration script should only be run once.
            Delete this file (<code>migrate_face_db.php</code>) after successful migration.
        </div>

        <?php if (!$isAuthenticated): ?>
            <form method="POST">
                <div class="form-group">
                    <label for="password">Migration Password:</label>
                    <input type="password" id="password" name="password" required placeholder="Enter migration password">
                </div>
                <button type="submit" name="run_migration" value="1" class="btn">
                    🚀 Run Migration
                </button>
            </form>
        <?php endif; ?>

        <?php if ($migrationRun && !empty($results)): ?>
            <div class="results">
                <h2 style="margin-bottom: 15px; color: #1e293b;">Migration Results</h2>
                <?php foreach ($results as $result): ?>
                    <div class="result-item <?= $result['type'] ?>">
                        <?= htmlspecialchars($result['message']) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="delete-notice">
                🗑️ <strong>IMPORTANT:</strong> Migration complete! Please delete this file now for security:
                <code>migrate_face_db.php</code>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>