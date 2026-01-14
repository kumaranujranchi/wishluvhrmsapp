<?php
echo "<h2>Environment Debugger</h2>";
echo "Current Directory: " . __DIR__ . "<br>";
echo "Parent Directory: " . dirname(__DIR__) . "<br>";

$files = scandir(__DIR__);
echo "<h3>Files in Current Directory:</h3>";
echo "<pre>" . print_r($files, true) . "</pre>";

$envPath = __DIR__ . '/.env';
echo "<h3>Checking for .env file at: $envPath</h3>";

if (file_exists($envPath)) {
    echo "<span style='color:green'>FOUND! .env file exists.</span><br>";
    $content = file_get_contents($envPath);
    if (strpos($content, 'GEMINI_API_KEY') !== false) {
        echo "<span style='color:green'>Valid content detected (GEMINI_API_KEY found).</span><br>";
    } else {
        echo "<span style='color:red'>File exists but GEMINI_API_KEY not found in content.</span><br>";
    }
} else {
    echo "<span style='color:red'>NOT FOUND! .env file is missing here.</span><br>";
    echo "Did you manually create the .env file on the server? (Git does not upload it)<br>";
}
?>