<?php
// Debug Script to check Gemini Config on Server
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Gemini Config Debugger</h1>";

// 1. Check if config file exists
if (file_exists('config/ai_config.php')) {
    echo "<p style='color:green'>✅ config/ai_config.php found</p>";

    // Read file content explicitly
    $content = file_get_contents('config/ai_config.php');
    echo "<h3>File Content Inspection:</h3>";
    echo "<pre style='background:#f0f0f0; padding:10px;'>";

    // Check GEMINI_MODEL
    if (strpos($content, 'gemini-1.5-flash-latest') !== false) {
        echo "✅ Model is CORRECT: gemini-1.5-flash-latest\n";
    } else {
        echo "❌ Model is WRONG (Old version detected)\n";
    }
    echo "</pre>";

    // Include the file to check runtime values
    require_once 'config/ai_config.php';
    echo "<h3>Runtime Values:</h3>";
    echo "GEMINI_MODEL constant: " . (defined('GEMINI_MODEL') ? GEMINI_MODEL : 'Not Defined') . "<br>";
    echo "API Key Length: " . (defined('GEMINI_API_KEY') ? strlen(GEMINI_API_KEY) : '0') . "<br>";

} else {
    echo "<p style='color:red'>❌ config/ai_config.php NOT found!</p>";
}

echo "<hr>";

// 2. Check Chat Handler
if (file_exists('ajax/chat_handler.php')) {
    echo "<p style='color:green'>✅ ajax/chat_handler.php found</p>";
    $ajax_content = file_get_contents('ajax/chat_handler.php');

    echo "<h3>Handler Code Inspection:</h3>";
    echo "<pre style='background:#f0f0f0; padding:10px;'>";

    // Check API Version
    if (strpos($ajax_content, 'v1beta/models') !== false) {
        echo "✅ API Version is CORRECT: v1beta\n";
    } else if (strpos($ajax_content, 'v1/models') !== false) {
        echo "❌ API Version is WRONG: v1 (Should be v1beta)\n";
    } else {
        echo "❌ API URL not found or unrecognized\n";
    }
    echo "</pre>";
} else {
    echo "<p style='color:red'>❌ ajax/chat_handler.php NOT found!</p>";
}
?>