-- Add gender column to employees table
ALTER TABLE employees 
ADD COLUMN gender ENUM('Male', 'Female', 'Other') DEFAULT 'Male' AFTER last_name;
