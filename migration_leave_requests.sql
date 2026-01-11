USE u743570205_wishluvhrmsapp;

CREATE TABLE IF NOT EXISTS leave_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    leave_type ENUM('Sick Leave', 'Half Day', 'Full Day', 'PL', 'EL') NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    reason TEXT,
    manager_status ENUM('Pending', 'Approved', 'Rejected', 'Justification') DEFAULT 'Pending',
    admin_status ENUM('Pending', 'Approved', 'Rejected', 'Justification') DEFAULT 'Pending',
    status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    manager_remarks TEXT,
    admin_remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);
