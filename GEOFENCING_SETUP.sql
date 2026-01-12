-- ============================================
-- GEOFENCING SETUP - RUN EACH QUERY SEPARATELY
-- ============================================
-- IMPORTANT: Select your database first in phpMyAdmin!

-- ============================================
-- QUERY 1: Create attendance_locations table
-- ============================================
CREATE TABLE attendance_locations (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    radius INT DEFAULT 100,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- ============================================
-- QUERY 2: Create employee_locations table
-- ============================================
CREATE TABLE employee_locations (
    id INT NOT NULL AUTO_INCREMENT,
    employee_id INT NOT NULL,
    location_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
);

-- ============================================
-- QUERY 3: Add punch_latitude column
-- ============================================
ALTER TABLE attendance 
ADD COLUMN punch_latitude DECIMAL(10, 8);

-- ============================================
-- QUERY 4: Add punch_longitude column
-- ============================================
ALTER TABLE attendance 
ADD COLUMN punch_longitude DECIMAL(11, 8);

-- ============================================
-- QUERY 5: Add location_id column
-- ============================================
ALTER TABLE attendance 
ADD COLUMN location_id INT;

-- ============================================
-- QUERY 6: Add out_of_range column
-- ============================================
ALTER TABLE attendance 
ADD COLUMN out_of_range TINYINT(1) DEFAULT 0;

-- ============================================
-- QUERY 7: Add out_of_range_reason column
-- ============================================
ALTER TABLE attendance 
ADD COLUMN out_of_range_reason TEXT;

-- ============================================
-- QUERY 8: Insert Head Office location
-- ============================================
INSERT INTO attendance_locations 
(name, address, latitude, longitude, radius, is_active) 
VALUES 
('Head Office', 'Main Office Address, City', 28.6139391, 77.2090212, 100, 1);

-- ============================================
-- QUERY 9: Insert Project Site location
-- ============================================
INSERT INTO attendance_locations 
(name, address, latitude, longitude, radius, is_active) 
VALUES 
('Project Site 1', 'Project Site Address, City', 28.6129391, 77.2290212, 150, 1);

-- ============================================
-- VERIFICATION: Check if tables created
-- ============================================
SELECT * FROM attendance_locations;
SELECT * FROM employee_locations;
