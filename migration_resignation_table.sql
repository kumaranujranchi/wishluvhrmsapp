-- Create Resignations Table
CREATE TABLE IF NOT EXISTS resignations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    reason TEXT NOT NULL,
    resignation_date DATE NOT NULL,
    last_working_day DATE,
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    admin_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
);
