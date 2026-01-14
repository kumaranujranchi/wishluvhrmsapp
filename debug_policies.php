<?php
require_once 'config/db.php';
header('Content-Type: text/plain');

try {
    $q = $conn->query("SELECT id, title, slug, LEFT(content, 50) as content_preview, created_at, updated_at FROM policies");
    $rows = $q->fetchAll(PDO::FETCH_ASSOC);
    print_r($rows);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>