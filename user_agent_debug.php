<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug User Agent</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            line-height: 1.5;
            word-break: break-all;
        }

        .box {
            padding: 15px;
            border: 1px solid #ddd;
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .label {
            font-weight: bold;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }

        .value {
            color: #000;
            font-size: 1.1em;
        }

        .highlight {
            color: green;
            font-weight: bold;
        }

        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <h2>User Agent Debugger</h2>

    <div class="box">
        <span class="label">Current User Agent:</span>
        <div class="value" id="ua">Loading...</div>
    </div>

    <div class="box">
        <span class="label">Status:</span>
        <div class="value" id="status">Checking...</div>
    </div>

    <div class="box">
        <span class="label">Window.Capacitor:</span>
        <div class="value" id="cap">Checking...</div>
    </div>

    <script>
        const ua = navigator.userAgent;
        document.getElementById('ua').innerText = ua;

        const isApp = ua.includes("WishluvMobileApp");
        const statusEl = document.getElementById('status');

        if (isApp) {
            statusEl.innerHTML = '<span class="highlight">✅ Correct (App Detected)</span>';
        } else {
            statusEl.innerHTML = '<span class="error">❌ Incorrect (App NOT Detected)</span>';
        }

        const capEl = document.getElementById('cap');
        if (window.Capacitor) {
            capEl.innerHTML = '<span class="highlight">✅ Present</span>';
            if (window.Capacitor.isNativePlatform()) {
                capEl.innerHTML += ' (Native Platform)';
            }
        } else {
            capEl.innerHTML = '<span class="error">❌ Not Found</span>';
        }
    </script>
</body>

</html>