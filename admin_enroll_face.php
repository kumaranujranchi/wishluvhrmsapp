<?php
/**
 * Admin Face Enrollment Page
 * Allows admins to enroll employee faces for attendance verification
 */

require_once 'config/db.php';
include 'includes/header.php';

// Only admins can access
if ($_SESSION['user_role'] !== 'Admin') {
    header('Location: index.php');
    exit;
}

// Fetch all employees with their latest face enrollment status
$sql = "SELECT e.id, e.first_name, e.last_name, e.employee_code, e.avatar, d.name as dept_name,
        MAX(ef.id) as face_id, MAX(ef.confidence_score) as confidence_score, 
        MAX(ef.enrolled_at) as enrolled_at, MAX(ef.is_active) as is_active
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN employee_faces ef ON e.id = ef.employee_id AND ef.is_active = TRUE
        GROUP BY e.id
        ORDER BY e.first_name ASC";
$stmt = $conn->query($sql);
$employees = $stmt->fetchAll();
?>

<style>
    .enroll-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 2rem;
    }

    .employee-card {
        background: white;
        border-radius: 16px;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: 2px solid #f1f5f9;
        transition: all 0.3s;
    }

    .employee-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -4px rgba(0, 0, 0, 0.15);
    }

    .employee-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .employee-avatar {
        width: 60px;
        height: 60px;
        border-radius: 12px;
        background: #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        color: #64748b;
        font-size: 1.2rem;
        overflow: hidden;
    }

    .employee-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .employee-info h3 {
        margin: 0;
        font-size: 1.1rem;
        color: #1e293b;
    }

    .employee-info p {
        margin: 0;
        font-size: 0.85rem;
        color: #64748b;
    }

    .enrollment-status {
        padding: 0.75rem;
        border-radius: 10px;
        margin-bottom: 1rem;
        font-size: 0.85rem;
    }

    .status-enrolled {
        background: #dcfce7;
        color: #166534;
        border-left: 4px solid #22c55e;
    }

    .status-not-enrolled {
        background: #fee2e2;
        color: #991b1b;
        border-left: 4px solid #ef4444;
    }

    .enroll-btn {
        width: 100%;
        padding: 0.75rem;
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s;
    }

    .enroll-btn:hover {
        transform: translateY(-2px);
    }

    .enroll-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Camera Modal */
    .camera-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(15, 23, 42, 0.95);
        backdrop-filter: blur(8px);
        z-index: 10000;
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .camera-modal.active {
        display: flex;
    }

    .camera-container {
        background: white;
        border-radius: 24px;
        padding: 2.5rem;
        max-width: 650px;
        width: 100%;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        position: relative;
    }

    .video-wrapper {
        position: relative;
        width: 100%;
        border-radius: 16px;
        overflow: hidden;
        background: #000;
        aspect-ratio: 4/3;
        margin-bottom: 1.5rem;
        border: 4px solid #f8fafc;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    #cameraFeed {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transform: scaleX(-1);
        /* Mirror effect */
    }

    /* Face Guide Overlay */
    .face-guide {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 200px;
        height: 260px;
        border: 2px dashed rgba(255, 255, 255, 0.6);
        border-radius: 50% / 45%;
        box-shadow: 0 0 0 1000px rgba(0, 0, 0, 0.4);
        pointer-events: none;
        z-index: 10;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .face-guide::before {
        content: "Align Face Here";
        color: white;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: -20px;
        background: rgba(0, 0, 0, 0.5);
        padding: 4px 8px;
        border-radius: 4px;
        opacity: 0.8;
    }

    .camera-controls {
        display: flex;
        gap: 0.75rem;
    }

    .camera-controls button {
        flex: 1;
        padding: 0.85rem 0.5rem;
        border: none;
        border-radius: 12px;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        white-space: nowrap;
        min-width: 0;
    }

    @media (max-width: 480px) {
        .camera-controls {
            flex-wrap: wrap;
        }

        .camera-controls button {
            flex: 1 1 calc(50% - 0.4rem);
        }

        .capture-btn {
            flex: 1 1 100% !important;
            order: -1;
            padding: 1rem !important;
            font-size: 1rem !important;
        }
    }

    .capture-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
    }

    .capture-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 20px -5px rgba(16, 185, 129, 0.4);
    }

    .cancel-btn {
        background: #f1f5f9;
        color: #64748b;
    }

    .enrollment-tips {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #f1f5f9;
    }

    .tip-item {
        text-align: center;
    }

    .tip-icon {
        width: 32px;
        height: 32px;
        background: #f8fafc;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 0.5rem;
        color: #6366f1;
    }

    .tip-text {
        font-size: 0.7rem;
        color: #64748b;
        font-weight: 600;
        line-height: 1.2;
    }

    .scan-line {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 2px;
        background: #10b981;
        box-shadow: 0 0 10px #10b981;
        z-index: 11;
        animation: scan 2.5s linear infinite;
        display: none;
    }

    @keyframes scan {
        0% {
            top: 20%;
            opacity: 0;
        }

        10% {
            opacity: 1;
        }

        90% {
            opacity: 1;
        }

        100% {
            top: 80%;
            opacity: 0;
        }
    }

    /* Multi-step Indicators */
    .enroll-steps {
        display: flex;
        justify-content: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .step-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
        opacity: 0.4;
        transition: all 0.3s;
    }

    .step-item.active {
        opacity: 1;
        transform: scale(1.1);
    }

    .step-item.completed {
        opacity: 0.8;
        color: #10b981;
    }

    .step-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        border: 2px solid #e2e8f0;
    }

    .step-item.active .step-circle {
        background: #6366f1;
        color: white;
        border-color: #4f46e5;
        box-shadow: 0 0 15px rgba(99, 102, 241, 0.4);
    }

    .step-item.completed .step-circle {
        background: #10b981;
        color: white;
        border-color: #059669;
    }

    .step-label {
        font-size: 0.75rem;
        font-weight: 600;
    }
</style>

<div class="page-content">
    <div class="page-header">
        <h2>👤 Face Enrollment</h2>
        <p>Enroll employee faces for attendance verification</p>
    </div>

    <div class="enroll-grid">
        <?php foreach ($employees as $emp): ?>
            <div class="employee-card">
                <div class="employee-header">
                    <div class="employee-avatar">
                        <?php if ($emp['avatar']): ?>
                            <img src="<?= htmlspecialchars($emp['avatar']) ?>" alt="Avatar">
                        <?php else: ?>
                            <?= strtoupper(substr($emp['first_name'], 0, 1) . substr($emp['last_name'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="employee-info">
                        <h3>
                            <?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>
                        </h3>
                        <p>
                            <?= htmlspecialchars($emp['employee_code']) ?> •
                            <?= htmlspecialchars($emp['dept_name'] ?? 'No Dept') ?>
                        </p>
                    </div>
                </div>

                <?php if ($emp['face_id']): ?>
                    <div class="enrollment-status status-enrolled">
                        ✓ Face Enrolled<br>
                        <small>Confidence:
                            <?= $emp['confidence_score'] ?>% •
                            <?= date('d M Y', strtotime($emp['enrolled_at'])) ?>
                        </small>
                    </div>
                    <button class="enroll-btn"
                        onclick="enrollFace(<?= $emp['id'] ?>, '<?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>')">
                        🔄 Re-enroll Face
                    </button>
                <?php else: ?>
                    <div class="enrollment-status status-not-enrolled">
                        ✗ Not Enrolled<br>
                        <small>Face recognition not available</small>
                    </div>
                    <button class="enroll-btn"
                        onclick="enrollFace(<?= $emp['id'] ?>, '<?= htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']) ?>')">
                        📸 Enroll Face
                    </button>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Camera Modal -->
<div class="camera-modal" id="cameraModal">
    <div class="camera-container">
        <h3 id="enrollEmployeeName"
            style="margin-bottom: 1.5rem; text-align: center; color: #1e293b; font-weight: 800;"></h3>

        <!-- Multi-step indicators -->
        <div class="enroll-steps">
            <div class="step-item active" id="step1">
                <div class="step-circle">1</div>
                <div class="step-label">Front</div>
            </div>
            <div class="step-item" id="step2">
                <div class="step-circle">2</div>
                <div class="step-label">Left Angle</div>
            </div>
            <div class="step-item" id="step3">
                <div class="step-circle">3</div>
                <div class="step-label">Right Angle</div>
            </div>
        </div>

        <div class="video-wrapper">
            <video id="cameraFeed" autoplay playsinline></video>
            <div class="face-guide"></div>
            <div class="scan-line" id="scanLine"></div>
        </div>

        <canvas id="captureCanvas" style="display: none;"></canvas>

        <div class="camera-controls">
            <button class="cancel-btn" onclick="closeCamera()">
                <i data-lucide="x" style="width: 18px;"></i> Cancel
            </button>
            <button class="cancel-btn" id="switchCameraBtn" onclick="toggleCamera()" style="display: none;">
                <i data-lucide="refresh-cw" style="width: 18px;"></i> Switch
            </button>
            <button class="capture-btn" onclick="captureFace()">
                <i data-lucide="camera" style="width: 18px;"></i> Capture & Enroll
            </button>
        </div>

        <div class="enrollment-tips">
            <div class="tip-item">
                <div class="tip-icon"><i data-lucide="sun" style="width: 16px;"></i></div>
                <div class="tip-text">Good<br>Lighting</div>
            </div>
            <div class="tip-item">
                <div class="tip-icon"><i data-lucide="smile" style="width: 16px;"></i></div>
                <div class="tip-text">Neutral<br>Expression</div>
            </div>
            <div class="tip-item">
                <div class="tip-icon"><i data-lucide="eye" style="width: 16px;"></i></div>
                <div class="tip-text">Eyes<br>Visible</div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentStream = null;
    let currentEmployeeId = null;
    let currentFacingMode = 'user';
    let currentStep = 1;
    const TOTAL_STEPS = 3;

    async function enrollFace(employeeId, employeeName) {
        currentEmployeeId = employeeId;
        currentFacingMode = 'user';
        currentStep = 1;
        updateStepUI();
        document.getElementById('enrollEmployeeName').textContent = 'Enrolling: ' + employeeName;
        document.getElementById('cameraModal').classList.add('active');

        // Check for multiple cameras
        try {
            const devices = await navigator.mediaDevices.enumerateDevices();
            const videoDevices = devices.filter(device => device.kind === 'videoinput');
            const switchBtn = document.getElementById('switchCameraBtn');
            if (videoDevices.length > 1) {
                switchBtn.style.display = 'flex';
            } else {
                switchBtn.style.display = 'none';
            }
        } catch (e) {
            console.warn('Device enumeration failed', e);
        }

        startCamera();
    }

    async function startCamera() {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: currentFacingMode,
                    width: { ideal: 640 },
                    height: { ideal: 480 }
                }
            });
            currentStream = stream;
            const video = document.getElementById('cameraFeed');
            video.srcObject = stream;

            // Mirror only for front camera
            if (currentFacingMode === 'user') {
                video.style.transform = 'scaleX(-1)';
            } else {
                video.style.transform = 'scaleX(1)';
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
        } catch (error) {
            console.error('Camera Access Error:', error);
            CustomDialog.alert('Camera access denied or not available. Please check permissions.', 'error', 'Camera Error');
            closeCamera();
        }
    }

    async function toggleCamera() {
        currentFacingMode = (currentFacingMode === 'user') ? 'environment' : 'user';
        await startCamera();
    }

    function updateStepUI() {
        for (let i = 1; i <= TOTAL_STEPS; i++) {
            const stepEl = document.getElementById(`step${i}`);
            if (!stepEl) continue;
            stepEl.classList.remove('active', 'completed');
            if (i === currentStep) stepEl.classList.add('active');
            else if (i < currentStep) stepEl.classList.add('completed');
        }
    }

    function closeCamera() {
        if (currentStream) {
            currentStream.getTracks().forEach(track => track.stop());
            currentStream = null;
        }
        document.getElementById('cameraModal').classList.remove('active');
    }

    async function captureFace() {
        const video = document.getElementById('cameraFeed');
        const canvas = document.getElementById('captureCanvas');
        const scanLine = document.getElementById('scanLine');

        // Show scan animation
        scanLine.style.display = 'block';

        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        const imageData = canvas.toDataURL('image/jpeg', 0.9);

        // Wait a bit for the "scan" feel
        await new Promise(r => setTimeout(r, 800));
        scanLine.style.display = 'none';

        // Show processing status
        const nameHeader = document.getElementById('enrollEmployeeName');
        const originalText = nameHeader.textContent;
        nameHeader.innerHTML = `<span style="color:#6366f1;">⏳ Processing Step ${currentStep}...</span>`;

        try {
            const response = await fetch('ajax/enroll_face.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `employee_id=${currentEmployeeId}&image_data=${encodeURIComponent(imageData)}&mode=${currentStep === 1 ? 'replace' : 'append'}`
            });

            const result = await response.json();

            if (result.success) {
                if (currentStep < TOTAL_STEPS) {
                    currentStep++;
                    updateStepUI();
                    nameHeader.textContent = originalText;

                    const hints = [
                        'Look Straight',
                        'Turn Slightly LEFT',
                        'Turn Slightly RIGHT'
                    ];

                    await CustomDialog.show({
                        type: 'success',
                        title: `Step ${currentStep - 1} Done!`,
                        message: `Great! Now ${hints[currentStep - 1]}`,
                        confirmText: 'Continue'
                    });
                } else {
                    await CustomDialog.show({
                        type: 'success',
                        title: 'Complete!',
                        message: 'All angles enrolled successfully.',
                        confirmText: 'Finish'
                    });
                    location.reload();
                }
            } else {
                nameHeader.textContent = originalText;
                CustomDialog.alert(result.message, 'error', 'Enrollment Failed');
            }
        } catch (error) {
            nameHeader.textContent = originalText;
            CustomDialog.alert(error.message, 'error', 'System Error');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>