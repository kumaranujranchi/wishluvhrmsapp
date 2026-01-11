-- 1. Update Employees Table (Run this first)
ALTER TABLE employees 
ADD COLUMN avatar VARCHAR(255) NULL AFTER email,
ADD COLUMN dob DATE NULL AFTER joining_date,
ADD COLUMN marriage_anniversary DATE NULL AFTER dob,
ADD COLUMN reporting_manager_id INT NULL AFTER designation_id;

-- 2. Link Reporting Manager (Run this second)
ALTER TABLE employees
ADD CONSTRAINT fk_reporting_manager
FOREIGN KEY (reporting_manager_id) REFERENCES employees(id)
ON DELETE SET NULL;

-- 3. Create Attendance Table (Run this third)
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    clock_in TIME NULL,
    clock_out TIME NULL,
    status ENUM('Present', 'Absent', 'Late', 'Half Day', 'Leave') DEFAULT 'Absent',
    total_hours DECIMAL(5,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (employee_id, date)
);
