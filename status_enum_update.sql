-- Database migration to add 'On Time' status to attendance table
ALTER TABLE attendance MODIFY COLUMN status ENUM('On Time', 'Present', 'Absent', 'Late', 'Half Day', 'Leave') DEFAULT 'Absent';

-- Optional: Update existing 'Present' or empty records that meet the 'On Time' criteria (before 10:00 AM)
-- Only run this if you want to retrospectively fix statuses for today or previous days.
-- UPDATE attendance SET status = 'On Time' WHERE clock_in < '10:00:00' AND status IN ('Present', '');
