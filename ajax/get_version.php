<?php
header('Content-Type: application/json');
require_once '../config/version.php';
echo json_encode(['version' => $ASSET_VERSION]);
?>