/**
 * Kiosk Mode Core Logic
 * Handles Camera, Face Detection, Animation, and API Calls
 */

let video, canvas, ctx, overlayCanvas, overlayCtx;
let isScanning = false;
let modelLoaded = false;
let lastProcessTime = 0;
const PROCESS_INTERVAL = 3000; // time between server checks (ms)
let isProcessing = false;

// --- AUDIO SYSTEM ---
const audioCtx = new (window.AudioContext || window.webkitAudioContext)();

function playRoboticSound(type) {
    if (audioCtx.state === 'suspended') audioCtx.resume();
    
    const osc = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    
    if (type === 'scan') {
        // High pitched futuristic blip
        osc.type = 'sine';
        osc.frequency.setValueAtTime(800, audioCtx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(1200, audioCtx.currentTime + 0.1);
        gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.1);
        osc.start();
        osc.stop(audioCtx.currentTime + 0.1);
    } 
    else if (type === 'success') {
        // Robotic "Computed" Sound
        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(200, audioCtx.currentTime);
        osc.frequency.linearRampToValueAtTime(600, audioCtx.currentTime + 0.2);
        gain.gain.setValueAtTime(0.2, audioCtx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
        
        // Add a second layer for depth
        const osc2 = audioCtx.createOscillator();
        const gain2 = audioCtx.createGain();
        osc2.connect(gain2);
        gain2.connect(audioCtx.destination);
        osc2.type = 'square';
        osc2.frequency.setValueAtTime(100, audioCtx.currentTime);
        osc2.frequency.linearRampToValueAtTime(300, audioCtx.currentTime + 0.2);
        gain2.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gain2.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);
        
        osc.start(); osc2.start();
        osc.stop(audioCtx.currentTime + 0.5);
        osc2.stop(audioCtx.currentTime + 0.5);
    }
}

// --- MAIN FUNCTIONS ---

async function startKioskMode() {
    document.getElementById('kioskOverlay').classList.add('active');
    
    video = document.getElementById('kioskVideo');
    canvas = document.createElement('canvas'); // hidden canvas for capture
    overlayCanvas = document.getElementById('overlayCanvas');
    overlayCtx = overlayCanvas.getContext('2d');

    // Initialize Camera
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ 
            video: { 
                width: { ideal: 1280 }, 
                height: { ideal: 720 },
                facingMode: 'user' 
            } 
        });
        video.srcObject = stream;
        
        // Wait for video to play to set canvas dimensions
        video.onloadedmetadata = () => {
             overlayCanvas.width = video.videoWidth;
             overlayCanvas.height = video.videoHeight;
             canvas.width = video.videoWidth;
             canvas.height = video.videoHeight;
        };
        
    } catch (err) {
        alert('Camera access denied. Please enable camera permissions.');
        stopKioskMode();
        return;
    }

    // Load Models
    if (!modelLoaded) {
        document.getElementById('hudStatus').textContent = 'Loading AI Models...';
        const MODEL_URL = 'https://justadudewhohacks.github.io/face-api.js/models';

        try {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODEL_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODEL_URL)
            ]);
            modelLoaded = true;
        } catch (err) {
            console.error(err);
            alert('Failed to load AI models. Please check your internet connection.');
            stopKioskMode();
            return;
        }
    }

    document.getElementById('hudStatus').textContent = 'Scanning for Lifeforms...';
    isScanning = true;
    detectLoop();
}

function stopKioskMode() {
    isScanning = false;
    document.getElementById('kioskOverlay').classList.remove('active');
    
    if (video && video.srcObject) {
        video.srcObject.getTracks().forEach(t => t.stop());
    }
}

async function detectLoop() {
    if (!isScanning) return;

    // Detect Face
    const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks();

    overlayCtx.clearRect(0, 0, overlayCanvas.width, overlayCanvas.height);

    if (detection) {
        const dims = faceapi.matchDimensions(overlayCanvas, video, true);
        const resized = faceapi.resizeResults(detection, dims);

        // 1. Draw "Futuristic" Box
        const box = resized.detection.box;
        drawFuturisticBox(box);

        // 2. Draw Points (Connect them)
        drawFaceMesh(resized.landmarks.positions);

        // 3. Check for Attendance Punch
        const now = Date.now();
        if (!isProcessing && (now - lastProcessTime > PROCESS_INTERVAL)) {
            // Check if face is centered and big enough (basic quality check)
            if (box.width > 150) { 
                processAttendance();
            }
        }
    }

    requestAnimationFrame(detectLoop);
}

function drawFuturisticBox(box) {
    const ctx = overlayCtx;
    const { x, y, width, height } = box;

    ctx.strokeStyle = '#6366f1';
    ctx.lineWidth = 2;
    ctx.shadowBlur = 10;
    ctx.shadowColor = '#6366f1';

    // Corners only
    const len = 30;
    
    // Top Left
    ctx.beginPath(); ctx.moveTo(x, y + len); ctx.lineTo(x, y); ctx.lineTo(x + len, y); ctx.stroke();
    // Top Right
    ctx.beginPath(); ctx.moveTo(x + width - len, y); ctx.lineTo(x + width, y); ctx.lineTo(x + width, y + len); ctx.stroke();
    // Bottom Right
    ctx.beginPath(); ctx.moveTo(x + width, y + height - len); ctx.lineTo(x + width, y + height); ctx.lineTo(x + width - len, y + height); ctx.stroke();
    // Bottom Left
    ctx.beginPath(); ctx.moveTo(x + len, y + height); ctx.lineTo(x, y + height); ctx.lineTo(x, y + height - len); ctx.stroke();
}

function drawFaceMesh(points) {
    const ctx = overlayCtx;
    ctx.fillStyle = '#10b981';
    ctx.strokeStyle = 'rgba(16, 185, 129, 0.3)';
    ctx.lineWidth = 1;

    // Connect dots
    ctx.beginPath();
    points.forEach((p, i) => {
        // Draw Dot
        
        // Draw Lines between neighbors (simple mesh effect)
        if (i < points.length - 1) {
            ctx.lineTo(p.x, p.y);
        }
    });
    ctx.stroke();
    
    // Draw Key Points
    points.forEach(p => {
         ctx.beginPath();
         ctx.arc(p.x, p.y, 2, 0, 2 * Math.PI);
         ctx.fill();
    });
}

async function processAttendance() {
    isProcessing = true;
    document.getElementById('hudStatus').textContent = 'Analyzing Biometrics...';
    playRoboticSound('scan'); // Blip

    // Capture Image
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
    const imageData = canvas.toDataURL('image/jpeg', 0.8);

    // Get Location (Mandatory per requirements)
    navigator.geolocation.getCurrentPosition(async (pos) => {
        const { latitude, longitude } = pos.coords;
        
        try {
            const formData = new FormData();
            formData.append('image_data', imageData);
            formData.append('latitude', latitude);
            formData.append('longitude', longitude);
            formData.append('passcode', '123456'); // Simple security check if needed

            const response = await fetch('ajax/kiosk_verify.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                playRoboticSound('success');
                showResult(result);
                lastProcessTime = Date.now(); // Reset timer
            } else {
                document.getElementById('hudStatus').textContent = 'Face Not Recognized';
                if(!result.no_match) {
                     // Only wait if it was an error, if just no match, keep scanning
                     lastProcessTime = Date.now() - 2000; // Retry sooner
                } else {
                    lastProcessTime = Date.now();
                }
            }
        } catch (e) {
            console.error(e);
            document.getElementById('hudStatus').textContent = 'Connection Error';
        } finally {
            isProcessing = false;
             // Reset Status text after delay if not success
             if(document.getElementById('hudStatus').textContent !== 'Attendance Recorded') {
                 setTimeout(() => {
                      if(isScanning) document.getElementById('hudStatus').textContent = 'Scanning...';
                 }, 1500);
             }
        }

    }, (err) => {
        alert('Location access is mandatory.');
        isProcessing = false;
    });
}

function showResult(data) {
    const card = document.getElementById('resultCard');
    const avatar = document.getElementById('resultAvatar');
    const name = document.getElementById('resultName');
    const time = document.getElementById('resultTime');

    name.textContent = data.employee_name;
    time.textContent = data.message;
    avatar.src = data.avatar || 'assets/logo.png'; // Fallback
    
    card.classList.add('visible');
    
    document.getElementById('hudStatus').textContent = 'Attendance Recorded';

    // Hide after 3 seconds
    setTimeout(() => {
        card.classList.remove('visible');
        document.getElementById('hudStatus').textContent = 'Scanning...';
    }, 4000);
}
