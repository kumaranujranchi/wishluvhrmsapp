<?php
require_once 'config/ai_config.php';
header('Content-Type: text/plain');

echo "=== Gemini API Debug Test ===\n\n";

// Check if API key is loaded
echo "API Key Loaded: " . (GEMINI_API_KEY ? "YES (Length: " . strlen(GEMINI_API_KEY) . ")" : "NO") . "\n\n";

if (!GEMINI_API_KEY) {
    echo "ERROR: API Key is empty!\n";
    exit;
}

// Test simple prompt with different models
$models_to_test = [
    'gemini-pro',
    'gemini-1.5-flash',
    'gemini-1.5-pro',
    'gemini-1.5-flash-latest',
    'gemini-1.5-pro-latest'
];

$test_prompt = "Say 'Hello' in one word.";

foreach ($models_to_test as $model) {
    echo "Testing Model: $model\n";
    echo str_repeat("-", 50) . "\n";

    $url = "https://generativelanguage.googleapis.com/v1/models/" . $model . ":generateContent?key=" . GEMINI_API_KEY;

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $test_prompt]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Code: $http_code\n";

    if ($http_code === 200) {
        $result = json_decode($response, true);
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'No response';
        echo "✅ SUCCESS! Response: $text\n";
    } else {
        echo "❌ FAILED\n";
        echo "Response: " . substr($response, 0, 300) . "\n";
    }

    echo "\n";
}

echo "\n=== Test Complete ===\n";
?>