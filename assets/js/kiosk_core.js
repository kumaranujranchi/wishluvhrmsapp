let video, canvas, ctx, overlayCanvas, overlayCtx;
let isScanning = false;
let modelLoaded = false;
let lastProcessTime = 0;
const PROCESS_INTERVAL = 3000; // time between server checks (ms)
let isProcessing = false;
let isCooldown = false; // New: Cooldown state after success
let countdownInterval = null;
let countdownValue = 3;
let faceStableStartTime = null;
const STABLE_THRESHOLD = 500; // ms face must be stable to start countdown
let unstableFrames = 0; // New: Counter for stability buffer
const MAX_UNSTABLE_FRAMES = 10; // New: Grace period for face loss/movement

const MODELS_URL = 'https://justadudewhohacks.github.io/face-api.js/models';

// ... [AUDIO SYSTEM omitted, unchanged] ...

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
                facingMode: 'user',
                aspectRatio: { ideal: 0.5625 } // Try for 9:16 aspect ratio on mobile
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

    document.getElementById('hudStatus').textContent = 'Loading AI Models...';
    
    // Load Models (Try-Catch with Timeout)
    try {
        if (!modelLoaded) {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODELS_URL)
            ]);
            modelLoaded = true;
            console.log("AI Models Loaded");
        }
    } catch (err) {
        console.error("AI Model Load Fail:", err);
        document.getElementById('hudStatus').textContent = 'AI Model Error. Manual Mode Ready.';
        // Fallback to manual button if models fail
        showManualButton();
    }

    document.getElementById('hudStatus').textContent = 'Scanning... Align your face.';
    isScanning = true;
    isCooldown = false;
    detectLoop();
}

function showManualButton() {
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
        scanBtn.innerHTML = '<i data-lucide="scan-face" style="width:24px;margin-right:8px;"></i> SCAN MANUALLY';
        scanBtn.onclick = processAttendance;
        document.querySelector('.video-container').appendChild(scanBtn);
        if(typeof lucide !== 'undefined') lucide.createIcons();
    }
}

function stopKioskMode() {
    isScanning = false;
    document.getElementById('kioskOverlay').classList.remove('active');
    
    if (video && video.srcObject) {
        video.srcObject.getTracks().forEach(t => t.stop());
    }
}

// Auto-detection loop
async function detectLoop() {
    // CRITICAL FIX: Ensure the loop continues unless explicitly stopped
    if (!isScanning) return; 

    // Skip processing if busy or cooling down, but keep looping
    if (isProcessing || !modelLoaded || isCooldown) {
        requestAnimationFrame(detectLoop);
        return;
    }

    try {
        const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
        const detection = await faceapi.detectSingleFace(video, options);

        if (detection) {
            const box = detection.box;
            const isCentered = isFaceCentered(box);
            // Responsive Size Check: Face must be at least 15% of video width
            const minFaceWidth = video.videoWidth * 0.15; 
            const isLargeEnough = box.width > minFaceWidth;

            if (isCentered && isLargeEnough) {
                // Face is good! Reset unstable counter
                unstableFrames = 0; 

                if (!faceStableStartTime) {
                    faceStableStartTime = Date.now();
                } else if (Date.now() - faceStableStartTime > STABLE_THRESHOLD) {
                    if (!countdownInterval) {
                        startCountdown();
                    }
                }
            } else {
                // Face detected but not good quality (off center/too small)
                handleUnstableFrame('Adjust position...');
            }
        } else {
            // No face detected
            handleUnstableFrame('Scanning for face...');
        }
    } catch (err) {
        console.error("Detect Error:", err);
    }

    requestAnimationFrame(detectLoop);
}

function handleUnstableFrame(statusMessage) {
    unstableFrames++;
    // Only reset if unstable for consecutive frames (Buffer)
    if (unstableFrames > MAX_UNSTABLE_FRAMES) {
        resetCountdown(statusMessage);
        unstableFrames = 0;
    }
}

function isFaceCentered(box) {
    const videoCenterX = video.videoWidth / 2;
    const videoCenterY = video.videoHeight / 2;
    const faceCenterX = box.x + box.width / 2;
    const faceCenterY = box.y + box.height / 2;
    
    // Responsive Threshold: Relaxed to 25% of video dimension for better UX
    const thresholdX = video.videoWidth * 0.25; 
    const thresholdY = video.videoHeight * 0.25;

    return Math.abs(faceCenterX - videoCenterX) < thresholdX && 
           Math.abs(faceCenterY - videoCenterY) < thresholdY;
}

function startCountdown() {
    if(countdownInterval) return; // Prevent double trigger
    
    countdownValue = 3;
    const overlay = document.getElementById('countdownOverlay');
    overlay.textContent = countdownValue;
    overlay.classList.add('visible');
    document.getElementById('hudStatus').textContent = `STAY STILL...`;
    playRoboticSound('scan');

    countdownInterval = setInterval(() => {
        countdownValue--;
        if (countdownValue > 0) {
            overlay.textContent = countdownValue;
            playRoboticSound('scan');
        } else {
            clearInterval(countdownInterval);
            countdownInterval = null;
            overlay.classList.remove('visible');
            document.getElementById('hudStatus').textContent = 'CAPTURING...';
            processAttendance();
        }
    }, 1000);
}

function resetCountdown(status) {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    const overlay = document.getElementById('countdownOverlay');
    if(overlay) overlay.classList.remove('visible');
    
    faceStableStartTime = null;
    if (!isProcessing && !isCooldown) {
        document.getElementById('hudStatus').textContent = status;
    }
}

// ... [draw functions omitted, likely unused but keeping structure if needed] ...

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
                isCooldown = true; // Enable cooldown to show result
                showResult(result);
                lastProcessTime = Date.now(); 
            } else {
                document.getElementById('hudStatus').textContent = 'Face Not Recognized';
                // Wait briefly before allowing retry
                setTimeout(() => {
                    if(isScanning) document.getElementById('hudStatus').textContent = 'Scanning...';
                }, 2000);
            }
        } catch (e) {
            console.error(e);
            document.getElementById('hudStatus').textContent = 'Connection Error';
            setTimeout(() => {
                if(isScanning) document.getElementById('hudStatus').textContent = 'Scanning...';
            }, 2000);
        } finally {
            isProcessing = false;
            document.getElementById('scanningLine').classList.remove('active'); // Stop Anim
            unstableFrames = 0; // Reset stability
            faceStableStartTime = null;
        }

    }, (err) => {
        alert('Location access is mandatory.');
        isProcessing = false;
        document.getElementById('scanningLine').classList.remove('active');
    });
}

function showResult(data) {
    const card = document.getElementById('resultCard');
    const avatar = document.getElementById('resultAvatar');
    const name = document.getElementById('resultName');
    const time = document.getElementById('resultTime');

    name.textContent = data.employee_name;
    
    // Explicit In/Out confirmation
    let actionText = 'Attendance Recorded';
    if (data.type === 'in') actionText = 'Punch-In Successful';
    else if (data.type === 'out') actionText = 'Punch-Out Successful';
    else if (data.type === 'complete') actionText = 'Punches Complete for Today';
    
    time.textContent = data.message + (data.type ? ' • ' + actionText : '');
    
    avatar.src = data.avatar || 'assets/logo.png'; // Fallback
    
    card.classList.add('visible');
    
    document.getElementById('hudStatus').textContent = actionText;

    // Hide after 3.5 seconds
    setTimeout(() => {
        card.classList.remove('visible');
        isCooldown = false; // Disable cooldown, allow next scan
        resetCountdown('Scanning...'); // Reset state for next user
    }, 3500);
}
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
                facingMode: 'user',
                aspectRatio: { ideal: 0.5625 } // Try for 9:16 aspect ratio on mobile
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

    document.getElementById('hudStatus').textContent = 'Loading AI Models...';
    
    // Load Models (Try-Catch with Timeout)
    try {
        if (!modelLoaded) {
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODELS_URL)
            ]);
            modelLoaded = true;
            console.log("AI Models Loaded");
        }
    } catch (err) {
        console.error("AI Model Load Fail:", err);
        document.getElementById('hudStatus').textContent = 'AI Model Error. Manual Mode Ready.';
        // Fallback to manual button if models fail
        showManualButton();
    }

    document.getElementById('hudStatus').textContent = 'Scanning... Align your face.';
    isScanning = true;
    detectLoop();
}

function showManualButton() {
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
        scanBtn.innerHTML = '<i data-lucide="scan-face" style="width:24px;margin-right:8px;"></i> SCAN MANUALLY';
        scanBtn.onclick = processAttendance;
        document.querySelector('.video-container').appendChild(scanBtn);
        if(typeof lucide !== 'undefined') lucide.createIcons();
    }
}

function stopKioskMode() {
    isScanning = false;
    document.getElementById('kioskOverlay').classList.remove('active');
    
    if (video && video.srcObject) {
        video.srcObject.getTracks().forEach(t => t.stop());
    }
}

// Auto-detection loop
async function detectLoop() {
    if (!isScanning || isProcessing || !modelLoaded) return;

    try {
        const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.5 });
        const detection = await faceapi.detectSingleFace(video, options);

        if (detection) {
            const box = detection.box;
            const isCentered = isFaceCentered(box);
            // Responsive Size Check: Face must be at least 15% of video width
            const minFaceWidth = video.videoWidth * 0.15; 
            const isLargeEnough = box.width > minFaceWidth;

            if (isCentered && isLargeEnough) {
                if (!faceStableStartTime) {
                    faceStableStartTime = Date.now();
                } else if (Date.now() - faceStableStartTime > STABLE_THRESHOLD) {
                    if (!countdownInterval) {
                        startCountdown();
                    }
                }
            } else {
                resetCountdown('Keep your face centered');
            }
        } else {
            resetCountdown('Scanning for face...');
        }
    } catch (err) {
        console.error("Detect Error:", err);
    }

    requestAnimationFrame(detectLoop);
}

function isFaceCentered(box) {
    const videoCenterX = video.videoWidth / 2;
    const videoCenterY = video.videoHeight / 2;
    const faceCenterX = box.x + box.width / 2;
    const faceCenterY = box.y + box.height / 2;
    
    // Responsive Threshold: 15% of video dimension
    const thresholdX = video.videoWidth * 0.15;
    const thresholdY = video.videoHeight * 0.15;

    return Math.abs(faceCenterX - videoCenterX) < thresholdX && 
           Math.abs(faceCenterY - videoCenterY) < thresholdY;
}

function startCountdown() {
    countdownValue = 3;
    const overlay = document.getElementById('countdownOverlay');
    overlay.textContent = countdownValue;
    overlay.classList.add('visible');
    document.getElementById('hudStatus').textContent = `STAY STILL...`;
    playRoboticSound('scan');

    countdownInterval = setInterval(() => {
        countdownValue--;
        if (countdownValue > 0) {
            overlay.textContent = countdownValue;
            playRoboticSound('scan');
        } else {
            clearInterval(countdownInterval);
            countdownInterval = null;
            overlay.classList.remove('visible');
            document.getElementById('hudStatus').textContent = 'CAPTURING...';
            processAttendance();
        }
    }, 1000);
}

function resetCountdown(status) {
    if (countdownInterval) {
        clearInterval(countdownInterval);
        countdownInterval = null;
    }
    const overlay = document.getElementById('countdownOverlay');
    if(overlay) overlay.classList.remove('visible');
    
    faceStableStartTime = null;
    if (!isProcessing) {
        document.getElementById('hudStatus').textContent = status;
    }
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
    let actionText = 'Attendance Recorded';
    if (data.type === 'in') actionText = 'Punch-In Successful';
    else if (data.type === 'out') actionText = 'Punch-Out Successful';
    else if (data.type === 'complete') actionText = 'Punches Complete for Today';
    
    time.textContent = data.message + (data.type ? ' • ' + actionText : '');
    
    avatar.src = data.avatar || 'assets/logo.png'; // Fallback
    
    card.classList.add('visible');
    
    document.getElementById('hudStatus').textContent = actionText;

    // Hide after 3 seconds
    setTimeout(() => {
        card.classList.remove('visible');
        document.getElementById('hudStatus').textContent = 'Scanning...';
    }, 4000);
}
