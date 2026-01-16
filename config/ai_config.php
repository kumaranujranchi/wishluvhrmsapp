<?php
// Environment Configuration
// This file loads API keys from .env file if available, otherwise uses fallback values

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


// Fallback: If .env not found or keys not loaded, use direct values
// IMPORTANT: Replace these with your actual API keys on live server
if (!getenv('GOOGLE_MAPS_API_KEY')) {
    putenv('GOOGLE_MAPS_API_KEY=AIzaSyB-G2YfDMGSnxWiqMRKASJENDHNW0iUVp8');
    $_ENV['GOOGLE_MAPS_API_KEY'] = 'AIzaSyB-G2YfDMGSnxWiqMRKASJENDHNW0iUVp8';
}

if (!getenv('GEMINI_API_KEY')) {
    putenv('GEMINI_API_KEY=AIzaSyB4My_SRbGXTEAGg2CJb1ZbxfnFt0FtAaE');
    $_ENV['GEMINI_API_KEY'] = 'AIzaSyB4My_SRbGXTEAGg2CJb1ZbxfnFt0FtAaE';
}

// Define constants for easy access
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: '');
define('GEMINI_MODEL', 'gemini-1.0-pro');
?>