<?php
// Environment Configuration
// This file loads API keys from .env file

// Try multiple possible paths to find .env file
$possiblePaths = [
    __DIR__ . '/../.env',              // Standard: config/../.env
    $_SERVER['DOCUMENT_ROOT'] . '/.env',  // Web root
    dirname(__DIR__) . '/.env',        // Project root
    __DIR__ . '/../../.env',           // Two levels up (for nested structures)
    '/home/u743570205/domains/wishluvhrms.in/public_html/.env'  // Absolute path for live server
];

$envLoaded = false;
foreach ($possiblePaths as $envPath) {
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0)
                continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                putenv(trim($name) . '=' . trim($value));
                $_ENV[trim($name)] = trim($value);
            }
        }
        $envLoaded = true;
        break;  // Stop after finding the first valid .env file
    }
}

// Define constants for easy access
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', 'gemini-1.0-pro');
?>