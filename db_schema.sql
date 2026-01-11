-- Database Schema for HRMS / Wishluv

-- Table: departments
CREATE TABLE IF NOT EXISTS departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: designations
CREATE TABLE IF NOT EXISTS designations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: employees
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_code VARCHAR(20) UNIQUE NOT NULL, -- e.g. EMP001
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Hashed password
    phone VARCHAR(20),
    department_id INT,
    designation_id INT,
    joining_date DATE,
    salary DECIMAL(10,2),
    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    role ENUM('Admin', 'Employee') DEFAULT 'Employee',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL,
    FOREIGN KEY (designation_id) REFERENCES designations(id) ON DELETE SET NULL
);

-- Default Admin User (Password: admin123)
-- hash for admin123 is roughly $2y$10$8.K... (using PHP password_hash)
-- INSERT INTO employees (employee_code, first_name, last_name, email, password, role) 
-- VALUES ('ADMIN01', 'Super', 'Admin', 'admin@wishluv.com', '$2y$10$YourHashedPasswordHere', 'Admin');
