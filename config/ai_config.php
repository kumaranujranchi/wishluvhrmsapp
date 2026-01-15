<?php
// Load .env file manually
// Try multiple paths to find .env
$possiblePaths = [
    __DIR__ . '/../.env',       // Standard: config/../.env
    $_SERVER['DOCUMENT_ROOT'] . '/.env',  // Web root
    dirname(__DIR__) . '/.env'  // Project root
];

$envPath = '';
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        $envPath = $path;
        break;
    }
}

if ($envPath) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0)
            continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
        $_ENV[trim($name)] = trim($value);
    }
}

// Gemini AI API Configuration
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', 'gemini-1.5-flash-latest');
