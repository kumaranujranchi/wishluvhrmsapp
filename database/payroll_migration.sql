-- Create Monthly Payroll Table
CREATE TABLE IF NOT EXISTS monthly_payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    month INT NOT NULL,
    year INT NOT NULL,
    total_working_days INT NOT NULL,
    present_days INT NOT NULL,
    absent_days INT NOT NULL,
    base_salary DECIMAL(10, 2) NOT NULL COMMENT 'Snapshot of employee monthly salary',
    net_salary DECIMAL(10, 2) NOT NULL COMMENT 'Calculated payout',
    status ENUM('Draft', 'Processed', 'Paid') DEFAULT 'Draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY unique_monthly_payroll (employee_id, month, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
