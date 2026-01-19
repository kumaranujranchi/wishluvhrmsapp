<?php
/**
 * Debug AWS Credentials
 * Upload this file to your public_html folder and visit it in browser
 * Delete immediately after use!
 */

require_once 'config/aws_config.php';

echo "<h1>AWS Credential Debugger</h1>";

echo "<h2>1. Environment Variables Check</h2>";
$key = getenv('AWS_ACCESS_KEY_ID');
$secret = getenv('AWS_SECRET_ACCESS_KEY');
$region = getenv('AWS_REGION');

echo "<strong>AWS_ACCESS_KEY_ID:</strong> " . ($key ? "Loaded (" . substr($key, 0, 4) . "..." . substr($key, -4) . ")" : "<span style='color:red'>NOT FOUND</span>") . "<br>";
echo "<strong>AWS_SECRET_ACCESS_KEY:</strong> " . ($secret ? "Loaded (Length: " . strlen($secret) . ")" : "<span style='color:red'>NOT FOUND</span>") . "<br>";
echo "<strong>AWS_REGION:</strong> " . ($region ? $region : "<span style='color:red'>NOT FOUND - using default</span>") . "<br>";

echo "<h2>2. Connection Test</h2>";

if (!$key || !$secret) {
    echo "<p style='color:red'>❌ Cannot test connection: Missing credentials.</p>";
    exit;
}

try {
    $client = getRekognitionClient();
    $creds = $client->getCredentials()->wait();

    echo "<h3>Active Credentials Object:</h3>";
    echo "Access Key: " . substr($creds->getAccessKeyId(), 0, 4) . "..." . substr($creds->getAccessKeyId(), -4) . "<br>";
    echo "Secret Key: " . (strlen($creds->getSecretKey()) > 0 ? "Present" : "Missing") . "<br>";
    echo "Session Token: " . ($creds->getSecurityToken() ? "<span style='color:red'>DETECTED: " . substr($creds->getSecurityToken(), 0, 10) . "...</span>" : "<span style='color:green'>None (Good)</span>") . "<br>";

    $result = $client->listCollections(['MaxResults' => 1]);
    echo "<p style='color:green'>✅ <strong>Connection Successful!</strong></p>";
    echo "Found collections: " . count($result['CollectionIds']) . "<br>";
    echo "<pre>" . print_r($result['CollectionIds'], true) . "</pre>";
} catch (Aws\Exception\AwsException $e) {
    echo "<p style='color:red'>❌ <strong>AWS Error:</strong> " . $e->getAwsErrorMessage() . "</p>";
    echo "<strong>Error Code:</strong> " . $e->getAwsErrorCode() . "<br>";
    echo "<strong>Raw Message:</strong> " . $e->getMessage() . "<br>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ <strong>General Error:</strong> " . $e->getMessage() . "</p>";
}
?>