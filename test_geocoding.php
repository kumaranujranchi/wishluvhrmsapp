<?php require_once 'config/ai_config.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Geocoding API Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }

        .test-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        button {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background: #4338ca;
        }

        .result {
            margin-top: 15px;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 4px solid #4f46e5;
        }

        .success {
            border-left-color: #10b981;
            background: #f0fdf4;
        }

        .error {
            border-left-color: #ef4444;
            background: #fef2f2;
        }

        pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
        }
    </style>
</head>

<body>
    <h1>🗺️ Google Maps Geocoding API Test</h1>

    <div class="test-box">
        <h2>Step 1: Check API Key</h2>
        <p>API Key from PHP: <strong id="apiKeyStatus">Loading...</strong></p>
        <script>
            const apiKey = '<?= getenv("GOOGLE_MAPS_API_KEY") ?>';
            const statusEl = document.getElementById('apiKeyStatus');
            if (apiKey && apiKey.length > 0) {
                statusEl.innerHTML = `✅ Loaded (${apiKey.length} characters)<br>Key: ${apiKey.substring(0, 15)}...`;
                statusEl.style.color = '#10b981';
            } else {
                statusEl.innerHTML = '❌ NOT LOADED or EMPTY';
                statusEl.style.color = '#ef4444';
            }
        </script>
    </div>

    <div class="test-box">
        <h2>Step 2: Get Your Location</h2>
        <button onclick="getLocation()">📍 Get My Location</button>
        <div id="locationResult"></div>
    </div>

    <div class="test-box">
        <h2>Step 3: Test Geocoding</h2>
        <button onclick="testGeocoding()">🔄 Convert to Address</button>
        <div id="geocodingResult"></div>
    </div>

    <script>
        let currentLat, currentLng;

        function getLocation() {
            const resultDiv = document.getElementById('locationResult');
            resultDiv.innerHTML = '<p>⏳ Getting location...</p>';

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        currentLat = position.coords.latitude;
                        currentLng = position.coords.longitude;
                        resultDiv.innerHTML = `
                            <div class="result success">
                                <strong>✅ Location Found!</strong><br>
                                Latitude: ${currentLat}<br>
                                Longitude: ${currentLng}<br>
                                Accuracy: ${position.coords.accuracy} meters
                            </div>
                        `;
                    },
                    (error) => {
                        resultDiv.innerHTML = `
                            <div class="result error">
                                <strong>❌ Error:</strong> ${error.message}
                            </div>
                        `;
                    },
                    {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            } else {
                resultDiv.innerHTML = '<div class="result error">❌ Geolocation not supported</div>';
            }
        }

        async function testGeocoding() {
            const resultDiv = document.getElementById('geocodingResult');

            if (!currentLat || !currentLng) {
                resultDiv.innerHTML = '<div class="result error">❌ Please get your location first (Step 2)</div>';
                return;
            }

            resultDiv.innerHTML = '<p>⏳ Converting coordinates to address...</p>';

            try {
                const apiKey = '<?= getenv("GOOGLE_MAPS_API_KEY") ?>';
                const url = `https://maps.googleapis.com/maps/api/geocode/json?latlng=${currentLat},${currentLng}&key=${apiKey}`;

                console.log('Request URL:', url);

                const response = await fetch(url);
                const data = await response.json();

                console.log('API Response:', data);

                if (data.status === 'OK' && data.results[0]) {
                    const addressComponents = data.results[0].address_components;

                    // Extract components
                    const getComponent = (type) => {
                        const comp = addressComponents.find(c => c.types.includes(type));
                        return comp ? comp.long_name : null;
                    };

                    const road = getComponent('route');
                    const sublocality = getComponent('sublocality_level_1') || getComponent('sublocality');
                    const locality = getComponent('locality');

                    let locationParts = [];
                    if (road) locationParts.push(road);
                    else if (sublocality) locationParts.push(sublocality);
                    if (locality) locationParts.push(locality);

                    const shortAddress = locationParts.length > 0
                        ? locationParts.join(', ')
                        : data.results[0].formatted_address;

                    resultDiv.innerHTML = `
                        <div class="result success">
                            <strong>✅ Geocoding Successful!</strong><br><br>
                            <strong>Short Address:</strong><br>
                            ${shortAddress}<br><br>
                            <strong>Full Address:</strong><br>
                            ${data.results[0].formatted_address}<br><br>
                            <details>
                                <summary>View Full API Response</summary>
                                <pre>${JSON.stringify(data, null, 2)}</pre>
                            </details>
                        </div>
                    `;
                } else {
                    resultDiv.innerHTML = `
                        <div class="result error">
                            <strong>❌ Geocoding Failed</strong><br>
                            Status: ${data.status}<br>
                            ${data.error_message ? `Error: ${data.error_message}` : ''}<br><br>
                            <details>
                                <summary>View Full Response</summary>
                                <pre>${JSON.stringify(data, null, 2)}</pre>
                            </details>
                        </div>
                    `;
                }
            } catch (error) {
                resultDiv.innerHTML = `
                    <div class="result error">
                        <strong>❌ Request Failed</strong><br>
                        ${error.message}<br><br>
                        Check browser console for details.
                    </div>
                `;
                console.error('Geocoding error:', error);
            }
        }
    </script>
</body>

</html>