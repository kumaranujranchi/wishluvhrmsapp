-- Attendance Regularization System - Database Schema

-- 1. Create attendance_regularization table
CREATE TABLE IF NOT EXISTS attendance_regularization (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    requested_clock_in TIME,
    requested_clock_out TIME,
    reason TEXT NOT NULL,
    request_type ENUM('missed_punch_in', 'missed_punch_out', 'both', 'correction') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    requested_by INT NOT NULL,
    requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    reviewed_by INT,
    reviewed_at DATETIME,
    admin_remarks TEXT,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES employees(id),
    FOREIGN KEY (reviewed_by) REFERENCES employees(id),
    INDEX idx_employee_date (employee_id, attendance_date),
    INDEX idx_status (status),
    INDEX idx_requested_at (requested_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Modify attendance table to track regularization
ALTER TABLE attendance 
ADD COLUMN IF NOT EXISTS is_regularized TINYINT(1) DEFAULT 0 AFTER total_hours,
ADD COLUMN IF NOT EXISTS regularized_by INT AFTER is_regularized,
ADD COLUMN IF NOT EXISTS regularized_at DATETIME AFTER regularized_by,
ADD COLUMN IF NOT EXISTS regularization_remarks TEXT AFTER regularized_at;

-- Add foreign key for regularized_by (if not exists)
-- Note: This will fail if the constraint already exists, which is fine
ALTER TABLE attendance 
ADD CONSTRAINT fk_attendance_regularized_by 
FOREIGN KEY (regularized_by) REFERENCES employees(id);

-- 3. Create index for faster queries
ALTER TABLE attendance 
ADD INDEX IF NOT EXISTS idx_is_regularized (is_regularized);
