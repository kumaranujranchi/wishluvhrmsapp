-- AWS Rekognition Face Recognition Database Schema
-- Run this migration to add face recognition tables

USE u743570205_wishluvhrmsapp;

-- Table to store employee face enrollment data
CREATE TABLE IF NOT EXISTS employee_faces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    aws_face_id VARCHAR(255) NOT NULL COMMENT 'AWS Rekognition Face ID',
    aws_image_id VARCHAR(255) NOT NULL COMMENT 'AWS Rekognition Image ID',
    confidence_score DECIMAL(5,2) COMMENT 'Face detection confidence score',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    enrolled_by INT COMMENT 'Admin who enrolled the face',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Only one active face per employee',
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (enrolled_by) REFERENCES employees(id) ON DELETE SET NULL,
    INDEX idx_employee_active (employee_id, is_active),
    INDEX idx_aws_face_id (aws_face_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to log all face verification attempts
CREATE TABLE IF NOT EXISTS face_verification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL COMMENT 'Employee attempting verification',
    attendance_id INT COMMENT 'Related attendance record if successful',
    verification_type ENUM('punch_in', 'punch_out', 'enrollment', 're_enrollment') NOT NULL,
    aws_face_id VARCHAR(255) COMMENT 'Matched AWS Face ID',
    confidence_score DECIMAL(5,2) COMMENT 'Match confidence score',
    success BOOLEAN DEFAULT FALSE,
    failure_reason VARCHAR(255) COMMENT 'Reason for failure if unsuccessful',
    ip_address VARCHAR(45) COMMENT 'IP address of the request',
    user_agent TEXT COMMENT 'Browser user agent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    FOREIGN KEY (attendance_id) REFERENCES attendance(id) ON DELETE SET NULL,
    INDEX idx_employee_date (employee_id, created_at),
    INDEX idx_success (success),
    INDEX idx_verification_type (verification_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add face verification columns to existing attendance table
ALTER TABLE attendance 
ADD COLUMN face_verified BOOLEAN DEFAULT FALSE COMMENT 'Whether face was verified for this punch' AFTER status,
ADD COLUMN face_confidence DECIMAL(5,2) COMMENT 'Face match confidence score' AFTER face_verified,
ADD COLUMN verification_method ENUM('face', 'manual', 'geolocation_only') DEFAULT 'geolocation_only' AFTER face_confidence;

-- Add index for face verified attendance queries
ALTER TABLE attendance
ADD INDEX idx_face_verified (face_verified, date);
