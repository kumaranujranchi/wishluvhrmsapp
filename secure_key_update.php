<?php
// Secure Key Updater
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apiKey = trim($_POST['api_key']);
    if (!empty($apiKey)) {
        $envContent = "GEMINI_API_KEY=" . $apiKey;
        if (file_put_contents('.env', $envContent)) {
            $message = "<div style='color:green; border:1px solid green; padding:10px; margin-bottom:20px;'>
                        ✅ Success! .env file updated. API Key secured.<br>
                        Now PLEASE DELETE this file from your server.
                        </div>";
        } else {
            $message = "<div style='color:red;'>❌ Error: Could not write to .env file. Check permissions.</div>";
        }
    } else {
        $message = "<div style='color:red;'>❌ Error: API Key cannot be empty.</div>";
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Secure API Key Update</title>
    <style>
        body {
            font-family: sans-serif;
            padding: 50px;
            text-align: center;
            background: #f4f4f5;
        }

        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: auto;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        button {
            background: #000;
            color: #fff;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            opacity: 0.9;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>🔐 Secure API Key Updater</h2>
        <p>Enter your new Gemini API Key below. This will verify and save it to the server's environment file directly.
        </p>
        <?= $message ?>
        <form method="POST">
            <input type="text" name="api_key" placeholder="Paste new API Key here" required>
            <button type="submit">Update Securely</button>
        </form>
    </div>
</body>

</html>