-- Add allow_outside_punch to employees
ALTER TABLE `employees` ADD COLUMN `allow_outside_punch` TINYINT(1) DEFAULT 0 COMMENT '0=No, 1=Yes';

-- Add out_of_range and out_of_range_reason to attendance if they do not exist
-- Using a stored procedure to handle idempotent column addition for MySQL < 8.0 support/safety
DROP PROCEDURE IF EXISTS upgrade_attendance_schema;

DELIMITER $$
CREATE PROCEDURE upgrade_attendance_schema()
BEGIN
    -- Check for out_of_range
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'out_of_range'
    ) THEN
        ALTER TABLE `attendance` ADD COLUMN `out_of_range` TINYINT(1) DEFAULT 0;
    END IF;

    -- Check for out_of_range_reason
    IF NOT EXISTS (
        SELECT * FROM information_schema.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'attendance' AND COLUMN_NAME = 'out_of_range_reason'
    ) THEN
        ALTER TABLE `attendance` ADD COLUMN `out_of_range_reason` VARCHAR(255) DEFAULT NULL;
    END IF;
END $$
DELIMITER ;

CALL upgrade_attendance_schema();
DROP PROCEDURE upgrade_attendance_schema;
