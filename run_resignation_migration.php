<?php
require_once 'config/db.php';

$sql = file_get_contents('migration_resignation_table.sql');

try {
    $conn->exec($sql);
    echo "Resignation migration executed successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>