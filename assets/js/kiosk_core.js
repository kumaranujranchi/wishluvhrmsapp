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
        
        await new Promise((resolve) => {
            video.onloadedmetadata = () => {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                video.play().then(resolve).catch(err => {
                    console.error("Play error:", err);
                    alert("Video play failed: " + err.message);
                });
            };
        });
        
    } catch (err) {
        alert('Camera access denied or failed: ' + err.message);
        stopKioskMode();
        return;
    }

    document.getElementById('hudStatus').textContent = 'Camera Active. Tap Scan to Punch.';
    isScanning = true;
    
    // Show manual scan button in HUD if not already there
    let scanBtn = document.getElementById('manualScanBtn');
    if (!scanBtn) {
        scanBtn = document.createElement('button');
        scanBtn.id = 'manualScanBtn';
        scanBtn.className = 'capture-btn-lg'; 
        scanBtn.style.position = 'absolute';
        scanBtn.style.bottom = '100px';
        scanBtn.style.left = '50%';
        scanBtn.style.transform = 'translateX(-50%)';
        scanBtn.style.zIndex = '1000';
        scanBtn.innerHTML = '<i data-lucide="scan-face" style="width:24px;margin-right:8px;"></i> SCAN FACE';
        scanBtn.onclick = processAttendance;
        document.querySelector('.video-container').appendChild(scanBtn);
        if(typeof lucide !== 'undefined') lucide.createIcons();
    }
}

// ... stopKioskMode ...

// No detection loop needed for manual scan
async function detectLoop() { return; }

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
    document.getElementById('scanningLine').classList.add('active'); // Start Anim
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
            document.getElementById('scanningLine').classList.remove('active'); // Stop Anim
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
    
    // Explicit In/Out confirmation
    const actionText = data.type === 'in' ? 'Punch-In Successful' : 'Punch-Out Successful';
    time.textContent = data.message + ' • ' + actionText;
    
    avatar.src = data.avatar || 'assets/logo.png'; // Fallback
    
    card.classList.add('visible');
    
    document.getElementById('hudStatus').textContent = actionText;

    // Hide after 3 seconds
    setTimeout(() => {
        card.classList.remove('visible');
        document.getElementById('hudStatus').textContent = 'Scanning...';
    }, 4000);
}
