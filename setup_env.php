<?php
$envContent = "GEMINI_API_KEY=AIzaSyBSLfZvyrAeVrRM76Du8BK0AYimOL0j1pI";
$file = __DIR__ . '/.env';

if (file_put_contents($file, $envContent)) {
    echo "<h1>SUCCESS!</h1>";
    echo ".env file created successfully at: " . $file;
    echo "<br>API Key has been secured.";
    echo "<br><br><b>Please DELETE this file (setup_env.php) after use!</b>";
} else {
    echo "<h1>ERROR</h1>";
    echo "Could not create .env file. Check permissions.";
}
?>