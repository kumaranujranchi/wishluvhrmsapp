<?php
// Script to LIST available Gemini models
require_once 'config/ai_config.php';

$apiKey = defined('GEMINI_API_KEY') ? GEMINI_API_KEY : 'KEY_NOT_FOUND';
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $apiKey;

echo "<h1>Gemini Available Models</h1>";
echo "<p>Checking API Key: " . substr($apiKey, 0, 5) . "...</p>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);

if (isset($data['models'])) {
    echo "<ul>";
    foreach ($data['models'] as $model) {
        if (in_array("generateContent", $model['supportedGenerationMethods'])) {
            echo "<li><strong>" . $model['name'] . "</strong> (Supported)</li>";
        } else {
            echo "<li style='color:grey'>" . $model['name'] . " (Not supported for chat)</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<h3>Error:</h3>";
    echo "<pre>" . print_r($data, true) . "</pre>";
}
?>