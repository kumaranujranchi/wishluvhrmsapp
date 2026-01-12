-- Geofencing Attendance System Migration
-- Run this after selecting your database

-- Step 1: Create locations table for geofencing
CREATE TABLE IF NOT EXISTS attendance_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    latitude DECIMAL(10, 8) NOT NULL,
    longitude DECIMAL(11, 8) NOT NULL,
    radius INT DEFAULT 100 COMMENT 'Radius in meters',
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 2: Create employee-location mapping table
CREATE TABLE IF NOT EXISTS employee_locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    location_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES attendance_locations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_emp_location (employee_id, location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Step 3: Check if columns already exist before adding
SET @dbname = DATABASE();
SET @tablename = 'attendance';
SET @columnname = 'punch_latitude';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE attendance ADD COLUMN punch_latitude DECIMAL(10, 8) AFTER remarks'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add punch_longitude
SET @columnname = 'punch_longitude';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE attendance ADD COLUMN punch_longitude DECIMAL(11, 8) AFTER punch_latitude'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add location_id
SET @columnname = 'location_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE attendance ADD COLUMN location_id INT AFTER punch_longitude'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add out_of_range
SET @columnname = 'out_of_range';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE attendance ADD COLUMN out_of_range TINYINT(1) DEFAULT 0 AFTER location_id'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Add out_of_range_reason
SET @columnname = 'out_of_range_reason';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE attendance ADD COLUMN out_of_range_reason TEXT AFTER out_of_range'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Step 4: Add foreign key if not exists (check first)
SET @fk_exists = (SELECT COUNT(*) 
                  FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
                  WHERE CONSTRAINT_SCHEMA = @dbname 
                  AND TABLE_NAME = 'attendance' 
                  AND CONSTRAINT_NAME = 'attendance_ibfk_location');

SET @preparedStatement = (SELECT IF(
    @fk_exists > 0,
    'SELECT 1',
    'ALTER TABLE attendance ADD CONSTRAINT attendance_ibfk_location FOREIGN KEY (location_id) REFERENCES attendance_locations(id) ON DELETE SET NULL'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Step 5: Insert default locations (update coordinates as needed)
INSERT INTO attendance_locations (name, address, latitude, longitude, radius, is_active) VALUES
('Head Office', 'Main Office Address, City', 28.6139391, 77.2090212, 100, 1),
('Project Site 1', 'Project Site Address, City', 28.6129391, 77.2290212, 150, 1)
ON DUPLICATE KEY UPDATE id=id;

-- Verification queries
SELECT 'Locations table created' as Status, COUNT(*) as Count FROM attendance_locations;
SELECT 'Employee-Location mapping table created' as Status, COUNT(*) as Count FROM employee_locations;
SELECT 'Attendance table columns added' as Status;
