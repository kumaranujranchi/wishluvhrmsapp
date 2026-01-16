<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$page_title = "Request Attendance Regularization";

include 'includes/header.php';
// sidebar.php is already included inside header.php
?>

<style>
    .regularization-container {
        max-width: 800px;
        margin: 2rem auto;
        padding: 2rem;
    }

    .form-card {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: #333;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
    }

    .form-group textarea {
        min-height: 100px;
        resize: vertical;
    }

    .time-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .existing-attendance {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1.5rem;
        border-left: 4px solid #007bff;
    }

    .existing-attendance h4 {
        margin: 0 0 0.5rem 0;
        color: #007bff;
    }

    .btn-submit {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 1rem 2rem;
        border: none;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        width: 100%;
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }

    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    @media (max-width: 768px) {
        .regularization-container {
            padding: 1rem;
        }

        .time-inputs {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="main-content">
    <div class="regularization-container">
        <h2><i data-lucide="clock"></i> Request Attendance Regularization</h2>

        <div class="form-card">
            <div id="alertContainer"></div>

            <form id="regularizationForm">
                <div class="form-group">
                    <label for="attendance_date">Select Date *</label>
                    <input type="date" id="attendance_date" name="attendance_date" required
                        max="<?php echo date('Y-m-d'); ?>">
                </div>

                <div id="existingAttendance" style="display: none;" class="existing-attendance">
                    <h4>Existing Attendance Record:</h4>
                    <p id="existingData"></p>
                </div>

                <div class="form-group">
                    <label for="request_type">Request Type *</label>
                    <select id="request_type" name="request_type" required>
                        <option value="">-- Select Type --</option>
                        <option value="missed_punch_in">Missed Punch In</option>
                        <option value="missed_punch_out">Missed Punch Out</option>
                        <option value="both">Both (Missed In & Out)</option>
                        <option value="correction">Correction (Wrong Time)</option>
                    </select>
                </div>

                <div class="time-inputs">
                    <div class="form-group">
                        <label for="clock_in">Clock In Time *</label>
                        <input type="time" id="clock_in" name="clock_in" required>
                    </div>

                    <div class="form-group">
                        <label for="clock_out">Clock Out Time *</label>
                        <input type="time" id="clock_out" name="clock_out" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="reason">Reason for Regularization * (Min 20 characters)</label>
                    <textarea id="reason" name="reason" required minlength="20"
                        placeholder="Please provide a detailed reason for this regularization request..."></textarea>
                    <small style="color: #666;">Characters: <span id="charCount">0</span>/20 minimum</small>
                </div>

                <button type="submit" class="btn-submit">
                    <i data-lucide="send"></i> Submit Request
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    // Character counter
    document.getElementById('reason').addEventListener('input', function () {
        document.getElementById('charCount').textContent = this.value.length;
    });

    // Fetch existing attendance when date changes
    document.getElementById('attendance_date').addEventListener('change', function () {
        const date = this.value;
        if (!date) return;

        fetch(`ajax/get_attendance.php?date=${date}`)
            .then(res => res.json())
            .then(data => {
                const container = document.getElementById('existingAttendance');
                const dataEl = document.getElementById('existingData');

                if (data.exists) {
                    dataEl.innerHTML = `
                    <strong>Clock In:</strong> ${data.clock_in || 'N/A'} | 
                    <strong>Clock Out:</strong> ${data.clock_out || 'N/A'} | 
                    <strong>Status:</strong> ${data.status}
                `;
                    container.style.display = 'block';

                    // Pre-fill times if they exist
                    if (data.clock_in) document.getElementById('clock_in').value = data.clock_in;
                    if (data.clock_out) document.getElementById('clock_out').value = data.clock_out;
                } else {
                    container.style.display = 'none';
                }
            });
    });

    // Form submission
    document.getElementById('regularizationForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i data-lucide="loader"></i> Submitting...';

        fetch('ajax/submit_regularization.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                const alertContainer = document.getElementById('alertContainer');

                if (data.success) {
                    alertContainer.innerHTML = `
                <div class="alert alert-success">
                    ${data.message}
                </div>
            `;
                    this.reset();
                    setTimeout(() => {
                        window.location.href = 'regularization_status.php';
                    }, 2000);
                } else {
                    alertContainer.innerHTML = `
                <div class="alert alert-error">
                    ${data.message}
                </div>
            `;
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i data-lucide="send"></i> Submit Request';
                }

                lucide.createIcons();
            })
            .catch(err => {
                alert('Error submitting request. Please try again.');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="send"></i> Submit Request';
            });
    });

    // Set max date to today
    document.getElementById('attendance_date').max = new Date().toISOString().split('T')[0];

    // Set min date to 30 days ago
    const minDate = new Date();
    minDate.setDate(minDate.getDate() - 30);
    document.getElementById('attendance_date').min = minDate.toISOString().split('T')[0];
</script>

<?php include 'includes/footer.php'; ?>