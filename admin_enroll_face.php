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

// Fetch all employees with their face enrollment status
$sql = "SELECT e.id, e.first_name, e.last_name, e.employee_code, e.avatar, d.name as dept_name,
        ef.id as face_id, ef.confidence_score, ef.enrolled_at, ef.is_active
        FROM employees e
        LEFT JOIN departments d ON e.department_id = d.id
        LEFT JOIN employee_faces ef ON e.id = ef.employee_id AND ef.is_active = TRUE
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
        background: rgba(0, 0, 0, 0.8);
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
        border-radius: 20px;
        padding: 2rem;
        max-width: 600px;
        width: 100%;
    }

    #cameraFeed {
        width: 100%;
        border-radius: 12px;
        background: #000;
    }

    .camera-controls {
        display: flex;
        gap: 1rem;
        margin-top: 1.5rem;
    }

    .camera-controls button {
        flex: 1;
        padding: 1rem;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .capture-btn {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        color: white;
    }

    .cancel-btn {
        background: #f1f5f9;
        color: #64748b;
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
        <h3 id="enrollEmployeeName" style="margin-bottom: 1rem; text-align: center;"></h3>
        <video id="cameraFeed" autoplay playsinline></video>
        <canvas id="captureCanvas" style="display: none;"></canvas>
        <div class="camera-controls">
            <button class="cancel-btn" onclick="closeCamera()">Cancel</button>
            <button class="capture-btn" onclick="captureFace()">📸 Capture Face</button>
        </div>
        <p style="margin-top: 1rem; text-align: center; color: #64748b; font-size: 0.85rem;">
            Position face in center, ensure good lighting
        </p>
    </div>
</div>

<script>
    let currentStream = null;
    let currentEmployeeId = null;

    async function enrollFace(employeeId, employeeName) {
        currentEmployeeId = employeeId;
        document.getElementById('enrollEmployeeName').textContent = 'Enrolling: ' + employeeName;
        document.getElementById('cameraModal').classList.add('active');

        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: 640, height: 480 }
            });
            currentStream = stream;
            document.getElementById('cameraFeed').srcObject = stream;
        } catch (error) {
            alert('Camera access denied. Please allow camera permissions.');
            closeCamera();
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
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;

        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);

        const imageData = canvas.toDataURL('image/jpeg', 0.9);

        // Close camera
        closeCamera();

        // Show loading
        const loadingMsg = document.createElement('div');
        loadingMsg.className = 'alert';
        loadingMsg.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 10001; background: #3b82f6; color: white;';
        loadingMsg.textContent = '⏳ Enrolling face...';
        document.body.appendChild(loadingMsg);

        try {
            const response = await fetch('ajax/enroll_face.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `employee_id=${currentEmployeeId}&image_data=${encodeURIComponent(imageData)}`
            });

            const result = await response.json();

            loadingMsg.remove();

            if (result.success) {
                alert('✅ ' + result.message + '\nConfidence: ' + result.confidence + '%');
                location.reload();
            } else {
                alert('❌ ' + result.message);
            }
        } catch (error) {
            loadingMsg.remove();
            alert('Error: ' + error.message);
        }
    }
</script>

<?php include 'includes/footer.php'; ?>