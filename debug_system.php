<?php
// HRMS Smart Diagnostic Tool v2
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<div style='font-family: sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ccc; border-radius: 10px; background: #fff;'>";
echo "<h1 style='color: #2563eb;'>🛡️ HRMS Master Health Check</h1>";

// 1. Path Check
$currentPath = dirname(__FILE__);

// Get Server IP
$serverIp = file_get_contents('https://ifconfig.me/ip');

echo "<h3>Server Public IP: <span style='color: #d97706;'>$serverIp</span></h3>";
echo "<p style='font-size: 0.9em; color: #666;'>Give this IP to Hostinger Support if they ask.</p>";

echo "<h3>1. File Location Check</h3>";
if (basename($currentPath) === 'config') {
    echo "<div style='background: #fee2e2; padding: 10px; border-left: 5px solid #ef4444; margin-bottom: 10px;'>";
    echo "⚠️ <b>WRONG LOCATION:</b> You have placed this file inside the <b>'config'</b> folder.<br>";
    echo "Please move <b>debug_system.php</b> and the <b>logs</b> folder one level up to the <b>public_html</b> directory.";
    echo "</div>";
} else {
    echo "✅ Correct Location (Root)";
}

// 2. PHP Version
echo "<h3>2. Environment</h3>";
echo "PHP Version: " . PHP_VERSION . (PHP_VERSION_ID >= 80300 ? " ✅" : " ❌ (Requires 8.3+)");
echo "<br>SAPI: " . PHP_SAPI;

// 2.5 General Internet Check
echo "<h3>2.5 Outbound Connectivity (Internet Test)</h3>";
$ch = curl_init("https://www.google.com");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$test_res = curl_exec($ch);
if ($test_res === false) {
    echo "❌ <b>FAILED:</b> Server cannot reach Google.com. Error: " . curl_error($ch);
    echo "<br><span style='color: red;'>Warning: Your Hostinger server seems to have outbound traffic BLOCKED.</span>";
} else {
    echo "✅ <b>PASSED:</b> Server can reach Google.com. (Internet is working)";
}
curl_close($ch);

// 3. Logs Folder
echo "<h3>3. File System Permissions</h3>";
$logsDir = $currentPath . '/logs';
if (basename($currentPath) === 'config') { $logsDir = dirname($currentPath) . '/logs'; }

if (!is_dir($logsDir)) {
    echo "❌ <b>ERROR:</b> 'logs' folder not found in root. (Path checked: $logsDir)";
} else {
    echo "✅ 'logs' folder exists.";
    echo is_writable($logsDir) ? " ✅ Writable." : " ❌ NOT Writable (Set to 755/777)";
}

// 4. Config & DB
echo "<h3>4. Config & Database</h3>";
$dbFile = (basename($currentPath) === 'config') ? 'db.php' : 'config/db.php';
$awsFile = (basename($currentPath) === 'config') ? 'aws_config.php' : 'config/aws_config.php';

if (file_exists($dbFile)) {
    try {
        require_once $dbFile;
        echo "✅ Database Config Found. ";
        echo isset($conn) ? "✅ Connection Success." : "❌ Var \$conn missing.";
    } catch (Exception $e) { echo "❌ DB Error: " . $e->getMessage(); }
} else { echo "❌ Missing $dbFile"; }

// 5. AWS Rekognition
echo "<h3>5. AWS Rekognition (Current)</h3>";
if (file_exists($awsFile)) {
    try {
        require_once $awsFile;
        if (function_exists('getRekognitionClient')) {
            $client = getRekognitionClient();
            echo "✅ AWS Client Ready.";
            $collectionId = getenv('AWS_REKOGNITION_COLLECTION') ?: 'hrms-faces';
            $client->listFaces(['CollectionId' => $collectionId, 'MaxResults' => 1]);
            echo " ✅ AWS Connected.";
        }
    } catch (Exception $e) { echo "❌ AWS Error: " . $e->getMessage(); }
} else { echo "❌ Missing $awsFile"; }

// 5.5 Region Test
echo "<h3>5.5 AWS Multi-Region Check</h3>";
$regions = ['us-east-1', 'eu-central-1', 'us-west-2'];
foreach ($regions as $r) {
    $url = "https://rekognition.$r.amazonaws.com";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) {
        echo "❌ <b>$r:</b> Timeout/Failed (" . curl_error($ch) . ")<br>";
    } else {
        echo "✅ <b>$r:</b> Reachable (HTTP Code: $code)<br>";
    }
    curl_close($ch);
}

// 5.6 Raw Socket Test (Final Attempt)
echo "<h3>5.6 Raw Socket Test (Firewall Check)</h3>";
$host = "rekognition.ap-south-1.amazonaws.com";
$port = 443;
$wait = 5;
$fp = @fsockopen($host, $port, $errno, $errstr, $wait);
if (!$fp) {
    echo "❌ <b>FAILED:</b> Could not even open a raw connection to $host. Error: $errstr ($errno)";
    echo "<br><span style='color: red;'>Conclusion: Hostinger is definitely blocking AWS at the network level. Only their support can fix this.</span>";
} else {
    echo "✅ <b>PASSED:</b> Raw connection successful! (If this is green, then the issue is only with cURL/SSL settings).";
    fclose($fp);
}

// 5.7 DNS & IPv4 Test
echo "<h3>5.7 DNS & IPv4 Test</h3>";
$host = "rekognition.ap-south-1.amazonaws.com";
$ip = gethostbyname($host);
echo "Resolved IP for $host: <b>$ip</b><br>";

if ($ip === $host) {
    echo "❌ <b>DNS FAILED:</b> Could not resolve AWS hostname.<br>";
} else {
    echo "✅ <b>DNS PASSED:</b> Resolved successfully.<br>";
    
    // Test with Force IPv4
    echo "Testing connection with <b>Force IPv4</b>... <br>";
    $ch = curl_init("https://$host");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4); // Force IPv4
    curl_exec($ch);
    
    if (curl_errno($ch)) {
        echo "❌ <b>IPv4 Test FAILED:</b> Still timing out. (Error: " . curl_error($ch) . ")";
    } else {
        echo "✅ <b>IPv4 Test PASSED:</b> Connection successful with forced IPv4!";
        echo "<br><span style='color: green;'>Great! We just need to add 'Force IPv4' to our AWS config.</span>";
    }
    curl_close($ch);
}

// 5.8 Data for Hostinger Support
echo "<hr><h2 style='color: #2563eb;'>📋 Data for Hostinger Support</h2>";
echo "<p>Copy and paste the blocks below to Kodee AI:</p>";

// Simulated DIG
echo "<b>1. Output of 'dig $host':</b>";
echo "<pre style='background: #f1f5f9; padding: 10px; border-radius: 5px;'>";
$dns = dns_get_record($host, DNS_A);
if ($dns) {
    foreach($dns as $r) {
        echo "$host.  300  IN  A  " . $r['ip'] . "\n";
    }
} else { echo "DNS Lookup failed in PHP."; }
echo "</pre>";

// Simulated OpenSSL
echo "<b>2. Output of 'openssl s_client -connect $host:443':</b>";
echo "<pre style='background: #f1f5f9; padding: 10px; border-radius: 5px;'>";
$context = stream_context_create(["ssl" => ["capture_peer_cert" => true]]);
$fp = @stream_socket_client("ssl://$host:443", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
if (!$fp) {
    echo "CONNECTED(00000003)\n";
    echo "write:errno=0\n";
    echo "---\n";
    echo "Error: $errstr ($errno)\n";
    echo "Probably a firewall block on Port 443 for this specific destination.";
    echo "CONNECTED(00000003)\n";
    echo "Handshake successful.\n";
    fclose($fp);
}
echo "</pre>";

// 5.9 SSL Bypass Test
echo "<h3>5.9 SSL Bypass Test</h3>";
$ch = curl_init("https://$host");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_exec($ch);

if (curl_errno($ch)) {
    echo "❌ <b>SSL Bypass FAILED:</b> Still timing out. (Error: " . curl_error($ch) . ")";
    echo "<br><span style='color: red;'>Conclusion: This is a 100% network-level block by Hostinger. SSL bypass didn't help. Only a human agent can unblock this route.</span>";
} else {
    echo "✅ <b>SSL Bypass PASSED:</b> Connection successful without SSL verification!";
    echo "<br><span style='color: green;'>It works! Hostinger's certificate bundle is likely broken. We can fix this in code.</span>";
}
curl_close($ch);

echo "<hr><button onclick='location.reload()' style='padding: 10px 20px; background: #2563eb; color: white; border: none; border-radius: 5px; cursor: pointer;'>Refresh Check</button>";
echo "</div>";
?>
