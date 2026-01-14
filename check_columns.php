<?php
require_once 'config/db.php';
echo "<h2>Employees Table Columns</h2>";
$q = $conn->query("DESCRIBE employees");
$cols = $q->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th></tr>";
foreach ($cols as $c) {
    echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td></tr>";
}
echo "</table>";

echo "<h2>Holidays Table Columns</h2>";
try {
    $q = $conn->query("DESCRIBE holidays");
    $cols = $q->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th></tr>";
    foreach ($cols as $c) {
        echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Holidays table not found.";
}

echo "<h2>Policies Table Columns</h2>";
try {
    $q = $conn->query("DESCRIBE policies");
    $cols = $q->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th></tr>";
    foreach ($cols as $c) {
        echo "<tr><td>{$c['Field']}</td><td>{$c['Type']}</td><td>{$c['Null']}</td></tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "Policies table not found.";
}
?>