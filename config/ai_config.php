<?php
// Load .env file manually (to avoid adding composer dependencies)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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
define('GEMINI_MODEL', 'gemini-flash-lite-latest');
