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
    :root {
        --primary-gradient: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
        --accent-gradient: linear-gradient(135deg, #0ea5e9 0%, #22d3ee 100%);
        --card-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1), 0 8px 12px -6px rgba(0, 0, 0, 0.05);
    }

    .regularization-container {
        max-width: 100%;
        margin: 0;
        padding: 2rem;
        animation: fadeIn 0.5s ease-out;
        font-family: 'Outfit', sans-serif;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(15px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Page Header */
    .page-header {
        margin-bottom: 2.5rem;
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .page-header h2 {
        font-size: 2rem;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
    }

    .header-icon-wrap {
        background: var(--primary-gradient);
        color: white;
        padding: 12px;
        border-radius: 16px;
        display: inline-flex;
        box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
    }

    /* Dashboard Layout */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 2rem;
        align-items: start;
    }

    @media (max-width: 1200px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    /* Form Card */
    .form-card {
        background: white;
        border-radius: 28px;
        padding: 2.5rem;
        box-shadow: var(--card-shadow);
        border: 1px solid #f1f5f9;
    }

    .form-group {
        margin-bottom: 2rem;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.75rem;
        font-weight: 700;
        color: #334155;
        font-size: 0.95rem;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 1rem 1.25rem;
        background: #f8fafc;
        border: 2px solid transparent;
        border-radius: 16px;
        font-size: 1rem;
        color: #1e293b;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        font-family: inherit;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        background: white;
        border-color: #6366f1;
        outline: none;
        box-shadow: 0 0 0 5px rgba(99, 102, 241, 0.1);
    }

    .time-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }

    /* Sidebar Styles */
    .info-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .sidebar-card {
        background: white;
        border-radius: 24px;
        padding: 1.5rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid #f1f5f9;
        font-size: 0.9rem;
    }

    .sidebar-card h4 {
        margin: 0 0 1rem 0;
        color: #1e293b;
        font-weight: 800;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .guideline-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .guideline-list li {
        padding-left: 1.5rem;
        position: relative;
        margin-bottom: 0.75rem;
        color: #64748b;
        line-height: 1.5;
    }

    .guideline-list li::before {
        content: "";
        position: absolute;
        left: 0;
        top: 8px;
        width: 6px;
        height: 6px;
        background: #6366f1;
        border-radius: 50%;
    }

    /* Existing Attendance Context */
    .existing-attendance {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 1.25rem;
        border-radius: 20px;
        border: 1px dashed #cbd5e1;
    }

    .existing-attendance h4 {
        color: #6366f1;
        margin-bottom: 0.5rem;
    }

    #existingData {
        font-weight: 600;
        color: #334155;
        line-height: 1.4;
    }

    .btn-submit {
        background: var(--primary-gradient);
        color: white;
        padding: 1.25rem 2rem;
        border: none;
        border-radius: 18px;
        font-size: 1.1rem;
        font-weight: 700;
        cursor: pointer;
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        transition: all 0.3s;
        box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
    }

    .btn-submit:hover:not(:disabled) {
        transform: translateY(-3px);
        box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4);
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

        .form-card {
            padding: 1.25rem;
            /* Reduced from default */
        }

        .time-inputs {
            grid-template-columns: 1fr;
        }
    }
</style>

<!-- Removed redundant main-content div -->
<div class="regularization-container">
    <div class="page-header">
        <div class="header-icon-wrap">
            <i data-lucide="clock" style="width: 32px; height: 32px;"></i>
        </div>
        <div>
            <h2>Attendance Regularization</h2>
            <p style="color: #64748b; margin: 0;">Request adjustments for missed or incorrect punches</p>
        </div>
    </div>

    <div class="dashboard-grid">
        <!-- Main Form Column -->
        <div class="form-card">
            <div id="alertContainer"></div>

            <form id="regularizationForm">
                <div class="form-group">
                    <label for="attendance_date">Select Date *</label>
                    <input type="date" id="attendance_date" name="attendance_date" required
                        max="<?php echo date('Y-m-d'); ?>">
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
                    <label for="reason">Reason for Regularization *</label>
                    <textarea id="reason" name="reason" required minlength="20" rows="4"
                        placeholder="Please provide a detailed reason for this regularization request..."></textarea>
                    <small style="color: #64748b; display: block; margin-top: 0.5rem;">
                        <i data-lucide="info" style="width:12px; height:12px;"></i> Characters: <span
                            id="charCount">0</span>/20 minimum
                    </small>
                </div>

                <button type="submit" class="btn-submit">
                    <i data-lucide="send"></i> Submit Request
                </button>
            </form>
        </div>

        <!-- Sidebar Info Column -->
        <div class="info-sidebar">
            <div id="existingAttendance" style="display: none;" class="sidebar-card existing-attendance">
                <h4><i data-lucide="info"></i> Selection Context</h4>
                <p id="existingData"></p>
            </div>

            <div class="sidebar-card">
                <h4><i data-lucide="list-checks"></i> Guidelines</h4>
                <ul class="guideline-list">
                    <li>Requests can be submitted for the last 30 days.</li>
                    <li>Detailed reason (min 20 characters) is mandatory.</li>
                    <li>Requested times should match your shift hours.</li>
                    <li>Admin approval is required for all changes.</li>
                </ul>
            </div>

            <div class="sidebar-card" style="background: var(--accent-gradient); color: white;">
                <h4 style="color: white;"><i data-lucide="help-circle"></i> Need Help?</h4>
                <p style="margin: 0; opacity: 0.9;">If you're unsure about punch times, check your monthly attendance
                    report first.</p>
            </div>
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
                CustomDialog.alert('Error submitting request. Please try again.', 'error');
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