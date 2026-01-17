-- SQL Script to convert existing decimal hours to minutes
-- This will fix the duration display issue

-- Update all attendance records where total_hours is in decimal format (< 100)
-- Convert decimal hours to minutes by multiplying by 60
UPDATE attendance 
SET total_hours = ROUND(total_hours * 60)
WHERE total_hours > 0 
  AND total_hours < 100;

-- Verify the update
SELECT 
    id,
    employee_id,
    date,
    clock_in,
    clock_out,
    total_hours,
    CONCAT(FLOOR(total_hours / 60), ' hr ', MOD(total_hours, 60), ' min') as formatted_duration
FROM attendance 
WHERE total_hours > 0
ORDER BY date DESC
LIMIT 20;
