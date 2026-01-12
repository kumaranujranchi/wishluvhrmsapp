-- Add approval workflow columns to leave_requests
ALTER TABLE leave_requests
ADD COLUMN manager_status ENUM('Pending', 'Approved', 'Rejected', 'Clarification') DEFAULT 'Pending',
ADD COLUMN manager_remarks TEXT NULL,
ADD COLUMN admin_status ENUM('Pending', 'Approved', 'Rejected', 'Clarification') DEFAULT 'Pending',
ADD COLUMN admin_remarks TEXT NULL,
ADD COLUMN clarification_text TEXT NULL;

-- Ensure created_at exists (it usually does, but good to be safe if table was manually created)
-- ALTER TABLE leave_requests ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;
