-- Add new columns to employees table
ALTER TABLE employees 
ADD COLUMN avatar VARCHAR(255) NULL AFTER email,
ADD COLUMN dob DATE NULL AFTER joining_date,
ADD COLUMN marriage_anniversary DATE NULL AFTER dob,
ADD COLUMN reporting_manager_id INT NULL AFTER designation_id;

-- Add foreign key for reporting manager (self-referencing)
ALTER TABLE employees
ADD CONSTRAINT fk_reporting_manager
FOREIGN KEY (reporting_manager_id) REFERENCES employees(id)
ON DELETE SET NULL;
