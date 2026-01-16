<?php
// Test if environment variables are loading correctly
require_once 'config/ai_config.php';

echo "<h2>Environment Variable Test</h2>";
echo "<p><strong>GOOGLE_MAPS_API_KEY:</strong> ";

$apiKey = getenv("GOOGLE_MAPS_API_KEY");
if ($apiKey) {
    echo "✅ LOADED (Length: " . strlen($apiKey) . " characters)";
    echo "<br>First 10 chars: " . substr($apiKey, 0, 10) . "...";
} else {
    echo "❌ NOT LOADED or EMPTY";
}

echo "</p>";

echo "<p><strong>GEMINI_API_KEY:</strong> ";
$geminiKey = getenv("GEMINI_API_KEY");
if ($geminiKey) {
    echo "✅ LOADED (Length: " . strlen($geminiKey) . " characters)";
} else {
    echo "❌ NOT LOADED or EMPTY";
}
echo "</p>";

echo "<h3>.env File Check</h3>";
$envPath = __DIR__ . '/.env';
if (file_exists($envPath)) {
    echo "<p>✅ .env file exists at: " . $envPath . "</p>";
    echo "<pre>";
    $lines = file($envPath);
    foreach ($lines as $line) {
        // Mask the actual keys for security
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            echo htmlspecialchars(trim($key)) . " = " . (trim($value) ? "[SET - " . strlen(trim($value)) . " chars]" : "[EMPTY]") . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p>❌ .env file NOT FOUND at: " . $envPath . "</p>";
}
?>