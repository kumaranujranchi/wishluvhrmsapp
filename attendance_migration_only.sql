USE u743570205_wishluvhrmsapp;

-- 1. Create Attendance Table (This is the most important missing part)
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

-- 2. Link Reporting Manager (Try this. If it says "Duplicate", ignore it. It means it's already done.)
ALTER TABLE employees
ADD CONSTRAINT fk_reporting_manager
FOREIGN KEY (reporting_manager_id) REFERENCES employees(id)
ON DELETE SET NULL;
