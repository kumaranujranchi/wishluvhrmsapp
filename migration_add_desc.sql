-- Run this query to add the description column to departments
ALTER TABLE departments ADD COLUMN description TEXT AFTER name;

-- Run this to verify
-- DESCRIBE departments;
