-- SIMPLE VERSION - Run these queries one by one in phpMyAdmin
-- Make sure you have selected your database first!

-- Query 1: Create locations table
CREATE TABLE IF NOT EXISTS attendance_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    radius INT DEFAULT 100,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Query 2: Create employee-location mapping
CREATE TABLE IF NOT EXISTS employee_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    location_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_emp_location (employee_id, location_id)
);

-- Query 3: Add columns to attendance table (run one by one if error)
ALTER TABLE attendance ADD COLUMN punch_latitude DECIMAL(10, 8);
ALTER TABLE attendance ADD COLUMN punch_longitude DECIMAL(11, 8);
ALTER TABLE attendance ADD COLUMN location_id INT;
ALTER TABLE attendance ADD COLUMN out_of_range TINYINT(1) DEFAULT 0;
ALTER TABLE attendance ADD COLUMN out_of_range_reason TEXT;

-- Query 4: Insert sample locations (update coordinates)
INSERT INTO attendance_locations (name, address, latitude, longitude, radius) VALUES
('Head Office', 'Main Office Address', 28.6139391, 77.2090212, 100),
('Project Site 1', 'Project Site 1 Address', 28.6129391, 77.2290212, 150);
